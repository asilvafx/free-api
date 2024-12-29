<?php

class WebAuthn
{

    public function __construct() {}

    public function Options()
    {
        global $f3;
        $response = new Response;

        // Set appropriate headers
        $f3->set('HEADERS', [
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => '*', // Adjust as necessary
        ]);

        $body = json_decode(file_get_contents('php://input'), true);

        // Validate CSRF Token
        // Example: $this->validateCsrfToken();

        // Fetch username from the request
        $username = (isset($body['username']) ? $body['username'] : $f3->get('POST.username'));

        // Generate display name based on username
        $displayName = null;
        if (!empty($username) && str_contains($username, '@')) {
            $displayName = substr($username, 0, strpos($username, '@'));
        }

        $user = [
            'id' => $this->base64url_encode($username), // Base64url encode username
            'name' => $username,
            'displayName' => $displayName
        ];

        $rp = ['name' => 'MyWebAuthnApp'];

        // Generate attestation options and return the response
        $options = $this->getAttestationOptions($user, $rp);
        echo json_encode($options);

        exit; // Ensure we stop execution after sending the response
    }

    private function getAttestationOptions($user, $rp)
    {

        return [
            'status' => 'ok',
            'rp' => $rp,
            'user' => $user,
            'challenge' => $this->base64url_encode(random_bytes(32)), // Generate a challenge
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => -7] // Example algorithm
            ],
            'excludeCredentials' => [], // Add any credentials to exclude here if necessary
        ];
    }

    public function Result()
    {
        global $f3;
        // Handle the result of the attestation here
        $data = json_decode($f3->get('BODY'), true);

        // Validate the incoming data
        if (isset($data['credentialId'], $data['publicKey'], $data['email'])) {
            $email = $data['email'];
            $credentialId = $data['credentialId'];
            $publicKey = $this->base64url_decode($data['publicKey']); // Decode the public key

            // Save the credential in the database
            if ($this->saveCredential($email, $credentialId, $publicKey)) {
                echo json_encode(['status' => 'success', 'message' => 'Credential saved successfully.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to save credential.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request data.']);
        }
    }

    private function saveCredential($email, $credentialId, $publicKey)
    {
        global $f3;
        global $db;

        $user = new DB\SQL\Mapper($db, 'users');
        $user->load(['email=?', $email]);
        if ($user->dry()) {
            return false; // User not found
        }

        $user->passkey = 1;
        $user->passkey_id = $credentialId;
        $user->passkey_pk = $publicKey;
        $user->passkey_sign_count += 1; // Increment correctly

        return $user->save(); // Return true if the insert was successful
    }

    public function base64url_encode($data)
    {
        if (empty($data)) {
            $data = random_bytes(32);
        }
        $b64 = base64_encode($data);
        return rtrim(strtr($b64, '+/', '-_'), '=');
    }

    public function base64url_decode($data, $strict = false)
    {
        $b64 = strtr($data, '-_', '+/');
        return base64_decode($b64, $strict);
    }
}
