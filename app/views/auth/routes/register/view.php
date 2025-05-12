<?php

$f3->set('PAGE.title', 'Create an account');

$enable_register = $f3->get('SITE.enable_register');

if(!$enable_register){
  $f3->reroute($f3->get('SITE.uri_auth'));
  return false;
}
if (empty($f3->get('SESSION.token'))) {
    $csrf = md5(uniqid(mt_rand(), true));
    $f3->set('SESSION.token', $csrf);
}
$f3->set('TOKEN', $f3->get('SESSION.token'));

if (isset($_GET['validate']) && $_SERVER['REQUEST_METHOD'] === "POST") {
    try {

        $response = new Response;
        $crypt = new Crypt; 
        $twofactor = new TwoFactor;

        $user_email = isset($_POST['username']) ? filter_var(strtolower($_POST['username']), FILTER_SANITIZE_EMAIL) :$f3->get('POST.username');
        $user_pass = isset($_POST['crypt']) ? base64_decode($_POST['crypt']) : $f3->get('POST.crypt');
        $user_displayName = isset($_POST['displayName']) ? htmlspecialchars($_POST['displayName']) : $f3->get('POST.displayName');
        $token = isset($_POST['token']) ? htmlspecialchars($_POST['token']) : $f3->get('POST.token');

        if(empty($user_email) || empty($user_pass) || empty($user_displayName) || empty($token)){
          $response->json('error', 'Missing information. Please, try again later');
          exit;
        }   

        // Validate CSRF token
        $csrf = $f3->get('SESSION.token');
        if (!$token || $token != $csrf) {
            $response->json('error', 'CSRF token mismatch error. Please reload your browser and try again.');
            exit;
        }
        $passwordPattern = '/^(?=.*[a-z])(?=.*[\d!@#$%^&*A-Z]).{8,}$/';
        if (!preg_match($passwordPattern, $user_pass)) {
            $response->json('error', 'Password does not meet criteria.');
            exit;
        }

        if(!filter_var($user_email, FILTER_VALIDATE_EMAIL)){
          $response->json('error', 'Invalid email address. Please enter a different email and try again.');
          exit;
        }

        // Prepare the database query (Users)
        global $db;
        $users = new DB\SQL\Mapper($db, 'users');
        $users->load(array('email=?', $user_email));
        if (!$users->dry()) {
            $response->json('error', $user_email . ' already exists in our records.');
            exit;
        }
 
        $d = time();
        $rand = $crypt->generateRandomString(3);
        $user_id = 'us_' . $d . $rand;

        $users->reset();
        $users->user_id = $user_id;
        $users->email = $user_email;
        $users->displayName = $user_displayName;
        $users->crypt = $crypt->generate($user_pass); 
        $users->is_admin = 0;
        $users->confirmed = 0;
        $users->status = 1;
        $users->twofactor = 0;
        $users->twofactor_sk = $twofactor->generateSecretKey();
        $users->created_at = time();
        $users->save();

        $f3->set('SESSION.loggedin', true);
        $f3->set('SESSION.username', $user_email);
        $f3->set('SESSION.timestamp', time()); 
        $f3->set('SESSION.rememberme', false);

        $response->json('success', 'Your account was successfully created.');

    } catch (Exception $e) {
        // Send error message if something went wrong
        $response->json('error', 'Unable to process your request. Error: ' . $e->getMessage());
    }

    exit;
}
