<?php
/**
 * VBase PublicAuthController
 *
 * Auth Controller - manages authentication processes
 *
 * @category   VBase
 * @package    Controller
 * @subpackage Public_Auth
 */ 

/**
 * @see XXXNet_Auth_Adapter_Interface
 */
require_once 'XXXNet/Auth/Adapter/Interface.php';

class PublicAuthController extends XXXNetControllerAction {

    /**
     * Predispatch() - run before action is dispatched.
     */

    public function  preDispatch() {
        parent::preDispatch();
        $this->view->render('_sidebar.phtml');
        $this->view->hasSidebar = true;
    }

    /**
     * checksessionAction() - this method will just check to see
     *  if a user is logged in or not
     *
     * @return void
     */
    public function checksessionAction() {
        if(!(bool) Zend_Auth::getInstance()->getIdentity()) {
            return false;
        } else {
            $this->view->success = true;
            $this->_helper->FlashMessenger->addMessage("User Logged in");
            return true;
        }
    }

    /**
     * loginAction() - this method will either login a user or
     *   require the login of the user depending on the mode
     *   will depend on the output of the loginForm
     *
     * @return void
     */
    public function loginAction() {
        $this->view->placeholder('sidebar')->prepend($this->view->render('info.phtml'));

        if($this->checksessionAction()) $this->_helper->redirector('logout');

        $request = $this->getRequest();
        $form = new Public_Form_Auth_Login();
        if($request->isPost()) {
            if($form->isValid($request->getPost())) {
                $this->_getAuthAdapter()->setIdentity($form->getValue("username"));
                $this->_getAuthAdapter()->setCredential($form->getValue("password"));
                $continue = $form->getValue('continue');
                $result = XXXNet_Auth::getInstance()->authenticate($this->_getAuthAdapter());

	             if($result->isValid()) {

                        $data = Zend_Auth::getInstance()->getIdentity();

                        $this->_helper->FlashMessenger->clearCurrentMessages();
                        $this->_helper->FlashMessenger->addMessage('Successful Login');
                        $this->view->success = true;

                        $id = $this->_getAuthAdapter()->getIdentity();
                        $user = new System_Model_DbTable_Users();


                        $user->registerAction($id, "/auth/login");

                        // check to make sure current jurisdiction exists.
                        if($data->scope_level==0) {
                            $juris = $user->jurisdictions(0, $data->id);
                            if(count($juris)<1) {
                                $data->national_jurisdiction=null;                                    
                                $data->state_jurisdiction_id=null;
                                $data->local_jurisdiction_id=null;
                            }
                        }
                        else if($data->scope_level==1) {
                            $juris = $user->jurisdictions(1, $data->id,$data->state_jurisdiction_id);

                            if(count($juris)<1) {
                                $data->national_jurisdiction=null;
                                $data->state_jurisdiction_id=null;
                                $data->local_jurisdiction_id=null;
                            }
                        }
                        else if($data->scope_level==2) {
                            $juris = $user->jurisdictions(2, $data->id,$data->local_jurisdiction_id);

                            if(count($juris)<1) {
                                $data->national_jurisdiction=null;
                                $data->state_jurisdiction_id=null;
                                $data->local_jurisdiction_id=null;
                            }
                            else {                                    
                                $data->state_jurisdiction_id = $juris[0]['state_id'];
                            }
                        }

                        $juris = null;

                        // if no jurisdiction is found, select highest available jurisdiction.                                 

                        if($data->national_jurisdiction==null && 
                           $data->state_jurisdiction_id==null &&
                           $data->local_jurisdiction_id==null) 
                        {

                            $found = false;

                            for($i=0;$i<3;$i++) {
                                $juris = $user->jurisdictions($i, $data->id);

                                $level = $i;

                                if(count($juris)) {
                                    $found = true;
                                    break;
                                }
                            }

                            if(!$found) {
                                $this->_helper->FlashMessenger->addMessage("PERSIST||No Available Active Jurisdiction found. Please contact a National Administrator.");
                                $this->logoutAction();
                                return;
                            }
                        }
                        else 
                        {

                            $juris_id = null;

                            if($data->national_jurisdiction!=null) {
                                $level = 0;
                            }
                            else if($data->state_jurisdiction_id!=null&&$data->local_jurisdiction_id==null) {
                                $juris_id = $data->state_jurisdiction_id;
                                $level = 1;
                            }
                            else if($data->local_jurisdiction_id!=null) {
                                $juris_id = $data->local_jurisdiction_id;
                                $level = 2;
                            }

                            $juris = $user->jurisdictions($level, $data->id, $juris_id);
                        }

                        // always reset jurisdiction information on login to prevent shenanigans

                        if(count($juris)) {
                            $updated = $user->registerCurrentJurisdiction($level,$juris[0]);

                             XXXNet_Auth::getInstance()->getStorage()->write((object)$updated);
                        }


                        // handle continuing from last page

                        if($continue=='true') {
                            //var_dump($data->last_uri);
                            //die();
                            $this->_helper->redirector->goToUrl($data->last_uri);
                            return;
                        }

                        $this->_helper->redirector('index','index','system');

	                return;
	            } else {

                        foreach($result->getMessages() as $message) {
                            $this->_helper->FlashMessenger->addMessage($message);
                        }
                        $this->_helper->redirector('login','auth','public',array('failure'=>'invalid'));
	            }

            }

        }

        // Set the Form for HTML view

        $this->view->form = $form;

        $resetlink = $this->action;
        $resetlink['action'] = "reset";
        $this->view->resetLink = new Zend_Navigation_Page_Mvc($resetlink);
        $this->view->failure = $this->getRequest()->getParam('failure');
        $this->view->messages = $this->_helper->FlashMessenger->getMessages();
  
    }

    /**
     * indexAction() - redirects user to default auth page (login)
     */

    public function indexAction() {
        $this->_helper->redirector('logout');
    }

    /**
     * logoutAction() - logs out a logged-in user; redirects to login page.
     */

    public function logoutAction() {
        $uid = Zend_Auth::getInstance()->getIdentity()->id;
        $user = new System_Model_DbTable_Users();
        $user->logOut($uid);

    	Zend_Auth::getInstance()->clearIdentity();
        $this->_helper->FlashMessenger->addMessage("User Logged out");
        $this->_helper->redirector('login'); // back to login page
    }

    /**
     * passwordAction() - form for setting a new password, same action also
     * handles commiting the password changes to the database.
     */
    public function passwordAction() {
        $set = $this->getRequest()->getParam('set'); // is this a set (and not a RESET?) - display some different text.

        $this->view->set = $set;

        $form = new Public_Form_Auth_Password(array('set_mode'=>$set));
        $this->view->messages = $this->_helper->FlashMessenger->getMessages();



        $request = $this->getRequest();
        if($request->isPost()) {
            if($form->isValid($request->getPost())) {
                try {
                     $password = $form->getValue('password1');
                     $id = Zend_Auth::getInstance()->getIdentity();
                     $user = new System_Model_DbTable_Users();
                     $auth = $user->auth($id->username, $form->getValue('old_password'));
                     if($auth['allowed']) {
                         $user->setPassword($password,$id->id);
                         $this->_helper->FlashMessenger->addMessage('Password Set. Please Re-login');
                         $user->logOut($id->id);
                         Zend_Auth::getInstance()->clearIdentity();

                         $this->_helper->redirector('login');
                     }
                     else {
                         $this->_helper->FlashMessenger->addMessage('Invalid Old or Temporary Password.');
                         //$this->_helper->redirector('password');
                     }
                }
                catch(Exception $e) {
                    var_dump($e);
                    die();
                }
            }
        }

        $this->view->form = $form;
    }

    /**
     * resetAction() - display and parse reset password forms.
     */

    public function resetAction() {
        $form = new Public_Form_Auth_Reset();
        $this->view->messages = $this->_helper->FlashMessenger->getMessages();

        $request = $this->getRequest();

        $key = $request->getParam('key');
        
        if($request->isPost()) {
            if($form->isValid($request->getPost())) {
                try {
                     $email = $form->getValue('email');
                     $user = new System_Model_DbTable_Users();
                     $result = $user->addReset($email);

                     if($result===false) {
                         $this->_helper->FlashMessenger->addMessage('No account with that primary email address found.');

                         $this->_helper->redirector('reset');
                     }
                     else {
                         $host = $_SERVER['SERVER_NAME'];
                         $result['host'] = $host;

                         $login = $this->action;
                         $login['action'] = 'login';
                         $loginLink = new Zend_Navigation_Page_Mvc($login);

                         $result['login'] = $loginLink;
                         
                         $reset = $this->action;
                         $reset['params'] = array('key'=>$result['key']);
                         $resetLink = new Zend_Navigation_Page_Mvc($reset);
                         
                         $result['reset'] = $resetLink;

                         $body = $this->view->partial('reset_email.phtml',array('result'=>$result));

                         $mail = new Zend_Mail();
                         $mail->setBodyText($body);
                         $mail->addTo($email);
                         $mail->setSubject('Password Reset Request for '.$host);
                         $mail->send();

                         $this->_helper->FlashMessenger->addMessage('Email with instructions has been sent to your Primary Address');


                         $this->_helper->redirector('login');
                     }
                }
                catch(Exception $e) {
                    throw($e);
                }
            }
        }
        else if(isset($key)) { // do an actual reset, if possible.
            $user = new System_Model_DbTable_Users();

            if($user->resetPassword($key)) {
                $this->_helper->FlashMessenger->addMessage('Password has been reset. Please use temporary password to login.');
            }
            else {
                $this->_helper->FlashMessenger->addMessage('Invalid or Expired Reset Key provided.');
            }
            $this->_helper->redirector('login');
        }

        $this->view->form = $form;
    }

    /**
     * switchAction() - change jurisdiction.
     */

    public function switchAction() {
        $to = $this->getRequest()->getParam('to');
        $post = $this->getRequest()->getPost();

        $user = XXXNet_Auth::getInstance()->getIdentity();
        if(isset($user->id)) {
            $user_id = $user->id;
            $users = new System_Model_DbTable_Users();
            switch($to) {
                case 'national':
                    $level = 0;
                    break;
                case 'state':
                    $level = 1;
                    break;
                case 'local':
                    $level = 2;
                    break;
                default:
                    $this->requireAccessAction();
                    break;
            }
            $possible = $users->jurisdictions($level, $user_id,$post['id']);

            if(is_array($possible)&&count($possible)>0) {
                $updated = $users->registerCurrentJurisdiction($level,$possible[0]);

                XXXNet_Auth::getInstance()->getStorage()->write((object)$updated);

                $this->_helper->redirector('index','index','system');
            }
            else {
                $this->requireAccessAction();
            }
        }
        else {
            $this->requireAccessAction();
        }
    }


    /**
     * requireAccessAction() - general-purpose 401 (Access denied) page.
     */

    public function requireAccessAction() {
        parent::requireaccessAction();
    }
}