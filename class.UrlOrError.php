<?php
 include_once('class.ECDC.php');

    /**
     * Name: class.UrlOrError.php
     * Created by : ERayfield
     * Created On: 11/28/12 at 8:40 AM
     * deals with xml format
     * <APIResponse>
     *   <Response>
     *     <EncryptedData></EncryptedData>ResponseTraceNumberSubscriberNumberStatusErrorTextErrorType
     *     <TraceNumber></TraceNumber>
     *     <SubscriberNumber>testauth1</SubscriberNumber>
     *     <Status>Success</Status>
     *     <ErrorText></ErrorText>
     *     <ErrorType>0</ErrorType>
     *     <Alert>
     *           <Url>http://someUrlHere</Url>
     *     <Alert>
     *  </Response>
     * </APIResponse
     */
    class UrlOrError
    {
        public $xmlString;
        public $nodeName;
        /**
         * post xml data to CSID
         */
        public function __construct($result, $name)
        {
            //rem, still encrypted and url encoded at this time
            $this->xmlString = urldecode($result);
            $this->nodeName = $name;
        }

        /**
        * destructor  will unset all set variables
        */
        public function __destruct()
        {
            foreach ($this as $key => $value)
            {
                unset($this->$key);
            }
        }

        /**
         * takes the returned xml, decrypts (if encrypted) and returns unencrypted data as array
         * @return array
         * $retArray['EncryptedData']
         * $retArray['TraceNumber']
         * $retArray['SubscriberNumber']
         * $retArray['Status']
         * $retArray['ErrorText']
         * $retArray['ErrorType']
         * $retArray['URL']
         */
        public function getReturnValsAsArray()
        {
            $retArray = array();
            $decodeStr = new ECDC();
            //get values

            $xml = simplexml_load_string ($this->xmlString); $passedInNodeName = $this->nodeName;
            $isEncrypted = trim($xml->Response->EncryptedData);
            if($isEncrypted =='Y')
            {
                $retArray['EncryptedData']  = $isEncrypted;
                $retArray['TraceNumber'] = $decodeStr->getDecrypt(trim($xml->Response->TraceNumber));
                $retArray['SubscriberNumber'] = $decodeStr->getDecrypt(trim($xml->Response->SubscriberNumber));
                $retArray['Status'] = $decodeStr->getDecrypt(trim($xml->Response->Status));
                $retArray['ErrorText'] = $decodeStr->getDecrypt(trim($xml->Response->ErrorText));
                $retArray['ErrorType'] = $decodeStr->getDecrypt(trim($xml->Response->ErrorType));
                $retArray['URL'] = $decodeStr->getDecrypt(trim($xml->Response->$passedInNodeName->Url));
            }
            else
            {
                //the user submitted was not accepted
                $retArray['EncryptedData']  = $isEncrypted;
                $retArray['TraceNumber'] = trim($xml->Response->TraceNumber);
                $retArray['SubscriberNumber'] = trim($xml->Response->SubscriberNumber);
                $retArray['Status'] = trim($xml->Response->Status);
                $retArray['ErrorText'] = trim($xml->Response->ErrorText);
                $retArray['ErrorType'] = trim($xml->Response->ErrorType);
                $retArray['URL'] = trim($xml->Response->$passedInNodeName->Url);
            }
            return $retArray;
        }
    }
