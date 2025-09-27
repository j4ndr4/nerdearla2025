<?php
require_once 'vendor/autoload.php';

use Dotenv\Dotenv;

session_start();

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

require_once 'includes/Group.php';
$groupClass = new  $_ENV['GROUPS_CLASS'];

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
    'totalSubmissions' => 0,
    'states' => [
        'TURNED_IN' => 0,
        'TURNED_IN_LATE' => 0,
        'RETURNED' => 0,
        'CREATED' => 0,
        'RECLAIMED_BY_STUDENT' => 0
    ],
    'works' => [],
    'groups' => []
];

$tempGroups = [];

try {
    // Fetch course works
    $courseWorks = $service->courses_courseWork->listCoursesCourseWork($courseId);
    $works = $courseWorks->getCourseWork();

    foreach ($works as $work) {
        $workId = $work->getId();
        $workTitle = $work->getTitle();

        $workData = [
            'title' => $workTitle,
            'states' => [
                'TURNED_IN' => 0,
                'TURNED_IN_LATE' => 0,
                'RETURNED' => 0,
                'CREATED' => 0,
                'RECLAIMED_BY_STUDENT' => 0
            ]
        ];

        $statesTable = [
            'TURNED_IN' => 'TURNED_IN',
            'NEW' => 'CREATED',
            'RETURNED' => 'RETURNED',
            'CREATED' => 'CREATED',
            'RECLAIMED_BY_STUDENT' => 'RECLAIMED_BY_STUDENT'
        ];
            
        try {
            // Fetch student submissions for this work
            $submissions = $service->courses_courseWork_studentSubmissions->listCoursesCourseWorkStudentSubmissions($courseId, $workId);
            $submissionList = $submissions->getStudentSubmissions();

            foreach ($submissionList as $submission) {
                $subGroup =$groupClass->getUserGroup($courseId, $submission->getUserId());
                if ( !isset($tempGroups[$subGroup])) {
                    $tempGroups[$subGroup] = [
                        'title' => $subGroup,
                        'states' => [
                            'TURNED_IN' => 0,
                            'TURNED_IN_LATE' => 0,
                            'RETURNED' => 0,
                            'CREATED' => 0,
                            'RECLAIMED_BY_STUDENT' => 0
                        ],
                        'students' => []
                    ];
                }
                if ( !isset($tempGroups[$subGroup]['students'][$submission->getUserId()])) {
                    // Fetch student profile to get the name
                    try {
                        $studentProfile = $service->userProfiles->get($submission->getUserId());
                        $studentName = $studentProfile->getName()->getFullName();
                    } catch (Exception $e) {
                        // Fallback to userId if profile can't be fetched
                        $studentName = $submission->getUserId();
                    }

                    $tempGroups[$subGroup]['students'][$submission->getUserId()] = [
                        'name' => $studentName,
                        'userId' => $submission->getUserId(),
                        'works' => []
                    ];
                }
                $state = $statesTable[$submission->getState()];
                $late = $submission->getLate();


                if ($state === 'TURNED_IN' && $late) {
                    $state = 'TURNED_IN_LATE';
                }
                
                $workData['states'][$state]++;
                $data['states'][$state]++;
                $tempGroups[$subGroup]['states'][$state]++;
                
                $data['totalSubmissions']++;
                $tempGroups[$subGroup]['students'][$submission->getUserId()]['works'][] = [
                    'link' => $submission->getAlternateLink(),
                    'title' => $workTitle,
                    'state' => $state,
                    //'late' => $late
                ];
            }
        } catch (Exception $e) {
            $workData['error'] = $e->getMessage();
        }

        $data['works'][] = $workData;
        
    }
    foreach ($tempGroups as $group) {
        $data['groups'][] = $group;
    }
} catch (Exception $e) {
    $data['error'] = $e->getMessage();
}

// Set content type to JSON
header('Content-Type: application/json');

// Return the data as JSON
echo json_encode($data);
?>
