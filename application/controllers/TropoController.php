<?php
require_once 'log/LoggerFactory.php';
require_once 'tropo/tropo.class.php';
require_once 'util/HttpUtil.php';

class TropoController extends Zend_Controller_Action {
	protected $logger;
	public function init() {
		$translate = new Zend_Translate ( 'ini', APPLICATION_PATH . "/language/English.ini", 'US' );
		$translate->addTranslation ( APPLICATION_PATH . "/language/Jp.ini", 'JP' );
		$filename = APPLICATION_PATH . "/configs/application.ini";
		$config = new Zend_Config_Ini ( $filename, 'production' );
		$translate->setlocale ( $config->mjs->locale );
		$this->logger = LoggerFactory::getTropoLogger();
		$this->view->translate = $translate;
		$this->httpUtil = new HttpUtil();
		$this->setting = Zend_Registry::get("TROPO_SETTING");
		$this->_helper->viewRenderer->setNeverRender();
	}
	public function indexAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo("TropoController","indexAction","recieve from tropo server message is: ".$tropoJson);
		
		$tropo = new Tropo();
		//$tropo->call("+14153789949");
		$tropo->hangup();
		$tropo->renderJSON();
		
	}
	
	public function studentAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo("TropoController","indexAction","recieve from tropo server message is: ".$tropoJson);
		$tropo = new Tropo();
		$tropo->call(array("+17023580286", "+12176507163"));
		$tropo->renderJSON();
	}
	public function mentorAction() {
		$tropo = new Tropo();
		$tropo->call("+14153789949");
		$tropo->renderJSON();
	}
	public function translatorAction() {
		$tropo = new Tropo();
		$tropo->call("+14153789949");
		$tropo->renderJSON();
	}
	
	
}

