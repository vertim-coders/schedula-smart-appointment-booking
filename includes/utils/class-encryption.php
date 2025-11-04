<?php
/**
 * Shared Encryption Utility.
 * Provides static methods for AES-256-CBC encryption and decryption.
 *
 * @package SCHESAB\Utils
 */

namespace SCHESAB\Utils;

if (!defined('ABSPATH')) {
    exit;
}

class SCHESAB_Encryption
{
    /**
     * The cipher method used for encryption.
     *
     * @var string
     */
    private static $cipher_method = 'aes-256-cbc';

    /**
     * Encrypts data using AES-256-CBC.
     *
     * @param string $data The data to encrypt.
     * @return string The encrypted and base64 encoded data, or an empty string if input is empty.
     */
    public static function encrypt_data($data) {
        if (empty($data)) {
            return '';
        }

        $key = substr(hash('sha256', wp_salt()), 0, 32); // Use WordPress salt as base key
        $iv_length = openssl_cipher_iv_length(self::$cipher_method);
        $iv = openssl_random_pseudo_bytes($iv_length);
        $encrypted = openssl_encrypt($data, self::$cipher_method, $key, 0, $iv);
        
        // Return data in format "encrypted_data::iv"
        return base64_encode($encrypted . '::' . $iv);
    }

    /**
     * Decrypts data encrypted by encrypt_data.
     *
     * @param string $data The base64 encoded "encrypted_data::iv" string.
     * @return string The decrypted data, or an empty string if input is empty or invalid.
     */
    public static function decrypt_data($data) {
        if (empty($data)) {
            return '';
        }

        $key = substr(hash('sha256', wp_salt()), 0, 32);
        list($encrypted_data, $iv) = array_pad(explode('::', base64_decode($data), 2), 2, null);

        if (is_null($iv) || is_null($encrypted_data)) {
            return ''; // Invalid format, cannot decrypt
        }

        return openssl_decrypt($encrypted_data, self::$cipher_method, $key, 0, $iv);
    }
}
