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
    //echo json_encode(['error' => 'Unauthorized']);
    include ('error.php');
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

// Get userId and subject from query string
$userId = isset($_GET['userId']) ? $_GET['userId'] : null;
$subject = $_GET['subject'] ?? '';

if ( is_null($userId) ) {
    http_response_code(400);
    //echo json_encode(['error' => 'Missing userId parameter']);
    include ('error.php');
    exit();
}

try {
    // Fetch user profile to get the email
    $userProfile = $service->userProfiles->get($userId);
    $email = $userProfile->getEmailAddress();

    if (!$email) {
        http_response_code(404);
        echo json_encode(['error' => 'Email not found for user']);
        exit();
    }

    // Construct Gmail compose URL
    $gmailUrl = 'https://mail.google.com/mail/?view=cm&fs=1&to=' . urlencode($email) . '&su=' . urlencode($subject);

    // Redirect to Gmail compose
    header('Location: ' . $gmailUrl);
    exit();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to retrieve user email: ' . $e->getMessage()]);
}
?>
