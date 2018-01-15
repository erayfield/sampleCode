<?php
/**
 * VBase
 * Index Controller - Main interface for public area
 *
 * @category   VBase
 * @package    Controller
 * @subpackage Public_Index
 */
class Public_IndexController extends Public_Controller_Action_Abstract {

    /**
     * init() - initialize common elements
     */
	public function init() {
            parent::init();
           $this->view->messages = $this->_helper->FlashMessenger->getMessages();
	}

    /**
     * indexAction() - main page
     */

    public function indexAction() {
        $this->_helper->redirector('logout','auth');
    }	
}