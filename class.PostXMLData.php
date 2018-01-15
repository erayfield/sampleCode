<?php
/**
 * File: class.PostXMLData.php
 * use to post to the API of client
 */
class PostXMLData
{
    public $postUrl;
    public $postString;

    /**
     * post xml data to api for client
     */
    public function __construct()
    {
    }

    /**
    * destructor for PostXMLData, will unset all set variables
    */
    public function __destruct()
    {
        foreach ($this as $key => $value)
        {
            unset($this->$key);
        }
    }
    public function getPostUrl()
    {
        return $this->postUrl;
    }

    public function getPostString()
    {
        return $this->postString;
    }

    /**
     * @param string $val the string containing the url to post data to
     */
    public function setPostUrl($val)
    {
        $this->postUrl = $val;
    }

    /**
     * @param string $val the string containing the string to post
     */
    public function setPostString($val)
    {
        $this->postString = $val;
    }

    /**
     * @return mixed response from curl execution
     */
    public function postVals()
    {
       // create a new cURL resource
        $daURL = $this->getPostUrl();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$daURL);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER,array('Content-Type:  application/x-www-form-urlencoded'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->getPostString());
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $retVal =curl_exec($ch);
         return $retVal;
    }
    /**
     * @return mixed response from curl execution
     */
    public function postValsSubscriberLoad()
    {
       // create a new cURL resource
        $daURL = 'https://this_is_client_confidential';
        $post_data = array('data'=>$this->getPostString());
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$daURL);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER,array('Content-Type:  application/x-www-form-urlencoded'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->getPostString());
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $retVal =curl_exec($ch);
         return $retVal;
    }
}
