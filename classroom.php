<?php
require_once 'vendor/autoload.php';

use Dotenv\Dotenv;

session_start();

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Check if user is logged in
if (!isset($_SESSION['access_token'])) {
    header('Location: index.php');
    exit();
}


require_once 'includes/User.php';
$user = new  $_ENV['USER_ROLES_CLASS'];
$role = $user->getRole($_SESSION['email']);
if ($role != 'student') {
    header('Location: panel.php');
    exit();
}

$clientId = $_ENV['GOOGLE_CLIENT_ID'];
$clientSecret = $_ENV['GOOGLE_CLIENT_SECRET'];
$redirectUri = $_ENV['GOOGLE_REDIRECT_URI'];

// Create Google Client
$client = new Google_Client();
$client->setClientId($clientId);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);

// Set the access token from session
$client->setAccessToken($_SESSION['access_token']);


try {
    // If token is expired, refresh it
    if ($client->isAccessTokenExpired()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        $_SESSION['access_token'] = $client->getAccessToken();
    }

    // Create Classroom service
    $service = new Google_Service_Classroom($client);


    // List courses
    $courses = $service->courses->listCourses()->getCourses();
} catch (Exception $e) {
    //echo "Error: " . $e->getMessage();
    include ('error.php');
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu Google Classroom</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Tu Google Classroom</h1>
        <h2>Hola <?php echo htmlspecialchars($_SESSION['email']);?></h2>
        
        <?php if (empty($courses)): ?>
            <p>No hay clases.</p>
        <?php else: ?>
            <div class="classrooms">
                <form>
                    <label for="course-select">Selecciona una clase:</label>
                    <select id="course-select" name="course" onchange="changeCourse()">
                        <option value="">Selecciona una clase</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo htmlspecialchars($course->getId()); ?>"><?php echo htmlspecialchars($course->getName()); ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <div id="loading" style="display: none;">
                Cargando...
            </div>
            <div id="error-message" style="display: none;">
                <p>Se produjo un error. Por favor espera unos minutos y vuelve a entrar en la aplicación. Si el error persiste, contacta al administrador.</p>
            </div>

            <div id="course-info" style="display: none;">
                <h2>Información de la clase: <span id="course-name"></span></h2>
                Notificaciones:
                <div id="notifications"></div>
                Materiales:
                <div id="materials"></div>
                Trabajos:
                <div id="course-works"></div>
            </div>
       
        <?php endif; ?>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

    <template id="notification-template">  
        <div class="notification">
            <p class="notification-text"></p>
            <p><a href=""  class="notification-link" target="_blank">Ver en Classroom ⤴️</a> | Fecha de creación: <span class="notification-date"></span></p>
        </div>
    </template>
    <template id="material-template">
        <div class="material">
            <p class="material-title"></p>
            <p class="material-description"></p>
            <p><a href="" class="material-link" target="_blank">Ver en Classroom ⤴️</a> | Fecha de creación: <span class="material-date"></span></p>
            
        </div>
    </template>
    <template id="course-work-template">
        <div class="course-work">
            <p class="course-work-title"></p>
            <p class="course-work-description"></p>
            <p><a href="" class="course-work-link" target="_blank">Ver en Classroom ⤴️</a> | Fecha de creación: <span class="course-work-date"></span></p>
            
        </div>
    </template>
</body>
</html>

<script language="javascript">

    function sanitizeInput(input) {
        return input
            .trim() // Remove whitespace
            .replace(/[<>]/g, '') // Remove potential HTML tags
            .substring(0, 100); // Limit length
    }

    function sanitizeUrl(url) {
        try {
            const parsedUrl = new URL(url);
            // Only allow http/https protocols
            if (!['http:', 'https:'].includes(parsedUrl.protocol)) {
                return '#';
            }
            return parsedUrl.href;
        } catch {
            return '#';
        }
    }

    function ajaxGet(url, callback) {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                callback(xhr.responseText);
            }
            else if (xhr.readyState === 4){
                hideLoading();
                document.getElementById('error-message').style.display = 'block';
            }
        };
        xhr.send();
    }

    function changeCourse() {
        const courseId = document.getElementById('course-select').value;
        document.getElementById('course-info').style.display = 'none';
        if (courseId) {
            document.getElementById('course-name').textContent = document.getElementById('course-select').options[document.getElementById('course-select').selectedIndex].text;
            showLoading();
            ajaxGet('courseInfo.php?courseId=' + encodeURIComponent(courseId), function(response) {
                
                let data;
                try {
                    data = JSON.parse(response);
                } catch (e) {
                    hideLoading();
                    document.getElementById('error-message').style.display = 'block';
                    return;
                }
                console.log(data);
                if ( typeof (data.notifications) == 'undefined')
                {
                    document.getElementById('error-message').style.display = 'block';
                    return;
                }

                document.getElementById('notifications').innerHTML = '';
                document.getElementById('materials').innerHTML = '';
                document.getElementById('course-works').innerHTML = '';
                document.getElementById('course-info').style.display = 'block';
                
                for (let i = 0; i < data.notifications.length; i++) {
                    const notification = data.notifications[i];
                    let notificationTemplate = document.getElementById('notification-template').content.cloneNode(true);
                    notificationTemplate.querySelector('.notification-text').textContent = sanitizeInput(notification.text);
                    
                    let date = new Date(notification.creationTime);
                    notificationTemplate.querySelector('.notification-date').textContent = date.toLocaleString() ;
                    notificationTemplate.querySelector('.notification-link').href = sanitizeUrl(notification.alternateLink);
                    document.getElementById('notifications').appendChild(notificationTemplate);
                }
                if (data.notifications.length === 0) {
                    const notificationDiv = document.createElement('div');
                    notificationDiv.innerHTML = 'No hay notificationes';
                    document.getElementById('notifications').appendChild(notificationDiv);
                }
                for (let i = 0; i < data.materials.length; i++) {
                    const material = data.materials[i];
                    let materialTemplate = document.getElementById('material-template').content.cloneNode(true);
                    materialTemplate.querySelector('.material-title').textContent = sanitizeInput(material.title);
                    materialTemplate.querySelector('.material-description').textContent = sanitizeInput(material.description);
                    materialTemplate.querySelector('.material-link').href = sanitizeUrl(material.alternateLink);
                    let date = new Date(material.updateTime);
                    materialTemplate.querySelector('.material-date').textContent = date.toLocaleString() ;
                    document.getElementById('materials').appendChild(materialTemplate);
                }
                if (data.materials.length === 0) {
                    const materialDiv = document.createElement('div');
                    materialDiv.innerHTML = 'No hay materiales';
                    document.getElementById('materials').appendChild(materialDiv);
                }
                for (let i = 0; i < data.courseWorks.length; i++) {
                    const courseWork = data.courseWorks[i];
                    let courseWorkTemplate = document.getElementById('course-work-template').content.cloneNode(true);
                    courseWorkTemplate.querySelector('.course-work-title').textContent = sanitizeInput(courseWork.title);
                    courseWorkTemplate.querySelector('.course-work-description').textContent = sanitizeInput(courseWork.description);
                    courseWorkTemplate.querySelector('.course-work-link').href = sanitizeUrl(courseWork.alternateLink);
                    let date = new Date(courseWork.updateTime);
                    courseWorkTemplate.querySelector('.course-work-date').textContent = date.toLocaleString() ;
                    document.getElementById('course-works').appendChild(courseWorkTemplate);
                }
                if (data.courseWorks.length === 0) {
                    const courseWorkDiv = document.createElement('div');
                    courseWorkDiv.innerHTML = 'No hay trabajos';
                    document.getElementById('course-works').appendChild(courseWorkDiv);
                }
                hideLoading();
            });
        }
        function showLoading() {
            document.getElementById('loading').style.display = 'block';
        }
        function hideLoading() {
            document.getElementById('loading').style.display = 'none';
        }
    }   
</script>