<?php
require_once 'vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$clientId = $_ENV['GOOGLE_CLIENT_ID'];
$redirectUri = $_ENV['GOOGLE_REDIRECT_URI'];
$scopes = explode(' ', $_ENV['GOOGLE_SCOPES']);

// Create Google Client
$client = new Google_Client();
$client->setClientId($clientId);
$client->setRedirectUri($redirectUri);
$client->setScopes($scopes);
$client->setAccessType('offline');

// Generate login URL
$loginUrl = $client->createAuthUrl();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu Google Classroom Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Bienvenido a Tu Google Classroom</h1>
        <p>Ingresa con tu cuenta de Google para acceder a las clases.</p>
        <a href="<?php echo htmlspecialchars($loginUrl); ?>" class="login-btn">Login con Google</a>
    </div>
</body>
</html>
