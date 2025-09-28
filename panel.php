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
if ($role == 'student') {
    header('Location: classroom.php');
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
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Análisis</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <h1>Panel de Análisis</h1>
        <h2>Hola <?php echo htmlspecialchars($_SESSION['email']);?></h2>

        <div class="selects">
            <label for="course-select">Selecciona una clase:</label>
            <select id="course-select" name="course" onChange="getCourseInfo()">
                <option value="">Selecciona una clase</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo htmlspecialchars($course->getId()); ?>" ><?php echo htmlspecialchars($course->getName()); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="loading" style="display: none;">
                Cargando...
            </div>

        <div id="error-message" style="display: none;">
            <p>Se produjo un error. Por favor espera unos minutos y vuelve a entrar en la aplicación. Si el error persiste, contacta al administrador.</p>
        </div>

        <div id="no-data" style="display: none;">
                No hay datos
        </div>
        <div class="charts" style="display: none;" id="course-charts">
            <h2>Información de la clase: <span id="course-name"></span></h2>
            
            <div class="chart-container">
                <h3>Entregas totales</h3>
                <canvas id="pie-chart"></canvas>

                <p> Total de trabajos asignados: <span id="course-assignments"></span></p>
                <p> Total de trabajos corregidos: <span id="course-returned-works"></span> (<span id="course-returned-works-percent"></span>%)</p>
                <p> Total de trabajos entregados sin corregir: <span id="course-turned-in-works"></span> (<span id="course-turned-in-works-percent"></span>%)</p>
            </div>

            <div class="chart-container">
                <h3>Entregas por trabajo</h3>
                <canvas id="work-bar-chart"></canvas>
            </div>

            <div class="selects">
                <label for="group-select">Selecciona una célula:</label>
                <select id="group-select" name="group" onChange="getGroupInfo()">
                    <option value="all">todas las células</option>
                </select>
            </div>

            <div class="chart-container">
                <h3>Entregas por célula</h3>
                <canvas id="group-bar-chart"></canvas>
                <div id="group-info" style="display: none;">
                    <p> Trabajos asignados en esta célula: <span id="group-assignments"></span></p>
                    <p> Trabajos corregidos en esta célula: <span id="group-returned-works"></span> (<span id="group-returned-works-percent"></span>%)</p>
                    <p> Trabajos entregados sin corregir en esta célula: <span id="group-turned-in-works"></span> (<span id="group-turned-in-works-percent"></span>%)</p>
                </div>
            </div>

            <div class="student-filters">
                    <div class="filter-group">
                        <label for="student-search">Buscar estudiante:</label>
                        <input type="text" id="student-search" placeholder="Nombre del estudiante..." onkeyup="filterStudents()">
                    </div>
                    <div class="filter-group" style="display: none;">
                        <label for="status-filter">Filtrar por estado:</label>
                        <select id="status-filter" onchange="filterStudents()">
                            <option value="all">Todos los estados</option>
                            <option value="corrected">Corregidos</option>
                            <option value="turned-in">Entregados (sin corregir)</option>
                            <option value="not-turned-in">No entregados</option>
                            <option value="mixed">Estado mixto</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="work-filter">Filtrar por trabajo:</label>
                        <select id="work-filter" onchange="filterStudents()">
                            <option value="all">Todos los trabajos</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="work-status-filter">Estado del trabajo:</label>
                        <select id="work-status-filter" onchange="filterStudents()">
                            <option value="all">Todos los estados</option>
                            <option value="corrected">Corregido</option>
                            <option value="turned-in">Entregado</option>
                            <option value="not-turned-in">No entregado</option>
                            <option value="reclaimed">Reclamado</option>
                        </select>
                    </div>
                </div>
            <div id="students" style="display: none;">
                
            </div>
        </div>

        

        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

    <template id="student-template">
        <div class="student-card">
            <div class="student-header">
                <p>Estudiante: <span class="student-name"></span></p>
                <p>Grupo: <span class="student-group"></span></p>
                <button class="toggle-details-btn" onclick="toggleStudentDetails(this)">Mostrar detalles</button>
            </div>
            <div class="student-works" style="display: none;">
            </div>
        </div>
    </template>
    <template id="work-template">
        <div class="work-card">
            <p>Trabajo: <span class="work-title"></span> <a href="" class="work-link" target="_blank"> (Ver en Classroom ⤴️)</a></p>
            <p>Estado: <span class="work-state"></span></p>
        </div>
    </template>
    <script>

        let stateTable = { 
                            'CREATED' : 'No entregado'
                            , 'RECLAIMED_BY_STUDENT' : 'Reclamado'
                            , 'RETURNED' : 'Corregido'
                            , 'TURNED_IN' : 'Entregado a tiempo'
                            , 'TURNED_IN_LATE' : 'Entregado con retraso' };

        let pieChart;
        let workBarChart;
        let groupBarChart;
        let groupInfo;
        let worksData;

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

        function showLoading() {
                document.getElementById('loading').style.display = 'block';
        }
        function hideLoading() {
                document.getElementById('loading').style.display = 'none';
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

        function getCourseInfo() {

            const courseId = document.getElementById('course-select').value;

            document.getElementById('course-charts').style.display = 'none';
            document.getElementById('no-data').style.display = 'none';
            document.getElementById('group-info').style.display = 'none';
            for (let i = document.getElementById('students').children.length - 1; i >= 0; i--) {
                document.getElementById('students').children[i].remove();
            }

            if (pieChart) {
                pieChart.destroy();
            }
            if (workBarChart) {
                workBarChart.destroy();
            }
            if (groupBarChart) {
                groupBarChart.destroy();
            }
            for (let i = document.getElementById('group-select').options.length - 1; i > 0; i--) {
                        document.getElementById('group-select').remove(i);
            }
            
            // Reset work filter
            const workFilter = document.getElementById('work-filter');
            while (workFilter.options.length > 1) {
                workFilter.remove(1);
            }
            workFilter.value = 'all';
            document.getElementById('work-status-filter').value = 'all';

            if (courseId) {
                document.getElementById('course-name').textContent = document.getElementById('course-select').options[document.getElementById('course-select').selectedIndex].text;
                showLoading();
                ajaxGet('panelInfo.php?courseId=' + encodeURIComponent(courseId), function(response) {
                    
                    let data;
                    try {
                        data = JSON.parse(response);
                    } catch (e) {
                        hideLoading();
                        document.getElementById('error-message').style.display = 'block';
                        return;
                    }
                    console.log(data);

                    if ( typeof (data.states) == 'undefined')
                    {
                        document.getElementById('error-message').style.display = 'block';
                        return;
                    }

                    let totalStates = parseInt (data.states.CREATED) + parseInt (data.states.RECLAIMED_BY_STUDENT) + parseInt (data.states.RETURNED) + parseInt (data.states.TURNED_IN) + parseInt (data.states.TURNED_IN_LATE);

                    if (totalStates > 0) {
                        document.getElementById('course-assignments').textContent = totalStates;
                        document.getElementById('course-returned-works').textContent = data.states.RETURNED;
                        document.getElementById('course-returned-works-percent').textContent =  Math.round ( parseInt(data.states.RETURNED) * 100 / totalStates ) ;
                        let totalTurnedIn = parseInt (data.states.TURNED_IN) + parseInt (data.states.TURNED_IN_LATE);
                        document.getElementById('course-turned-in-works').textContent = totalTurnedIn;
                        document.getElementById('course-turned-in-works-percent').textContent =  Math.round ( totalTurnedIn * 100 / totalStates ) ;
                        const pieData = {
                            labels: [stateTable.CREATED, stateTable.RECLAIMED_BY_STUDENT, stateTable.RETURNED, stateTable.TURNED_IN, stateTable.TURNED_IN_LATE],
                            datasets: [{
                                data: [ data.states.CREATED, data.states.RECLAIMED_BY_STUDENT, data.states.RETURNED, data.states.TURNED_IN, data.states.TURNED_IN_LATE],
                                backgroundColor: ['#F44336', '#FFC107', '#4CAF50', '#2196F3', '#9C27B0']
                            }]
                        };

                        pieChart = new Chart(document.getElementById('pie-chart'), {
                            type: 'pie',
                            data: pieData
                        });


                        let workBarData = {
                            labels: [],
                            datasets: [{
                                label: stateTable.CREATED,
                                data: [],
                                backgroundColor: '#F44336'
                            }, {
                                label: stateTable.RECLAIMED_BY_STUDENT,
                                data: [],
                                backgroundColor: '#FFC107'
                            }, {
                                label: stateTable.RETURNED,
                                data: [],
                                backgroundColor: '#4CAF50'
                            }, {
                                label: stateTable.TURNED_IN,
                                data: [],
                                backgroundColor: '#2196F3'
                            }, {
                                label: stateTable.TURNED_IN_LATE,
                                data: [],
                                backgroundColor: '#9C27B0'
                            }]
                        };
                        for (let i = 0; i < data.works.length; i++) {
                            const work = data.works[i];
                            workBarData.labels.push( work.title);
                            workBarData.datasets[0].data.push(work.states.CREATED);
                            workBarData.datasets[1].data.push(work.states.RECLAIMED_BY_STUDENT);
                            workBarData.datasets[2].data.push(work.states.RETURNED);
                            workBarData.datasets[3].data.push(work.states.TURNED_IN);
                            workBarData.datasets[4].data.push(work.states.TURNED_IN_LATE);
                        }
                        document.getElementById('work-bar-chart').style.maxHeight = ( 100 * data.works.length )+'px';
                        workBarChart = new Chart(document.getElementById('work-bar-chart'), {
                            type: 'bar',
                            data: workBarData,
                            options: {
                                indexAxis: 'y',
                                scales: {
                                    x: { stacked: true },
                                    y: { stacked: true }
                                }
                            }
                        });

                        // Populate work filter dropdown
                        worksData = data.works;
                        const workFilter = document.getElementById('work-filter');
                        // Clear existing options except "All works"
                        while (workFilter.options.length > 1) {
                            workFilter.remove(1);
                        }
                        // Add works to dropdown
                        for (let i = 0; i < data.works.length; i++) {
                            const option = document.createElement('option');
                            option.value = i; // Use index as value
                            option.textContent = sanitizeInput(data.works[i].title);
                            workFilter.appendChild(option);
                        }

                        document.getElementById('course-charts').style.display = 'block';
                        document.getElementById('students').style.display = 'block';
                    }
                    else {
                        document.getElementById('no-data').style.display = 'block';
                    }

                    const groupBarData = {
                        labels: [],
                        datasets: [{
                                label: stateTable.CREATED,
                                data: [],
                                backgroundColor: '#F44336'
                            }, {
                                label: stateTable.RECLAIMED_BY_STUDENT,
                                data: [],
                                backgroundColor: '#FFC107'
                            }, {
                                label: stateTable.RETURNED,
                                data: [],
                                backgroundColor: '#4CAF50'
                            }, {
                                label: stateTable.TURNED_IN,
                                data: [],
                                backgroundColor: '#2196F3'
                            }, {
                                label: stateTable.TURNED_IN_LATE,
                                data: [],
                                backgroundColor: '#9C27B0'
                            }]
                    };

                    groupInfo = data.groups;

                    for (let i = 0; i < data.groups.length; i++) {
                        const group = data.groups[i];

                        // Create a new option element
                        let option = document.createElement('option');
                        option.value = sanitizeInput(group.title);
                        option.textContent = sanitizeInput(group.title);
                        document.getElementById('group-select').appendChild( option );

                        groupBarData.labels.push( group.title);
                        groupBarData.datasets[0].data.push(group.states.CREATED);
                        groupBarData.datasets[1].data.push(group.states.RECLAIMED_BY_STUDENT);
                        groupBarData.datasets[2].data.push(group.states.RETURNED);
                        groupBarData.datasets[3].data.push(group.states.TURNED_IN);
                        groupBarData.datasets[4].data.push(group.states.TURNED_IN_LATE);


                        
                        studentIds = Object.keys(group.students);
                        for (let j = 0; j < studentIds.length; j++) {
                            let studentTemplate = document.getElementById('student-template').content.cloneNode(true);
                            studentTemplate.querySelector('.student-name').textContent = sanitizeInput(group.students[studentIds[j]].name);
                            studentTemplate.querySelector('.student-group').textContent  = sanitizeInput(group.title);
                            //studentTemplate.querySelector('.group-date').textContent = group.creationTime;

                            for (let k = 0; k < group.students[studentIds[j]].works.length; k++) {
                                let workTemplate = document.getElementById('work-template').content.cloneNode(true);
                                workTemplate.querySelector('.work-title').textContent = sanitizeInput(group.students[studentIds[j]].works[k].title);
                                workTemplate.querySelector('.work-state').textContent = stateTable[group.students[studentIds[j]].works[k].state];
                                workTemplate.querySelector('.work-link').href = sanitizeUrl(group.students[studentIds[j]].works[k].link);
                                if (group.students[studentIds[j]].works[k].state == 'RETURNED') {
                                    workTemplate.querySelector('.work-card').classList.add('work-link-corrected');
                                } else if (group.students[studentIds[j]].works[k].state == 'TURNED_IN' || group.students[studentIds[j]].works[k].state == 'TURNED_IN_LATE') {
                                    workTemplate.querySelector('.work-card').classList.add('work-link-turned-in');
                                }
                                studentTemplate.querySelector('.student-works').appendChild(workTemplate);
                            }
                            document.getElementById('students').appendChild(studentTemplate);
                        }
                    }
                    
                    document.getElementById('group-bar-chart').style.maxHeight = ( 100 * data.groups.length )+'px';
                    groupBarChart = new Chart(document.getElementById('group-bar-chart'), {
                        type: 'bar',
                        data: groupBarData,
                        options: {
                            indexAxis: 'y',
                            scales: {
                                x: { stacked: true },
                                y: { stacked: true }
                            }
                        }
                    });

                    hideLoading();
                    
                });
            }
        }
        
        function getGroupInfo() {
            groupLabel = document.getElementById('group-select').value;

            if (groupBarChart) {
                groupBarChart.destroy();
            }

            const groupBarData = {
                        labels: [],
                        datasets: [{
                                label: stateTable.CREATED,
                                data: [],
                                backgroundColor: '#F44336'
                            }, {
                                label: stateTable.RECLAIMED_BY_STUDENT,
                                data: [],
                                backgroundColor: '#FFC107'
                            }, {
                                label: stateTable.RETURNED,
                                data: [],
                                backgroundColor: '#4CAF50'
                            }, {
                                label: stateTable.TURNED_IN,
                                data: [],
                                backgroundColor: '#2196F3'
                            }, {
                                label: stateTable.TURNED_IN_LATE,
                                data: [],
                                backgroundColor: '#9C27B0'
                            }]
                    };

            let groupIndex = -1;
            for (let i = 0; i < groupInfo.length; i++) {
                if (groupInfo[i].title === groupLabel) {
                    groupIndex = i;
                }
            }
            if (groupIndex === -1) {
                for (let i = 0; i < groupInfo.length; i++) {
                        const group = groupInfo[i];
                        groupBarData.labels.push( group.title);
                        groupBarData.datasets[0].data.push(group.states.CREATED);
                        groupBarData.datasets[1].data.push(group.states.RECLAIMED_BY_STUDENT);
                        groupBarData.datasets[2].data.push(group.states.RETURNED);
                        groupBarData.datasets[3].data.push(group.states.TURNED_IN);
                        groupBarData.datasets[4].data.push(group.states.TURNED_IN_LATE);
                }
                document.getElementById('group-info').style.display = 'none';

                for (let i = document.getElementById('students').children.length - 1; i > 0; i--) {
                    document.getElementById('students').children[i].style.display = 'block';
                }
            }
            else
            {
                const group = groupInfo[groupIndex];
                groupBarData.labels.push(group.title);
                groupBarData.datasets[0].data.push(group.states.CREATED);
                groupBarData.datasets[1].data.push(group.states.RECLAIMED_BY_STUDENT);
                groupBarData.datasets[2].data.push(group.states.RETURNED);
                groupBarData.datasets[3].data.push(group.states.TURNED_IN);
                groupBarData.datasets[4].data.push(group.states.TURNED_IN_LATE);
                document.getElementById('group-info').style.display = 'block';
                let totalAssigments = parseInt(group.states.CREATED)+parseInt(group.states.RECLAIMED_BY_STUDENT)+parseInt(group.states.RETURNED)+parseInt(group.states.TURNED_IN)+parseInt(group.states.TURNED_IN_LATE);
                document.getElementById('group-assignments').textContent = totalAssigments;
                document.getElementById('group-returned-works').textContent = group.states.RETURNED;
                document.getElementById('group-returned-works-percent').textContent = Math.round ( parseInt(group.states.RETURNED) * 100 / totalAssigments ) ;
                document.getElementById('group-turned-in-works').textContent = parseInt(group.states.TURNED_IN)+parseInt(group.states.TURNED_IN_LATE);
                document.getElementById('group-turned-in-works-percent').textContent = Math.round ( (parseInt(group.states.TURNED_IN)+parseInt(group.states.TURNED_IN_LATE)) * 100 / totalAssigments ) ;

                for (let i = document.getElementById('students').children.length - 1; i >= 0; i--) {
                    if (document.getElementById('students').children[i].querySelector('.student-group').textContent === groupLabel) {
                        document.getElementById('students').children[i].style.display = 'block';
                    }
                    else {
                        document.getElementById('students').children[i].style.display = 'none';
                    }
                }       
            }
            filterStudents();

            document.getElementById('group-bar-chart').style.maxHeight = ( 100 * ( groupIndex === -1 ? groupInfo.length : 1 ) )+'px';
            groupBarChart = new Chart(document.getElementById('group-bar-chart'), {
                        type: 'bar',
                        data: groupBarData,
                        options: {
                            indexAxis: 'y',
                            scales: {
                                x: { stacked: true },
                                y: { stacked: true }
                            }
                        }
                    });
        }
       
        function toggleStudentDetails(button) {
            const studentCard = button.closest('.student-card');
            const studentWorks = studentCard.querySelector('.student-works');
            
            if (studentWorks.style.display === 'none' || studentWorks.style.display === '') {
                studentWorks.style.display = 'block';
                button.textContent = 'Ocultar detalles';
            } else {
                studentWorks.style.display = 'none';
                button.textContent = 'Mostrar detalles';
            }
        }

        function filterStudents() {
            const searchTerm = document.getElementById('student-search').value.toLowerCase();
            const statusFilter = document.getElementById('status-filter').value;
            const workFilterIndex = document.getElementById('work-filter').value;
            const workStatusFilter = document.getElementById('work-status-filter').value;
            const studentCards = document.querySelectorAll('.student-card');
            const groupLabel = document.getElementById('group-select').value;

            studentCards.forEach(card => {
                const studentName = card.querySelector('.student-name').textContent.toLowerCase();
                const workCards = card.querySelectorAll('.work-card');
                
                // Check name filter
                const nameMatch = studentName.includes(searchTerm);
                
                // Check status filter (overall student status)
                let statusMatch = true;
                if (statusFilter !== 'all') {
                    let hasCorrected = false;
                    let hasTurnedIn = false;
                    let hasNotTurnedIn = false;
                    
                    workCards.forEach(workCard => {
                        const state = workCard.querySelector('.work-state').textContent;
                        if (state === stateTable.RETURNED) {
                            hasCorrected = true;
                        } else if (state === stateTable.TURNED_IN || state === stateTable.TURNED_IN_LATE) {
                            hasTurnedIn = true;
                        } else if (state === stateTable.CREATED || state === stateTable.RECLAIMED_BY_STUDENT) {
                            hasNotTurnedIn = true;
                        }
                    });
                    
                    switch (statusFilter) {
                        case 'corrected':
                            statusMatch = hasCorrected && !hasNotTurnedIn;
                            break;
                        case 'turned-in':
                            statusMatch = hasTurnedIn && !hasCorrected && !hasNotTurnedIn;
                            break;
                        case 'not-turned-in':
                            statusMatch = hasNotTurnedIn && !hasCorrected && !hasTurnedIn;
                            break;
                        case 'mixed':
                            statusMatch = (hasCorrected && hasTurnedIn) || (hasCorrected && hasNotTurnedIn) || (hasTurnedIn && hasNotTurnedIn);
                            break;
                    }
                }

                // Check work-specific filter
                let workMatch = true;
                if (workFilterIndex !== 'all' && workStatusFilter !== 'all') {
                    workMatch = false;
                    const targetWorkTitle = worksData[parseInt(workFilterIndex)].title;
                    
                    workCards.forEach(workCard => {
                        const workTitle = workCard.querySelector('.work-title').textContent;
                        const workState = workCard.querySelector('.work-state').textContent;
                        
                        if (workTitle === targetWorkTitle) {
                            let statusMatches = false;
                            switch (workStatusFilter) {
                                case 'corrected':
                                    statusMatches = workState === stateTable.RETURNED;
                                    break;
                                case 'turned-in':
                                    statusMatches = workState === stateTable.TURNED_IN || workState === stateTable.TURNED_IN_LATE;
                                    break;
                                case 'not-turned-in':
                                    statusMatches = workState === stateTable.CREATED;
                                    break;
                                case 'reclaimed':
                                    statusMatches = workState === stateTable.RECLAIMED_BY_STUDENT;
                                    break;
                            }
                            if (statusMatches) {
                                workMatch = true;
                            }
                        }
                    });
                }

                let groupMatch = true;
                if (groupLabel !== 'all') {
                    groupMatch = card.querySelector('.student-group').textContent === groupLabel;
                }
                
                // Show/hide card based on filters
                if (nameMatch && statusMatch && workMatch && groupMatch) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
