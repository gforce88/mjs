<?php
require_once 'log/LoggerFactory.php';
require_once 'tropo/tropo.class.php';
require_once 'util/HttpUtil.php';
require_once 'service/TropoService.php';
class LinestuController extends Zend_Controller_Action {
	protected $logger;
	public function init() {
		$this->logger = LoggerFactory::getTropoLogger ();
		$this->httpUtil = new HttpUtil ();
		$this->setting = Zend_Registry::get ( "TROPO_SETTING" );
		$this->_helper->viewRenderer->setNeverRender ();
	}
	public function indexAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo ( "LinestuController", "indexAction", "recieve from tropo server message is: " . $tropoJson );
		$session = new Session ( $tropoJson );
		$this->logger->logInfo ( "LinestuController", "indexAction", "session  is: " . $session->getId () );
		$params = $this->initSessionParameters ($session);
		$tropo = new Tropo ();
		$tropo->call ( "+17023580286" );
		$tropo->wait(3000);
		//$tropo->call ( $params ["stuphone"].";pause=5000ms" );
		$tropo->say ( "Welcome to Mjs Application! Please waiting for join the conference" );
// 		$tropo->say ( "你好，欢迎使用 MJS 系统", array (
// 				"voice" => "Linlin" 
// 		) );
		$confOptions = array (
				"name" => "conference",
				"id" => "mjsconf".$params["sessionid"],
				//"id" => "123123321",
				"mute" => false,
				"allowSignals" => array (
						"playremind",
						"exit" 
				) 
		);
		//$tropo->conference ( null, $confOptions );
		//$troposervice = new TropoService ();
		//$troposervice->callmnt($params);
		if($params ["trlphone"]!=""){
		//	$troposervice->calltrl($params);
		}
		// $tropo->say("http://115.28.40.165/audio/WeAreNowConnecting.mp3");
		
		$tropo->on ( array (
				"event" => "hangup",
				"next" => "/linestu/hangup" 
		) );
		$tropo->on ( array (
				"event" => "continue",
				"next" => "/linestu/continue" 
		) );
		$tropo->on ( array (
				"event" => "incomplete",
				"next" => "/linestu/incomplete" 
		) );
		$tropo->on ( array (
				"event" => "error",
				"next" => "/linestu/error" 
		) );
		$tropo->renderJSON ();
		
	}
	public function hangupAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$result = new Result();
		$this->logger->logInfo ( "LinestuController", "hangupAction", "student hangup message: " . $tropoJson );
	}
	public function continueAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo ( "LinestuController", "continueAction", "student continue message: " . $tropoJson );
		$session = new Session ( $tropoJson );
		$params = $this->initSessionParameters ($session);
		sleep(10);
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
	}
	public function incompleteAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo ( "LinestuController", "incompleteAction", "student incomplete message: " . $tropoJson );
	}
	public function errorAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo ( "LinestuController", "errorAction", "student error message: " . $tropoJson );
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
		foreach ($paramArr as $key=>$value){
			$this->logger->logInfo ( "LinestuController", "initSessionParameters", "key->" . $key );
			$this->logger->logInfo ( "LinestuController", "initSessionParameters", "value->" . $value );
		}
		return $paramArr;
	}
}

