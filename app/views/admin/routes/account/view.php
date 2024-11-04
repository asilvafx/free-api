<?php

$f3->set('PAGE.title', 'My Profile');
$f3->set('PAGE.slug', 'account');
$f3->set('breadcrumbs', [
    [
        "name" => "Account",
        "slug" => "account"
    ]
]);
global $db;
$response = new Response;
$utils = new Utils;
$user = null;
$csrf = (isset($_POST['token']) ? htmlspecialchars_decode($_POST['token']) : null);
$upload = isset($_GET['upload-avatar']) && $_SERVER['REQUEST_METHOD'] === "POST" ? true : false;
$updateProfile = isset($_GET['update-profile']) && $_SERVER['REQUEST_METHOD'] === "POST" ? true : false;
$updateBio = isset($_GET['update-bio']) && $_SERVER['REQUEST_METHOD'] === "POST" ? true : false;
$sendOtp = isset($_GET['send-otp']) && $_SERVER['REQUEST_METHOD'] === "POST" ? true : false;
$verifyOtp = isset($_GET['verify-otp']) && $_SERVER['REQUEST_METHOD'] === "POST" ? true : false;

$user = null;

if ($upload || $updateProfile || $updateBio || $sendOtp || $verifyOtp) {
    // Validate CSRF Token

    if ($csrf != $f3->get('SESSION.token')) {
        $response->json('error', 'CSRF token mismatch error. Please reload your browser and try again.');
        exit;
    }

    $userId = $f3->get('CXT')->user_id;
    $user = new DB\SQL\Mapper($db, 'users');
    $user->load(array('user_id=?', $userId));
}

if ($updateBio) {

    $bio = !empty($f3->get('POST.bio')) ? htmlspecialchars_decode($f3->get('POST.bio')) : $f3->get('USER.bio');

    $user->bio = $bio;
    $user->save();

    $response->json('success', 'Profile bio was successfully updated.');
    exit;
}

if ($updateProfile) {

    $displayName = !empty($f3->get('POST.displayName')) ? htmlspecialchars_decode($f3->get('POST.displayName')) : $f3->get('CXT')->displayName;
    $phone = !empty($f3->get('POST.phone')) ? htmlspecialchars_decode($f3->get('POST.phone')) : $f3->get('CXT')->phone;

    $user->displayName = $displayName;
    $user->phone = $phone;
    $user->save();

    $response->json('success', 'Profile information was successfully updated.');
    exit;
}

if ($upload) {

    // Check if file is uploaded
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $response->json('error', 'No file uploaded or there was an error with the upload.');
        exit;
    }

    $file = $_FILES['file'];
    $utils = new Utils;
    $uploadedFile = $utils->uploadFile($file, 'public/uploads/');

    if (!$uploadedFile) {
        $response->json('error', 'Failed to upload file.');
    } else {

        $user->avatar = $uploadedFile;
        $user->save();

        $response->json('success', 'Profile avatar was successfully updated.');
    }
    exit;
}

if($sendOtp){
    $nonce = random_int(100000, 999999);  
    $user->nonce = $nonce; 
    $user->save();

    $user_email = $user->email; 
    $mail = new Mail;

    if ( $mail->sendEmail($user_email, 'Verification Code', 'otpCode', ['otp' => $nonce])) {
        $response->json('success', 'OTP code was successfully sent.');
    } else {
        $response->json('error', 'There was an error processing your request. Please, try again later.');
    }
    exit;
}
if($verifyOtp){
    $user_otp = !empty($f3->get('POST.otp')) ? htmlspecialchars_decode($f3->get('POST.otp')) : null;
    $otp_secret = $user->nonce;
    if($user_otp != $otp_secret){ 
       $response->json('error', 'Invalid OTP code.');
    } else {
       $user->confirmed = 1;
       $user->save();
       $response->json('success', 'OTP was confirmed successfully!');  
    }

    exit;
}
