<?php

class Crypt
{
    // Use the same secret key as JavaScript
    private $secretKey = "your-default-secret-key";

    function generate($string)
    {
        $hash = password_hash(
            $string,
            PASSWORD_DEFAULT
        );
        return $hash;
    }

    function verify($string, $hash)
    {
        return password_verify($string, $hash);
    }

    function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    /**
     * Decrypt data from a CryptoJS json encoding string
     * Compatible with CryptoJS AES encryption
     *
     * @param string $jsonString
     * @return mixed|false
     */
    function JsDecrypt($jsonString)
    {
        try {
            $jsondata = json_decode($jsonString, true);

            if (!$jsondata || !isset($jsondata["s"]) || !isset($jsondata["ct"]) || !isset($jsondata["iv"])) {
                error_log("Invalid JSON structure for decryption");
                return false;
            }

            $salt = hex2bin($jsondata["s"]);
            $ct = base64_decode($jsondata["ct"]);
            $iv = hex2bin($jsondata["iv"]);

            // Use CryptoJS-compatible key derivation
            $key = $this->evpKDF($this->secretKey, $salt, 32, 16);

            $data = openssl_decrypt($ct, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

            if ($data === false) {
                error_log("OpenSSL decryption failed");
                return false;
            }

            $result = json_decode($data, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("JSON decode error: " . json_last_error_msg());
                return false;
            }

            return $result;
        } catch (Exception $e) {
            error_log("Decryption exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Encrypt value to a cryptojs compatible json encoding string
     * Compatible with CryptoJS AES encryption
     *
     * @param mixed $value
     * @return string|false
     */
    function JsEncrypt($value)
    {
        try {
            $salt = openssl_random_pseudo_bytes(8);

            // Use CryptoJS-compatible key derivation
            $keyAndIV = $this->evpKDF($this->secretKey, $salt, 32 + 16, 16);
            $key = substr($keyAndIV, 0, 32);
            $iv = substr($keyAndIV, 32, 16);

            $encrypted_data = openssl_encrypt(
                json_encode($value),
                'aes-256-cbc',
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($encrypted_data === false) {
                error_log("OpenSSL encryption failed");
                return false;
            }

            $data = array(
                "ct" => base64_encode($encrypted_data),
                "iv" => bin2hex($iv),
                "s" => bin2hex($salt)
            );

            return json_encode($data);
        } catch (Exception $e) {
            error_log("Encryption exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * CryptoJS-compatible key derivation function
     * Equivalent to CryptoJS.evpKDF
     *
     * @param string $password
     * @param string $salt
     * @param int $keySize (in bytes)
     * @param int $ivSize (in bytes)
     * @return string
     */
    private function evpKDF($password, $salt, $keySize, $ivSize)
    {
        $targetKeySize = $keySize + $ivSize;
        $derivedBytes = '';
        $numberOfDerivedWords = 0;
        $block = null;
        $hasher = hash_init('md5');

        while ($numberOfDerivedWords < ($targetKeySize / 4)) {
            if ($block != null) {
                hash_update($hasher, $block);
            }

            hash_update($hasher, $password);
            hash_update($hasher, $salt);
            $block = hash_final($hasher, true);
            $hasher = hash_init('md5');

            // Iterations = 1 for CryptoJS compatibility

            $derivedBytes .= $block;
            $numberOfDerivedWords += 4;
        }

        return substr($derivedBytes, 0, $targetKeySize);
    }
}