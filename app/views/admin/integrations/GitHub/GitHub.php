<?php

class GitHub
{
    private $client;
    private $secret;

    public function __construct()
    {
        global $f3;
        $this->client = "Ov23liYhob2PacpCKe6C";
        $this->secret = "fd51dd005f972850200893a59ef063eee8c8301f";
    }

    function Init(): void
    {

        header('Content-Type: application/json');


        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }

        // Only allow POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit();
        }

        // Parse JSON input
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || !isset($input['code'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing code parameter']);
            exit();
        }

        $code = $input['code'];

        try {
            // Exchange code for access token
            $tokenResponse = $this->exchangeCodeForToken($code);

            if ($tokenResponse && isset($tokenResponse['access_token'])) {
                $accessToken = $tokenResponse['access_token'];

                // Fetch user info from GitHub
                $userInfo = $this->fetchGitHubUser($accessToken);

                echo json_encode([
                    'success' => true,
                    'access_token' => $accessToken,
                    'user' => $userInfo
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to obtain access token']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }

        exit;
    }

    /* Exchange GitHub OAuth code for access token
    *
    * @param string $code
    * @return array|null
    */
    private function exchangeCodeForToken($code)
    {
        $url = 'https://github.com/login/oauth/access_token';

        $data = [
            'client_id' => $this->client,
            'client_secret' => $this->secret,
            'code' => $code
        ];

        $options = [
            'http' => [
                'header' => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'User-Agent: PHP OAuth Client'
                ],
                'method' => 'POST',
                'content' => json_encode($data)
            ]
        ];

        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            throw new Exception('Failed to make request to GitHub');
        }

        $responseData = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from GitHub');
        }

        return $responseData;
    }

    private function fetchGitHubUser($accessToken)
    {
        $url = 'https://api.github.com/user';

        $options = [
            'http' => [
                'header' => [
                    "Authorization: token {$accessToken}",
                    "User-Agent: PHP OAuth Client",
                    "Accept: application/vnd.github.v3+json"
                ],
                'method' => 'GET'
            ]
        ];

        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            throw new Exception('Failed to fetch user info from GitHub');
        }

        $userData = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON user response from GitHub');
        }

        return $userData;
    }

}

