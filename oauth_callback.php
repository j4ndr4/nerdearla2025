<?php
require_once 'vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$clientId = $_ENV['GOOGLE_CLIENT_ID'];
$clientSecret = $_ENV['GOOGLE_CLIENT_SECRET'];
$redirectUri = $_ENV['GOOGLE_REDIRECT_URI'];
$scopes = explode(' ', $_ENV['GOOGLE_SCOPES']);

// Create Google Client
$client = new Google_Client();
$client->setClientId($clientId);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->setScopes($scopes);
$client->setAccessType('offline');

// Handle the OAuth callback
if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);

    // Store the token in session for later use
    session_start();
    $_SESSION['access_token'] = $token;

    // Get the email and role
    require_once 'includes/User.php';


    // Get user's address
    $service = new Google_Service_Classroom($client);
    $userInfo = $service->userProfiles->get('me');
    $email = $userInfo->emailAddress;
    $user = new  $_ENV['USER_ROLES_CLASS'];
    $role = $user->getRole($email);
    $_SESSION['email'] = strtolower($email);
    $_SESSION['role'] = $role;

    // Redirect to classroom page
    if ($_SESSION['role'] == 'student')
        header('Location: classroom.php');
    else
        header('Location: panel.php');

    exit();
} else {
    echo "Authorization failed.";
}
?>
