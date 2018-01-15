<?php
/**
 * @name ErrorController
 * @desc The controller to serve 404 and 500 errors and debugging stacktrace on DEV ENV
 * 
 * @author Andrei
 * @filesource application/controllers/ErrorController.php
 * @version 1.0.0
 */

Class Public_ErrorController extends Public_Controller_Action_Abstract
{
    /**
     * errorAction() - output exceptions in human-readable format
     */
    public function errorAction()
    {
		$error = $this->_getParam('error_handler');
        switch ($error->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                $this->getResponse()->setHttpResponseCode(404);
                $this->view->message = 'Uh oh, we can\'t seem to find that page you wanted!';
                $this->view->stack_trace = $this->_getFullErrorMessage($error);
				$this->view->code = 404;
                break;

            default:
                $this->getResponse()->setHttpResponseCode(500);
                $this->view->message = 'Looks like something\'s gone wrong! Please refresh the page - if the problem persists please report the error';
                $this->view->stack_trace = $this->_getFullErrorMessage($error);
				$this->view->code = 500;
                break;
        }

        $this->view->headTitle()->prepend( $this->view->code .  ' Error' );
    }

	protected function _getFullErrorMessage($error)
    {
        // get configuration for error logging
        $config = Zend_Registry::get('config')->vansysnet->errorlog;

        // prepare error output.
        $message = '';

        if (!empty($_SERVER['SERVER_ADDR'])) {
            $message .= "Server IP: " . $_SERVER['SERVER_ADDR'] . "\n";
        }

        if (!empty($_SERVER['HTTP_USER_AGENT'])) {
            $message .= "User agent: " . $_SERVER['HTTP_USER_AGENT'] . "\n";
        }

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            $message .= "Request type: " . $_SERVER['HTTP_X_REQUESTED_WITH'] . "\n";
        }

        $message .= "Server time: " . date("Y-m-d H:i:s") . "\n";
        $message .= "RequestURI: " . $error->request->getRequestUri() . "\n";

        if (!empty($_SERVER['HTTP_REFERER'])) {
            $message .= "Referer: " . $_SERVER['HTTP_REFERER'] . "\n";
        }

        $message .= "Message: " . $error->exception->getMessage() . "\n\n";
        $message .= "Trace:\n" . $error->exception->getTraceAsString() . "\n\n";
        $message .= "Request data: " . var_export($error->request->getParams(), true) . "\n\n";

        $it = $_SESSION;

        $message .= "Session data:\n\n";
        foreach ($it as $key => $value) {
            $message .= $key . ": " . var_export($value, true) . "\n";
        }
        $message .= "\n";

        $message .= "Cookie data:\n\n";
        foreach ($_COOKIE as $key => $value) {
            $message .= $key . ": " . var_export($value, true) . "\n";
        }
        $message .= "\n";

        // log errors.
        // XXXXX.errorlog.log
        // true = use settings in XXXXX.errorlog.db to log
        // false = do not log errors.

        $log = $config->get('log');

        if($log) 
        {
            switch($error->type) {
                case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
                case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                    $msg = "Page not found.";
                    $code = 404;
                    $trace = "REFERRED BY: ".$_SERVER['HTTP_REFERER'];
                    $_this = "";
                    $session = var_export($_SESSION,true) . var_export($error->request->getPost(),true) . var_export($error->request->getParams(),true);
                    $exception_code = "";
                    $category_code = "";
                    $location =  $error->request->getRequestUri();
                    break;
                default:
                    $msg = $error->exception->getMessage();
                    $code = 500;
                    $trace = "EXECUTION TRACE: ".$error->exception->getFile() . $error->exception->getTraceAsString();
                    $_this = "";
                    $session = var_export($_SESSION,true) . var_export($error->request->getPost(),true) . var_export($error->request->getParams(),true);
                    $exception_code = $error->exception->getCode();
                    $category_code = "";
                    $location =  $error->request->getRequestUri();
                    break;
            }


            $log_message = $msg;
            $log_priority = Zend_Log::ERR;
            $log_data = array(
                'code' => $code,
                'location' => $location,
                'client_ip' => (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'localhost' ) .":". (isset($_SERVER['REMOTE_POST']) ? $_SERVER['REMOTE_POST'] : 'localhost' ),
                'stack' => $trace,
                'session' => $session,
                'this' => $_this,
                'exception_code' => $exception_code,
                'category_code' => $category_code,
            );

            $logger = new VansysNet_Log($config);
            $logger->log($log_message, $log_priority, $log_data);
        }

        // display errors
        // vansysnet.errorlog.display
        // true = display error report
        // false = only give error type

        $display = $config->get('display');
        return $display ? '<pre>' . $message . '</pre>' : '';
    }
}