<?php


// Get error message from query string or use default
$errorMessage = 'Ha ocurrido un error inesperado. Por favor, inténtalo de nuevo más tarde.';

// Get error code if provided
$errorCode = isset($_GET['code']) ? intval($_GET['code']) : 500;

// Set HTTP status code
http_response_code($errorCode);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Tu Google Classroom</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .error-container {
            text-align: center;
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .error-icon {
            font-size: 4rem;
            color: #F44336;
            margin-bottom: 20px;
        }
        .error-title {
            color: #F44336;
            font-size: 2rem;
            margin-bottom: 15px;
        }
        .error-message {
            color: #666;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .error-actions {
            margin-top: 30px;
        }
        .error-actions a {
            display: inline-block;
            margin: 0 10px;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn-primary:hover, .btn-secondary:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-container">
            <div class="error-icon">⚠️</div>
            <h1 class="error-title">¡Ups! Algo salió mal.</h1>
            <p class="error-message"><?php echo htmlspecialchars($errorMessage); ?></p>

            <div class="error-actions">
               
                <a href="index.php" class="btn-primary">Ir al inicio</a>
                
            </div>
        </div>
    </div>
</body>
</html>
