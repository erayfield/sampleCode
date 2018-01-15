<?php

/**
 * class ECDC
 * encrypt/decrypt functionality for xml api
 * Example of use:
 * instantiate the object
 * $encodeDecode = new ECDC();
 * to encode
 * $encryptedString = $encodeDecode->getEncrypt($theStringToEncrypt);
 * if(is_null($encryptedString)) {failed;}
 * to decode
 * $decodedString = $encodeDecode->getDecrypt($theStringToDecrypt);
 *  if(is_null($decodedString)) {failed;}
 */

class ECDC
{
    /**
     * @var string $key string for encryption/decryption
     */
    private $aes_key;
    public $retVal;

    /**
     * gets encryption aes_key
     * @return string - encryption aes_key
     */
    private function getKey()
    {

        include_once('../configuration/config.php');
        $retStr = null;
        return AES_KEY;
    }

    /**
     * this public function encrypts the passed in string
     * @param string $encryptThisString plain text to encrypt
     * @return string empty or containing encrypted passed in string     
	 **/
    public function getEncrypt($encryptThisString)
    {
        if(!empty($encryptThisString))
        {
           return $this->enc(trim($encryptThisString));
        }
        return '';
    }

    /**
     * this public function decrypts the passed in string
     * @param string $decryptThisString the encrypted string to decrypt
     * @return string empty or containing decrypted passed in string
     */
    public function getDecrypt($decryptThisString)
    {
        if(!empty($decryptThisString))
        {
            $sam= $this->dec($decryptThisString);
            return $sam;
        }
        return '';
    }
    /**
     * construct for class ECDC, sets up the aes_key
     */
    public function __construct()
    {
        $this->aes_key = $this->getKey();
    }

    /**
     * destructor for ECDC, will unset all set variables
     */
    public function __destruct()
    {
        foreach ($this as $key => $value)
        {
            unset($this->$key);
        }
    }


    /**
     * private encodes string
     * @param string $data data to encode
     * @return string encoded string or empty string if passed information is not set
     */
    private function enc($data)
    {

       if(empty($data))
            return '';
       $cipher = MCRYPT_RIJNDAEL_128;
       $mode = MCRYPT_MODE_CBC;

       $key_hash = md5($this->getKey());
       $key_size = mcrypt_get_key_size($cipher, $mode);
       $key_size = 16;
       $iv_size = mcrypt_get_iv_size($cipher, $mode);
       $AES_KEY = substr($key_hash, 0, $key_size);
       $iv_size = mcrypt_get_iv_size($cipher, $mode);
       $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

       //Perform decryption
       $ctext = mcrypt_encrypt($cipher, $AES_KEY, $data, $mode, $iv);
       $ctext = $iv.$ctext;

       // Perform URL encoding on a base64-encoded string
       $ctext = urlencode(base64_encode($ctext));
       return $ctext;
    }

    /**
     * private decodes string
     * @param string $str what to decode
     * @return string empty if no value able to be constructed
     */
    private function dec($str)
    {       
        if(empty($str))
            return '';
       $cipher = MCRYPT_RIJNDAEL_128;
       $mode = MCRYPT_MODE_CBC;
     
       $key_hash = md5($this->getKey());
       $key_size = mcrypt_get_key_size($cipher, $mode);
       $key_size = 16;
       $iv_size = mcrypt_get_iv_size($cipher, $mode);
       $AES_KEY = substr($key_hash, 0, $key_size);
       $iv_size = mcrypt_get_iv_size($cipher, $mode);
       $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

       //Decoding: both urldecode and base64_decode
       $ctext = base64_decode(urldecode($str));

       //Perform decryption
       $ptext = mcrypt_decrypt($cipher, $AES_KEY, $ctext, $mode, $iv);
       $ptext = substr($ptext,$iv_size);

       return str_replace(chr(0),"",$ptext);
    }

    function cleanupRequestArray()
    {
        foreach($_REQUEST as $k=>$v)
        {
            $_REQUEST[$k] = trim($v);
        }
    }
}
