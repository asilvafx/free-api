<?php

$f3->set('PAGE.title', 'Sign in');

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
        $user_pass = isset($inputData['crypt']) ? base64_decode($inputData['crypt']) : null;
        $rememberme = isset($inputData['rememberme']) ? htmlspecialchars($inputData['rememberme']) : null;
        $token = isset($inputData['token']) ? htmlspecialchars($inputData['token']) : null;

        // Validate required fields
        if (!$action || !$user_email) {
            $response->json('error', 'Missing required fields.');
            exit;
        }

        // Validate CSRF token
        $csrf = $f3->get('SESSION.token');
        if (!$token || $token != $csrf) {
            $response->json('error', 'CSRF token mismatch error. Please reload your browser and try again.');
            exit;
        }

        $message = null;

        // Prepare the database query (Users)
        $users = new DB\SQL\Mapper($db, 'users');
        $users->load(array('email=?', $user_email));
        if ($users->dry()) {
            $response->json('error', $user_email . ' was not found in our records.');
            exit;
        }

        switch ($action) {
            case "auth":
                $message = $f3->get('CSRF');
                break;
            case "login":
                $crypt = new Crypt;
                if ($crypt->verify($user_pass, $users->crypt)) {

                    $login_alerts = $users->login_alerts == 1;
                    $pin = $users->pin == 1;
                    $twofactor = $users->twofactor == 1;
                    $passkey = $users->passkey == 1;

                    $message = 'pass';
                    if ($pin || $twofactor || $passkey) {
                        $message = [['token' => $f3->get('CSRF')], ['otp' => ($pin ? 'pin' : '') . ($twofactor ? ', twofactor' : '') . ($passkey ? ', passkey' : '')]];
                    } else {
                        $f3->set('SESSION.loggedin', true);
                        $f3->set('SESSION.username', $user_email);
                        $f3->set('SESSION.timestamp', time());
                        if (!empty($rememberme) && $rememberme == true) {
                            $f3->set('SESSION.rememberme', true);
                        }
                        $current_ip = $f3->get('CLIENT.ip');
                        $login_alerts = false;
                        $login_count = $users->login_count;
                        $users->last_online = time();
                        $users->login_count = $login_count+1;
                        if($current_ip !== $users->ip_address){
                          $login_alerts = $users->login_alerts == 1;
                          $users->ip_address = $current_ip;
                        }
                        $users->save();
                        if($login_alerts){
                          $mail = new Mail;
                          $mail->sendEmail($user_email, 'New login on '.$f3->get('SITE.name'), 'loginAlert', ['ip' => $f3->get('CLIENT.ip')]);
                        }
                    }
                } else {
                    $current_try = $users->login_try_count;
                    $users->login_last_try = time();
                    $users->login_try_count = $current_try+1;
                    $users->save();

                    $response->json('error', 'Password do not match with our records.');
                    exit;
                }

                break;
            case "otp":
                $crypt = new Crypt;
                if ($crypt->verify($user_pass, $users->crypt)) {

                    $pin = isset($inputData['pin']) ? htmlspecialchars($inputData['pin']) : null;
                    $twofactor = isset($inputData['twofactor']) ? htmlspecialchars($inputData['twofactor']) : null;
                    $passkey = isset($inputData['passkey']) ? htmlspecialchars($inputData['passkey']) : null;

                    $message = null;
                    $requestPass = false;

                    if ($pin) {
                        if ($pin === $users->pin_code) {
                            $requestPass = true;
                        }
                    } else 
                    if ($twofactor) {
                        $tfa = new TwoFactor;
                        
                        $window = 4; // 4 keys (respectively 2 minutes) past and future

                        $valid = $tfa->verifyKey($users->twofactor_sk, $twofactor, $window);

                        if ($valid) { $requestPass = true; }
                    } else 
                    if ($passkey) {
                        if ($users->passkey_id === $passkey) { $requestPass = true; }
                    } else {
                        $response->json('error', '2FA method not defined.');
                        exit;
                    }
                } else {
                    $current_try = $users->login_try_count;
                    $users->login_last_try = time();
                    $users->login_try_count = $current_try+1;
                    $users->save();

                    $response->json('error', 'Password do not match with our records.');
                    exit;
                }
                if ($requestPass) {
                    $f3->set('SESSION.loggedin', true);
                    $f3->set('SESSION.username', $user_email);
                    $f3->set('SESSION.timestamp', time());
                    if (!empty($rememberme) && $rememberme == true) {
                        $f3->set('SESSION.rememberme', true);
                    }
                    $current_ip = $f3->get('CLIENT.ip');
                    $login_alerts = false;
                    $login_count = $users->login_count;
                    $users->last_online = time();
                    $users->login_count = $login_count+1;
                    if($current_ip !== $users->ip_address){
                      $login_alerts = $users->login_alerts == 1;
                      $users->ip_address = $current_ip;
                    }
                    $users->save();
                    if($login_alerts){
                      $mail = new Mail;
                      $mail->sendEmail($user_email, 'New login on '.$f3->get('SITE.name'), 'loginAlert', ['ip' => $f3->get('CLIENT.ip')]);
                    }

                    $message = $f3->get('SITE.base_url') . '/' . $f3->get('SITE.uri_backend');
                } else {
                    $response->json('error', '2FA authentication failed. Please, try again.');
                    exit;
                }
                break;
        }

        $response->json('success', $message);
    } catch (Exception $e) {
        // Send error message if something went wrong
        $response->json('error', 'Unable to process your request. Error: ' . $e->getMessage());
    }

    exit;
}
