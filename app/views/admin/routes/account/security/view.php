<?php

$f3->set('PAGE.title', 'Security');
$f3->set('PAGE.slug', 'account');
$f3->set('breadcrumbs', [
    [
        "name" => "Account",
        "slug" => "account"
    ],
    [
        "name" => "Security",
        "slug" => "account"
    ]
]);

global $db;

$twofactor = new TwoFactor;
$response = new Response;

$csrf = (isset($_POST['token']) ? htmlspecialchars_decode($_POST['token']) : null);

$authnRegister = isset($_GET['authn-register']) && $_SERVER['REQUEST_METHOD'] === "POST" ? true : false;
$authnRemove = isset($_GET['authn-remove']) && $_SERVER['REQUEST_METHOD'] === "POST" ? true : false;
$twoFactorRegister = isset($_GET['twofactor-register']) && $_SERVER['REQUEST_METHOD'] === "POST" ? true : false;
$twoFactorRemove = isset($_GET['twofactor-remove']) && $_SERVER['REQUEST_METHOD'] === "POST" ? true : false;
$pinRegister = isset($_GET['pin-register']) && $_SERVER['REQUEST_METHOD'] === "POST" ? true : false;
$pinRemove = isset($_GET['pin-remove']) && $_SERVER['REQUEST_METHOD'] === "POST" ? true : false;
$loginAlerts = isset($_GET['login-alerts']) && $_SERVER['REQUEST_METHOD'] === "POST" ? true : false;
$updateEmail = isset($_GET['update-email']) && $_SERVER['REQUEST_METHOD'] === "POST" ? true : false;
$updatePassword = isset($_GET['update-password']) && $_SERVER['REQUEST_METHOD'] === "POST" ? true : false;

$user = null;

function loadUser($token){
    // Validate CSRF Token
    global $f3;
    global $db;

    $response = new Response;
    
    if ($token != $f3->get('SESSION.token')) {
        $response->json('error', 'CSRF token mismatch error. Please reload your browser and try again.');
        exit;
    }

    $userId = $f3->get('CXT')->user_id;
    $user = new DB\SQL\Mapper($db, 'users');
    $user->load(array('user_id=?', $userId));

    $currentPassword = isset($_POST['currentPassword']) ? base64_decode($_POST['currentPassword']) : null;
    if(!verifyCrypt($currentPassword, $user->crypt)){
        $response->json('error', "Password doesn't match with our records!");
        exit;
    }  
    
    return $user;
} 
function verifyCrypt($string, $hash){

    $crypt = new Crypt;
    if($crypt->verify($string, $hash)){
      return true;
    } else {
      return false;
    }
}

if ($updatePassword) {
    $user = loadUser($csrf);  

    $newPassword = isset($_POST['password']) ? base64_decode($_POST['password']) : null;
    $crypt = new Crypt;
    if (!empty($newPassword)) {
        $user->crypt = $crypt->generate($newPassword); 
        $user->save(); 
        $response->json('success', "Your information was successfully updated!");
    } else {
        $response->json('error', "There was an error with your request!");
    }

    exit;
} else 
if ($updateEmail) {

    $user = loadUser($csrf);
 
    $email= isset($_POST['email']) ? filter_var(strtolower($_POST['email']), FILTER_SANITIZE_EMAIL) : null;
 
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
      $response->json('error', 'Invalid email address. Please enter a different email and try again.');
      exit;
    }

    $query = new Query;
    $accountFound = $query->lookup('users', 'email=', $email);
    if($email === $user->email){
        $response->json('error', 'Email address is the same as the current email. Please enter a different email and try again.');
        exit;
    } else 
    if($accountFound){
        $response->json('error', 'Email address already exists in our system. Please enter a different email and try again.');
        exit;
    } 
     
    if (!empty($email)) {
        $user->email = $email;
        $user->confirmed = 0;
        $user->save();

        $response->json('success', "Your information was successfully updated!");
    } else {
        $response->json('error', "There was an error with your request!");
    }

    exit;
} else 
if ($authnRemove) {
    $user = loadUser($csrf);
    $userId = isset($_POST['userId']) ? htmlspecialchars_decode($_POST['userId']) : null;

    if ($user->passkey_id === $userId) {
        $user->passkey = 0;
        $user->passkey_id = '';
        $user->save();

        $response->json('success', "Passkey's was removed from your account!");
    } else {
        $response->json('error', "There was an error removing your passkey!");
    }

    exit;
} else 
if ($authnRegister) {
    $user = loadUser($csrf);
    $passkey = isset($_POST['passkey']) ? htmlspecialchars_decode($_POST['passkey']) : null;
    $userId = isset($_POST['userId']) ? htmlspecialchars_decode($_POST['userId']) : null;

    if ($passkey == 1) {
        $user->passkey = 1;
        $user->passkey_id = $userId;
        $user->save();

        $response->json('success', "Passkey's now active in your account!");
    } else {
        $response->json('error', "There was an error activating your passkey!");
    }
    exit;
} else 
if ($twoFactorRegister) {
    $user = loadUser($csrf);
    $twofactorkey = isset($_POST['twofactor_key']) ? base64_decode($_POST['twofactor_key']) : null;

    $window = 4; // 4 keys (respectively 2 minutes) past and future

    $valid = $twofactor->verifyKey($user->twofactor_sk, $twofactorkey, $window);

    if ($valid) {
        $user->twofactor = 1;
        $user->save();
        $response->json('success', "Authenticator's now active in your account!");
    } else {
        $response->json('error', "There was an error activating your authenticator!");
    }
    exit;
} else 
if ($twoFactorRemove) {
    $user = loadUser($csrf);
    $requestPass = true;

    if ($requestPass) {
        $user->twofactor = 0;
        $user->twofactor_sk = "";
        $user->save();

        $response->json('success', "Authenticator's was removed from your account!");
    } else {
        $response->json('error', "There was an error removing your authenticator!");
    }

    exit;
} else 
if ($pinRegister) {
    $user = loadUser($csrf);
    $requestPass = true;

    $pincode = isset($_POST['pin']) ? base64_decode($_POST['pin']) : null;

    if (!empty($pincode)) {
        $user->pin = 1;
        $user->pin_code = $pincode;
        $user->save();

        $response->json('success', "PIN Code's now active in your account!");
    } else {
        $response->json('error', "There was an error activating your PIN code!");
    }

    exit;
} else 
if ($pinRemove) {
    $user = loadUser($csrf);
    $requestPass = true;

    if ($requestPass) {
        $user->pin = 0;
        $user->pin_code = "";
        $user->save();

        $response->json('success', "PIN Code's was removed from your account!");
    } else {
        $response->json('error', "There was an error removing your PIN code!");
    }

    exit;
} else 
if ($loginAlerts) {
    $user = loadUser($csrf);
    $requestPass = true;

    $alertInput = isset($_POST['loginAlert']) ? htmlspecialchars_decode($_POST['loginAlert']) : null;

    $msg = "Login Alert's was removed from your account!";
    if ($alertInput == 1) {
        $msg = "Login Alert's now active in your account!";
    }

    if ($requestPass) {
        $user->login_alerts = $alertInput;
        $user->save();

        $response->json('success', $msg);
    } else {
        $response->json('error', "There was an error activating your Login Alerts!");
    }

    exit;
}
 
$userId = $f3->get('CXT')->user_id;
$user = new DB\SQL\Mapper($db, 'users');
$user->load(array('user_id=?', $userId));
if (!$user->dry()) {

    $secretKey = $user->twofactor_sk;
    if (empty($secretKey)) {
        $secretKey = $twofactor->generateSecretKey();
        $user->twofactor_sk = $secretKey;
        $user->save();
    }
    if (empty($user->twofactor)) {
        $qrCodeUrl = $twofactor->getQRCodeUrl($secretKey);
        $f3->set('qrCodeUrl', $qrCodeUrl);
    }

    $f3->set('TWOFACTOR.secret', $secretKey);
}
