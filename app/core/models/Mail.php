<?php

  class Mail extends \Prefab  {

    function sendEmail($address, $title, $messageType = 'default', $params = []) {
      global $f3;

      // Retrieve SMTP settings from configuration
      $host = $f3->get('SITE.smtp_host');
      $port = $f3->get('SITE.smtp_port');
      $scheme = $f3->get('SITE.smtp_scheme');
      $user = $f3->get('SITE.smtp_user');
      $pw = $f3->get('SITE.smtp_pass');

      try {
        // Initialize the SMTP connection
        $smtp = new SMTP($host, $port, $scheme, $user, $pw);
        $hash = uniqid(TRUE);
        $smtp->set('Errors-to', '<'.$user.'>');
        $smtp->set('From', $user);
        $smtp->set('To', $address);
        $smtp->set('Content-Type', 'multipart/alternative; boundary="'.$hash.'"');
        $smtp->set('Subject', $title);

        // Generate the email body
        $htmlBody = $this->generateEmailBody($messageType, $params);
        $plainTextBody = strip_tags($htmlBody); // Generate plain text from HTML

        // Prepare the email body with multipart content
        $eol = "\r\n";
        $body  = '--' . $hash . $eol;
        $body .= 'Content-Type: text/plain; charset=UTF-8' . $eol;
        $body .= $plainTextBody . $eol . $eol;
        $body .= '--' . $hash . $eol;
        $body .= 'Content-Type: text/html; charset=UTF-8' . $eol . $eol;
        $body .= $htmlBody . $eol;

        // Send the email
        $smtp->send($body, true);

      } catch (Exception $e) {
        // Log any errors or exceptions that occur
        error_log("Email sending failed: " . $e->getMessage());
        return false;
      }

      return true;
    }

    // Generate the entire email body based on the message type
    function generateEmailBody($messageType, $params = []) {
      $body = $this->body($params);  // Email header
      switch ($messageType) {
        case 'otpCode':
          $body .= $this->otpCode($params);
          break;
        case 'loginAlert':
          $body .= $this->loginAlert($params);
          break;
        case 'default':
          $body .= $this->defaultMsg($params);
          break;
      }
      $body .= $this->footer($params);  // Email footer
      return $body;
    }

    // General body structure with placeholders
    function body($params = []) {
      global $f3;
      $siteName = $f3->get('SITE.name');
      return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
              <html xmlns="http://www.w3.org/1999/xhtml">
              <head>
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <style>
                  /* General Styles */
                  body {
                    margin: 0;
                    padding: 0;
                    font-family: "Fira Sans", Helvetica, Arial, sans-serif;
                    background-color: #f4f4f4;
                    width: 100% !important;
                    -webkit-text-size-adjust: none;
                    -ms-text-size-adjust: none;
                  }
                  table {
                    border-spacing: 0;
                    width: 100%;
                  }
                  td {
                    padding: 0;
                  }
                  img {
                    border: 0;
                    -ms-interpolation-mode: bicubic;
                  }
                  a {
                    color: #1595E7;
                    text-decoration: none;
                  }
                  .verify-btn {
                    background-color: #1595E7 !important;
                    color: #f4f4f4 !important;
                  }
              
                  /* Responsive Styles */
                  @media only screen and (max-width: 600px) {
                    table[class="container"] {
                      width: 100% !important;
                    }
                    img[class="responsive"] {
                      width: 100% !important;
                      height: auto !important;
                    }
                  }
                </style>
              </head>
              <body>
              <table width="100%" bgcolor="#f4f4f4">
                <tr>
                  <td align="center">
                    <table class="container" width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; padding: 20px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);">
                      <!-- Header Section -->
                      <tr>
                        <td align="center" style="padding: 34px 40px 20px;">
                          <a href="'.$f3->get('SITE.base_url').'" style="font-size: 16px; font-weight: bold; color: #9B9B9B;">'.$siteName.'</a>
                        </td>
                      </tr>
              
                      <!-- Email Content -->
                      <tr>
                        <td style="padding: 10px; text-align: center;">
     ';
    }


    // Footer section of the email
    function footer($params = []) {
      global $f3;
      $footer = isset($params['footer']) ? $params['footer'] : '';
      return '
            </td>
        </tr>
        <!-- Footer Section -->
        <tr>
          <td style="padding: 31px 40px; background-color: #ffffff; text-align: center; color: #9B9B9B; font-size: 14px;">
            <span>'.$footer.'</span>
            <div style="padding-top: 20px;">
              <a href="http://example.com/" style="margin-right: 15px;"><img src="'.$f3->get('SITE.base_url').'public/assets/img/facebook-icon.png" alt="Facebook" width="16" height="16"></a>
              <a href="http://example.com/" style="margin-right: 15px;"><img src="'.$f3->get('SITE.base_url').'public/assets/img/twitter-icon.png" alt="Twitter" width="16" height="16"></a>
              <a href="http://example.com/"><img src="'.$f3->get('SITE.base_url').'public/assets/img/instagram-icon.png" alt="Instagram" width="16" height="16"></a>
            </div>
            <div style="padding-top: 10px;">
              <a href="http://example.com/" style="color: #1595E7;">Manage Preferences</a>
              &nbsp;&nbsp;
              <a href="http://example.com/" style="color: #1595E7;">Unsubscribe</a>
            </div>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>
  ';
    }

    // Default message structure
    function defaultMsg($params = []) {
      $message = isset($params['message']) ? htmlspecialchars_decode($params['message']) : '';
      return '<div class="message">' . $message . '</div>';
    }

    // OTP-specific message structure
    function otpCode($params = []) {
      $otpCode = isset($params['otp']) ? $params['otp'] : '000000';  // Default OTP if not provided
      return '<div class="otp-title" style="margin-bottom: 10px; font-size: x-small; text-transform: uppercase; font-weight: bold; color: #3aa055;">Verification Code</div>
            <span class="otp-code" style="margin-bottom: 20px; font-size: 24px; font-weight: bold; color: #1595E7;">
            ' . $otpCode . '
            </span>
            <div class="message" style="margin: 20px 0;">You\'re now performing a one-time verification. Your code will be valid for 15 minutes.</div>
            <a href="http://example.com/" class="verify-btn">Verify manually</a>
            <div class="disclaimer" style="padding-top: 10px; font-size: small; color: #9B9B9B;">*Don\'t forward this email or verification code to anyone</div>
      ';
    }

    // OTP-specific message structure
    function loginAlert($params = []) {
      global $f3;
      $visitorIp = isset($params['ip']) ? $params['ip'] : ':0';  // Default OTP if not provided
      return '<div class="message" style="margin: 20px 0;">
              We noticed a login to your account from a new device. Was this you?
              <p>
               IP address: '.$visitorIp.'
              </p>
              <p style="font-weight: bolder">If this was you</p>
              <span>You can ignore this message. There\'s no need to take any action.</span>              
              <p style="font-weight: bolder">If this wasn\'t you</p> 
              <span>Complete these steps now to protect your account:</span>
              <br><br>
              <a href="'.$f3->get('SITE.base_url').'/'.$f3->get('SITE.uri_auth').'/forgot">Change your password</a>
              </div>
      ';
    }

}
