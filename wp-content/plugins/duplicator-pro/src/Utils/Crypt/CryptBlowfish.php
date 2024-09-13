<?php

/**
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

namespace Duplicator\Utils\Crypt;

use DUP_PRO_Log;
use Duplicator\Libs\Snap\SnapLog;
use Duplicator\Libs\Snap\SnapUtil;
use Duplicator\Libs\Snap\SnapWP;
use Duplicator\Libs\WpConfig\WPConfigTransformer;
use Error;
use Exception;
use VendorDuplicator\phpseclib3\Crypt\Blowfish;

class CryptBlowfish implements CryptInterface
{
    const AUTH_DEFINE_NAME_OLD = 'DUP_SECURE_KEY'; // OLD define name
    const AUTH_DEFINE_NAME     = 'DUPLICATOR_AUTH_KEY';
    const AUTO_SALT_LEN        = 32;

    /** @var string */
    protected static $tempDefinedKey = null;

    /**
     * Check if encryption is available
     *
     * @return bool
     */
    public static function isEncryptAvailable()
    {
        // Check only once to avoid too much trace
        static $check = null;
        if ($check === null) {
            try {
                DUP_PRO_Log::trace('Encryption check');
                $stringToEncrypt = 'Check Encryption';
                $test            = self::encrypt($stringToEncrypt);
                $result          = self::decrypt($test);
                $check           = ($result === $stringToEncrypt);
            } catch (Exception $e) {
                $check = false;
            } catch (Error $e) {
                $check = false;
            }

            if ($check === false) {
                DUP_PRO_Log::trace('Encryption is not available, check failed');
            } else {
                DUP_PRO_Log::trace('Encryption is available, check ok');
            }
        }
        return $check ;
    }

    /**
     * Create wp-config dup secure key
     *
     * @param bool $overwrite  if it is false and the key already exists it is not modified
     * @param bool $fromLegacy if true save legacy key
     *
     * @return bool
     */
    public static function createWpConfigSecureKey($overwrite = false, $fromLegacy = false)
    {
        $result = false;

        try {
            if (($wpConfig = SnapWP::getWPConfigPath()) == false) {
                return false;
            }

            if ($fromLegacy) {
                $authVal = self::getLegacyKey();
            } else {
                $authVal = SnapUtil::generatePassword(64, true, true);
            }

            $transformer = new WPConfigTransformer($wpConfig);

            if ($transformer->exists('constant', self::AUTH_DEFINE_NAME_OLD) && !$transformer->exists('constant', self::AUTH_DEFINE_NAME)) {
                $authVal = $transformer->getValue('constant', self::AUTH_DEFINE_NAME_OLD);
                if (!is_writeable($wpConfig)) {
                    throw new Exception('wp-config isn\'t writeable');
                }
                $result = $transformer->update('constant', self::AUTH_DEFINE_NAME, $authVal);
            } elseif ($transformer->exists('constant', self::AUTH_DEFINE_NAME)) {
                if ($overwrite) {
                    if (!is_writeable($wpConfig)) {
                        throw new Exception('wp-config isn\'t writeable');
                    }
                    $result = $transformer->update('constant', self::AUTH_DEFINE_NAME, $authVal);
                }
            } else {
                if (!is_writeable($wpConfig)) {
                    throw new Exception('wp-config isn\'t writeable');
                }
                $result = $transformer->add('constant', self::AUTH_DEFINE_NAME, $authVal);
            }

            if ($result) {
                self::$tempDefinedKey = $authVal;
            }

            // Remove old constant if new one is prepared/exists
            if ($transformer->exists('constant', self::AUTH_DEFINE_NAME_OLD) && $transformer->exists('constant', self::AUTH_DEFINE_NAME)) {
                if (!is_writeable($wpConfig)) {
                    throw new Exception('Can\'t delete old constant ' . self::AUTH_DEFINE_NAME_OLD . ' from wp-config, error: wp-config isn\'t writeable');
                }
                $transformer->remove('constant', self::AUTH_DEFINE_NAME_OLD);
            }
        } catch (Exception $e) {
            DUP_PRO_Log::trace('Can\'t create wp-config secure key, error: ' . $e->getMessage());
        } catch (Error $e) {
            DUP_PRO_Log::trace('Can\'t create wp-config secure key, error: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Remove secure key in wp config is exists
     *
     * @return bool
     */
    public static function removeWpConfigSecureKey()
    {
        $result = false;

        try {
            if (($wpConfig = SnapWP::getWPConfigPath()) == false) {
                return false;
            }

            $transformer = new WPConfigTransformer($wpConfig);

            if ($transformer->exists('constant', self::AUTH_DEFINE_NAME)) {
                if (!is_writeable($wpConfig)) {
                    throw new Exception('wp-config isn\'t writeable');
                }

                $result = $transformer->remove('constant', self::AUTH_DEFINE_NAME);
            }

            if (!is_writeable($wpConfig)) {
                throw new Exception('wp-config isn\'t writeable');
            }
        } catch (Exception $e) {
            DUP_PRO_Log::trace('Can remove wp-config secure key, error: ' . $e->getMessage());
        } catch (Error $e) {
            DUP_PRO_Log::trace('Can remove wp-config secure key, error: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Get default key encryption
     *
     * @return string
     */
    public static function getDefaultKey()
    {
        if (self::$tempDefinedKey !== null) {
            return self::$tempDefinedKey;
        } elseif (strlen(constant(self::AUTH_DEFINE_NAME)) > 0) {
            return constant(self::AUTH_DEFINE_NAME);
        } elseif (defined(self::AUTH_DEFINE_NAME_OLD) && strlen(constant(self::AUTH_DEFINE_NAME_OLD)) > 0) {
            return constant(self::AUTH_DEFINE_NAME_OLD);
        } else {
            return self::getLegacyKey();
        }
    }


    /**
     * Get legacy key encryption
     *
     * @return string
     */
    protected static function getLegacyKey()
    {
        $auth_key  = defined('AUTH_KEY') ? AUTH_KEY : 'atk';
        $auth_key .= defined('DB_HOST') ? DB_HOST : 'dbh';
        $auth_key .= defined('DB_NAME') ? DB_NAME : 'dbn';
        $auth_key .= defined('DB_USER') ? DB_USER : 'dbu';
        return hash('md5', $auth_key);
    }

    /**
     * Return encrypt string
     *
     * @param string $string  string to encrypt
     * @param string $key     hash key
     * @param bool   $addSalt if true add HASH salt to string
     *
     * @return string renturn empty string if error
     */
    public static function encrypt($string, $key = null, $addSalt = false)
    {
        try {
            if ($key == null) {
                $key = self::getDefaultKey();
            }

            if ($addSalt) {
                $string = SnapUtil::generatePassword(self::AUTO_SALT_LEN, true, true) . $string . SnapUtil::generatePassword(self::AUTO_SALT_LEN, true, true);
            }

            $crypt = new Blowfish('ecb');
            $crypt->setKey($key);
            $crypt->disablePadding();
            $expectedLength = 8 * (int) ceil(strlen($string) / 8);
            $string         = str_pad($string, $expectedLength, "\0");


            $encrypted_value = $crypt->encrypt($string);
        } catch (Exception $e) {
            DUP_PRO_Log::traceException($e, "Error encrypting string");
            return '';
        } catch (Error $e) {
            DUP_PRO_Log::traceException($e, "Error encrypting string");
            return '';
        }

        return base64_encode($encrypted_value);
    }

    /**
     * Encrypt if encryption is available or return the original string
     *
     * @param string $string  string to encrypt
     * @param string $key     hash key
     * @param bool   $addSalt if true add HASH salt to string
     *
     * @return string renturn empty string if error
     */
    public static function encryptIfAvaiable($string, $key = null, $addSalt = false)
    {
        if (self::isEncryptAvailable()) {
            return self::encrypt($string, $key, $addSalt);
        }
        return $string;
    }

    /**
     * Return decrypt string
     *
     * @param string $string     string to decrypt
     * @param string $key        hash key
     * @param bool   $removeSalt if true remove HASH salt from string
     *
     * @return string renturn empty string if error
     */
    public static function decrypt($string, $key = null, $removeSalt = false)
    {
        try {
            $string = (string) $string;
            if (strlen($string) === 0) {
                return '';
            }

            if ($key == null) {
                $key = self::getDefaultKey();
            }

            $crypt = new Blowfish('ecb');
            $crypt->disablePadding();
            $crypt->setKey($key);

            $orig   = $string;
            $string = base64_decode($string);
            if ($string === false) {
                DUP_PRO_Log::trace("Bad encrypted string base64 encoded for " . $orig . "\n" . SnapLog::getCurrentbacktrace());
                return '';
            }


            $decrypted_value = $crypt->decrypt($string);
            $decrypted_value = str_replace("\0", '', $decrypted_value);

            if ($removeSalt) {
                $decrypted_value = substr($decrypted_value, self::AUTO_SALT_LEN, (strlen($decrypted_value) - (self::AUTO_SALT_LEN * 2)));
            }
        } catch (Exception $e) {
            DUP_PRO_Log::traceException($e, "Error decrypting string");
            return '';
        } catch (Error $e) {
            DUP_PRO_Log::traceException($e, "Error decrypting string");
            return '';
        }

        return $decrypted_value;
    }

    /**
     * Decrypt if encryption is available or return the original string
     *
     * @param string $string     string to decrypt
     * @param string $key        hash key
     * @param bool   $removeSalt if true remove HASH salt from string
     *
     * @return string renturn empty string if error
     */
    public static function decryptIfAvaiable($string, $key = null, $removeSalt = false)
    {
        if (self::isEncryptAvailable()) {
            return self::decrypt($string, $key, $removeSalt);
        }
        return $string;
    }
}
