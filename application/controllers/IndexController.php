<?php
class IndexController extends Zend_Controller_Action {
	public function init() {
		$translate = new Zend_Translate ( 'ini', APPLICATION_PATH . "/language/English.ini", 'US' );
		$translate->addTranslation ( APPLICATION_PATH . "/language/Jp.ini", 'JP' );
		$filename=APPLICATION_PATH ."/configs/application.ini";
		$config = new Zend_Config_Ini($filename,'production');
		$translate->setlocale ( $config->mjs->locale );
	}
	public function indexAction() {
		$log = Zend_Registry::get('IVR_LOGGER');
		date_default_timezone_set("UTC");
		$dt = new DateTime();
		
		$log->info($dt->format("Y-m-d"));
	}
	public function loginAction() {
		// action body
	}
}





