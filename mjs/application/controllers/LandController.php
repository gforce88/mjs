<?php
class LandController extends Zend_Controller_Action {
	public function init() {
		$translate = new Zend_Translate ( 'ini', APPLICATION_PATH . "/language/English.ini", 'US' );
		$translate->addTranslation ( APPLICATION_PATH . "/language/Jp.ini", 'JP' );
		$filename = APPLICATION_PATH . "/configs/application.ini";
		$config = new Zend_Config_Ini ( $filename, 'production' );
		$translate->setlocale ( $config->mjs->locale );
		// $translate->setlocale("JP");
		$this->view->translate = $translate;
	}
	public function indexAction() {
		// action body
	}
}

