<?php

$f3->set('PAGE.title', 'Forgot Password');

if (empty($f3->get('SESSION.token'))) {
    $csrf = md5(uniqid(mt_rand(), true));
    $f3->set('SESSION.token', $csrf);
}
$f3->set('TOKEN', $f3->get('SESSION.token'));

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    try {
        // Initialize response class (assuming you have one)
        $response = new Response();

        // Fetch the database instance (assuming you use Fat-Free or similar)
        global $db;

        // Retrieve and decode the JSON payload
        $inputData = json_decode(file_get_contents('php://input'), true);

        // Retrieve the form data sent via POST
        $action = isset($inputData['action']) ? htmlspecialchars($inputData['action']) : null;
        $user_email = isset($inputData['username']) ? filter_var(strtolower($inputData['username']), FILTER_SANITIZE_EMAIL) : null;
        $user_password = isset($inputData['password']) ? htmlspecialchars($inputData['password']) : null;
        $user_otp = isset($inputData['otp']) ? htmlspecialchars($inputData['otp']) : null;
        $token = isset($inputData['token']) ? htmlspecialchars($inputData['token']) : null;

        // Validate required fields
        if (!$action || !$user_email) {
            $response->json('error', 'Missing required fields.');
            exit;
        }

        // Validate CSRF token
        $csrf = $f3->get('SESSION.token');
        if (!$token && $token != $csrf) {
            $response->json('error', 'CSRF token mismatch error.');
            exit;
        }

        // Prepare the database query (Users)
        $users = new DB\SQL\Mapper($db, 'users');
        $users->load(array('email=?', $user_email));
        if ($users->dry()) {
            $response->json('error', $user_email . ' was not found in our records.');
            exit;
        }

        switch ($action) {
            case "forgot":
                $otp_gen = random_int(100000, 999999);

                $users->nonce = $otp_gen;
                if ($users->save()) {
                    $mail = new Mail;
                    $mail->sendEmail($user_email, 'Verification Code', 'otpCode', ['otp' => $otp_gen]);
                    $message = $f3->get('CSRF');
                } else {
                    $response->json('error', 'There was an error processing your request. Please, try again later.');
                    exit;
                }

                break;
            case "otp":
                $otp_secret = $users->nonce;

                if ($user_otp != $otp_secret) {
                    $response->json('error', 'Invalid OTP code.');
                    exit;
                }
                $message = $f3->get('CSRF');
                break;
            case "crypt":
                $crypt = new Crypt;
                if ($user_otp != $users->nonce) {
                    $response->json('error', 'Nonce mismatch error.');
                    exit;
                } else
                if (empty($user_password)) {
                    $response->json('error', 'Password cannot be empty.');
                    exit;
                } else 
                if (strlen($user_password) < 8) {
                    $response->json('error', 'Password does not match criteria.');
                    exit;
                } else
                if ($crypt->verify($user_password, $users->crypt)) {
                    $response->json('error', 'Password cannot be the same as current password.');
                    exit;
                }
                $new_password = $crypt->generate($user_password);
                $users->crypt = $new_password;
                $users->save();

                $message = $f3->get('SCHEME') . '://' . $f3->get('HOST') . '/' . $f3->get('SITE.uri_auth');
                break;
        }

        $response->json('success', $message);
    } catch (Exception $e) {
        // Send error message if something went wrong
        $response->json('error', 'Unable to process your request. Error: ' . $e->getMessage());
    }

    exit;
}
