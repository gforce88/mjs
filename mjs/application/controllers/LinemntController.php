<?php
require_once 'log/LoggerFactory.php';
require_once 'tropo/tropo.class.php';
require_once 'util/HttpUtil.php';
class LinemntController extends Zend_Controller_Action {
	protected $logger;
	public function init() {
		$this->logger = LoggerFactory::getTropoLogger ();
		$this->httpUtil = new HttpUtil ();
		$this->setting = Zend_Registry::get ( "TROPO_SETTING" );
		$this->_helper->viewRenderer->setNeverRender ();
	}
	public function indexAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo("LinemntController","indexAction","recieve from tropo server message is: ".$tropoJson);
		$session = new Session ( $tropoJson );
		$this->logger->logInfo ( "LinestuController", "indexAction", "session  is: " . $session->getId () );
		$params = $this->initSessionParameters ($session);
		
		$tropo = new Tropo();
		$tropo->call("+12176507163");
		$tropo->say("Welcome to Mjs Application! Please waiting for join the conference");
		$confOptions = array (
			"name" => "conference",
			//"id" => "mjsconf".$params["sessionid"],
			"id" => "123123321",
			"mute" => false,
			"allowSignals" => array (
				"playremind",
				"exit" 
			) 
		);
		$tropo->conference(null, $confOptions);
		$this->logger->logInfo ( "LinemntController", "indexAction", "mentor join conoference, id is :" . "conf".$params["sessionid"] );
		
		$tropo->on ( array (
				"event" => "hangup",
				"next" => "/linemnt/hangup"
		) );
		$tropo->on ( array (
				"event" => "continue",
				"next" => "/linemnt/continue"
		) );
		$tropo->on ( array (
				"event" => "incomplete",
				"next" => "/linemnt/incomplete"
		) );
		$tropo->on ( array (
				"event" => "error",
				"next" => "/linemnt/error"
		) );
		$tropo->renderJSON();
	}
	public function hangupAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo ( "LinemntController", "hangupAction", "hangup message: " . $tropoJson );
	}
	public function continueAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo ( "LinemntController", "continueAction", "continue message: " . $tropoJson );
	}
	public function incompleteAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo ( "LinemntController", "incompleteAction", "incomplete message: " . $tropoJson );
	}
	public function errorAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo ( "LinemntController", "errorAction", "hangup message: " . $tropoJson );
	}
	protected function initSessionParameters($session) {
		// Parameters for call flow control
		$paramArr = array ();
		$paramArr ["session_id"] = $session->getId ();//tropo的session
		$paramArr["sessionid"]=$session->getParameters("sessionid");//课程的session
		$paramArr["stuphone"]=$session->getParameters("stuphone");
		$paramArr["stuid"]=$session->getParameters("stuid");
		$paramArr["mntphone"]=$session->getParameters("mntphone");
		$paramArr["trlphone"]=$session->getParameters("trlphone");
		return $paramArr;
	}
}

