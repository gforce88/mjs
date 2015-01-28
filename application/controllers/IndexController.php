<?php
class IndexController extends Zend_Controller_Action {
	public function init() {
		$translate = new Zend_Translate ( 'ini', APPLICATION_PATH . "/language/English.ini", 'US' );
		$translate->addTranslation ( APPLICATION_PATH . "/language/Jp.ini", 'JP' );
		$filename=APPLICATION_PATH ."/configs/application.ini";
		$config = new Zend_Config_Ini($filename,'production');
		$translate->setlocale ( $config->mjs->locale );
		$this->_helper->viewRenderer->setNeverRender ();
	}
	public function indexAction() {
		$data = array();
		$data["name"] = 'xwm';
		$data["age"] = '12';
		$this->_helper->json ( $data, true, false, true );
	}
	public function loginAction() {
		// action body
	}
}





