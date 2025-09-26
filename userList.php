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

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="user_list.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Write CSV header
    fputcsv($output, ['email', 'courseTitle', 'courseId', 'userId'], ',' , '"', '\\');

try {
    // List courses
    $courses = $service->courses->listCourses()->getCourses();

    foreach ($courses as $course) {
        $courseId = $course->getId();
        $courseTitle = $course->getName();

        try {
            // Get course roster (students)
            $students = $service->courses_students->listCoursesStudents($courseId)->getStudents();

            foreach ($students as $student) {
                $email = $student->getProfile()->getEmailAddress();
                $userId = $student->getUserId();

                // Write CSV row
                fputcsv($output, [$email, $courseTitle, $courseId, $userId], ',' , '"', '\\');
            }
        } catch (Exception $e) {
            // If can't get students for a course, skip or log
            // For now, skip
        }
    }
} catch (Exception $e) {
    // If can't list courses, output error in CSV
    fputcsv($output, ['Error', $e->getMessage(), '', '']);
}

// Close output stream
fclose($output);
?>
