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
		$params = $this->initSessionParameters ( $session );
		
		$callModel = new Application_Model_Call ();
		$callModel->updateStuCallSession ( $params ["sessionid"], $session->getId () );
		
		if ($callModel->checkStuCallTimes ( $params ) > 3) {
			$this->logger->logInfo ( "LinestuController", "indexAction", "student didn't answer the call for 3times" );
			$this->sendNotification ();
		} else {
			$this->logger->logInfo ( "LinestuController", "indexAction", "call student:" . $params ["stuphone"] );
			$tropo = new Tropo ();
			$tropo->call ( $params ["stuphone"] );
			// 电话接通后
			$tropo->on ( array (
					"event" => "continue",
					"next" => "/linestu/welcome",
					"say" => "Welcome to Mjs Application! You will joining the conference soon." 
			) );
			// 电话未拨通
			$tropo->on ( array (
					"event" => "incomplete",
					"next" => "/linestu/incomplete" 
			) );
			// tropo应用发生错误
			$tropo->on ( array (
					"event" => "error",
					"next" => "/linestu/error" 
			) );
			
			$tropo->renderJSON ();
		}
	}
	public function hangupAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo ( "LinestuController", "hangupAction", "student hangup message: " . $tropoJson );
	}
	public function welcomeAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo ( "LinestuController", "continueAction", "student continue message: " . $tropoJson );
		$result = new Result ( $tropoJson );
		$callModel = new Application_Model_Call ();
		$row = $callModel->findSessionIdByStuCallsessionIdAndUpdateCallTimes ( $result->getSessionId () );
		$this->logger->logInfo ( "LinestuController", "welcomeAction", "session id: " . $result->getSessionId () );
		$tropo = new Tropo ();
		$confOptions = array (
				"name" => "conference",
				"id" => "mjsconf" . $row ["inx"],
				"mute" => false,
				"allowSignals" => array (
						"playremind",
						"exit" 
				) 
		);
		$tropo->on ( array (
				"event" => "hangup",
				"next" => "/linestu/hangup"
		) );
		$tropo->conference ( null, $confOptions );
		$tropo->renderJSON ();
		//call translator
		$sessionModel = new Application_Model_Session ();
		$row = $sessionModel->getSessionForCallBySessionId ( $row ["inx"]);
		$paramArr = array ();
		$paramArr ["sessionid"] = $row ["inx"];
		$paramArr ["stuphone"] = $row ["b_phone"];
		$paramArr ["stuid"] = $row ["b_inx"];
		$paramArr ["mntphone"] = $row ["c_phone"];
		$paramArr ["mntid"] = $row ["c_inx"];
		$paramArr ["trlphone"] = $row ["d_phone"];
		$paramArr ["trlid"] = $row ["d_inx"];
		if($paramArr ["trlid"]!=null){
			$troposervice = new TropoService ();
			$troposervice->calltrl( $paramArr );
			$this->logger->logInfo ( "LinestuController", "welcomeAction", "call translator phone:--- " . $paramArr ["trlphone"] );
		}
	}
	public function incompleteAction() {
		$tropoJson = file_get_contents ( "php://input" );
		
		$this->logger->logInfo ( "LinestuController", "incompleteAction", "incomplete message: " . $tropoJson );
		$result = new Result ( $tropoJson );
		$callModel = new Application_Model_Call ();
		$session = $callModel->findSessionIdByStuCallsessionIdAndUpdateCallTimes ( $result->getSessionId () );
		$this->logger->logInfo ( "LinestuController", "incompleteAction", "session id: " . $session ["inx"] );
		$sessionModel = new Application_Model_Session ();
		$row = $sessionModel->getSessionForCallBySessionId ( $session ["inx"] );
		$this->logger->logInfo ( "LinestuController", "incompleteAction", "row: " . $row ["inx"] );
		
		$paramArr = array ();
		$paramArr ["sessionid"] = $row ["inx"];
		$paramArr ["stuphone"] = $row ["b_phone"];
		$paramArr ["stuid"] = $row ["b_inx"];
		$paramArr ["mntphone"] = $row ["c_phone"];
		$paramArr ["mntid"] = $row ["c_inx"];
		$paramArr ["trlphone"] = $row ["d_phone"];
		$paramArr ["trlid"] = $row ["d_inx"];
		// 调用打电话应用并创建call记录
		$this->logger->logInfo ( "LinestuController", "incompleteAction", "call student for : " . $session ["party2CallRes"] . " times" );
		sleep ( 5 );
		$this->logger->logInfo ( "LinestuController", "incompleteAction", "sleep 5 seconds " );
		$troposervice = new TropoService ();
		$troposervice->callstu ( $paramArr );
	}
	public function errorAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo ( "LinestuController", "errorAction", "student error message: " . $tropoJson );
	}
	protected function initSessionParameters($session) {
		// Parameters for call flow control
		$paramArr = array ();
		$paramArr ["session_id"] = $session->getId (); // tropo的session
		$paramArr ["sessionid"] = $session->getParameters ( "sessionid" ); // 课程的session
		$paramArr ["stuphone"] = $session->getParameters ( "stuphone" );
		$paramArr ["stuid"] = $session->getParameters ( "stuid" );
		$paramArr ["mntphone"] = $session->getParameters ( "mntphone" );
		$paramArr ["trlphone"] = $session->getParameters ( "trlphone" );
		return $paramArr;
	}
}

