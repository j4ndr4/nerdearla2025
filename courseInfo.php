<?php
require_once 'vendor/autoload.php';

use Dotenv\Dotenv;

session_start();

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Check if user is logged in
if (!isset($_SESSION['access_token'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
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

// If token is expired, refresh it
if ($client->isAccessTokenExpired()) {
    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
    $_SESSION['access_token'] = $client->getAccessToken();
}

// Create Classroom service
$service = new Google_Service_Classroom($client);

// Get courseId from query string
$courseId = $_GET['courseId'] ?? null;
if (!$courseId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing courseId parameter']);
    exit();
}

$data = [
    'notifications' => [],
    'materials' => [],
    'courseWorks' => []
];

try {
    // Fetch announcements (notifications)
    $announcements = $service->courses_announcements->listCoursesAnnouncements($courseId);
    $data['notifications'] = $announcements->getAnnouncements();
} catch (Exception $e) {
    $data['notifications'] = ['error' => $e->getMessage()];
}

try {
    // Fetch course work materials
    $materials = $service->courses_courseWorkMaterials->listCoursesCourseWorkMaterials($courseId);
    $data['materials'] = $materials->getCourseWorkMaterial();
} catch (Exception $e) {
    $data['materials'] = ['error' => $e->getMessage()];
}

try {
    // Fetch course works
    $courseWorks = $service->courses_courseWork->listCoursesCourseWork($courseId);
    $data['courseWorks'] = $courseWorks->getCourseWork();
} catch (Exception $e) {
    $data['courseWorks'] = ['error' => $e->getMessage()];
}

// Set content type to JSON
header('Content-Type: application/json');

// Return the data as JSON
echo json_encode($data);
?>
