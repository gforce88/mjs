<?php
require_once 'log/LoggerFactory.php';
require_once 'tropo/tropo.class.php';
require_once 'util/HttpUtil.php';
require_once 'service/TropoService.php';
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
		$this->logger->logInfo ( "LinemntController", "indexAction", "recieve from tropo server message is: " . $tropoJson );
		$session = new Session ( $tropoJson );
		$this->logger->logInfo ( "LinemntController", "indexAction", "session  is: " . $session->getId () );
		$params = $this->initSessionParameters ( $session );
		
		$callModel = new Application_Model_Call ();
		$callModel->updateMntCallSession ( $params ["sessionid"], $session->getId () );
		// // for testing
		// $tropo = new Tropo ();
		// //$tropo->call ( "+17023580286" );
		// $tropo->call ( $params ["mntphone"] );
		// $tropo->on ( array (
		// "event" => "hangup",
		// "next" => "/linemnt/hangup"
		// ) );
		// //电话接通后
		// $tropo->on ( array (
		// "event" => "continue",
		// "next" => "/linemnt/continue"
		// ) );
		// //电话未拨通
		// $tropo->on ( array (
		// "event" => "incomplete",
		// "next" => "/linemnt/incomplete"
		// ) );
		// //tropo应用发生错误
		// $tropo->on ( array (
		// "event" => "error",
		// "next" => "/linemnt/error"
		// ) );
		
		// $tropo->renderJSON ();
		
		// 判断拨号次数是否达到3次
		if ($callModel->checkMntCallTimes ( $params ) > 3) {
			$this->logger->logInfo ( "LinemntController", "indexAction", "instructor didn't answer the call for 3times" );
			$this->sendNotification ();
		} else {
			$this->logger->logInfo ( "LinemntController", "indexAction", "call ." . $params ["mntphone"] );
			$tropo = new Tropo ();
			$tropo->call ( $params ["mntphone"] );
			$tropo->on ( array (
					"event" => "hangup",
					"next" => "/linemnt/hangup" 
			) );
			// 电话接通后
			$tropo->on ( array (
					"event" => "continue",
					"next" => "/linemnt/continue" 
			) );
			// 电话未拨通
			$tropo->on ( array (
					"event" => "incomplete",
					"next" => "/linemnt/incomplete" 
			) );
			// tropo应用发生错误
			$tropo->on ( array (
					"event" => "error",
					"next" => "/linemnt/error" 
			) );
			
			$tropo->renderJSON ();
		}
	}
	public function hangupAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo ( "LinemntController", "hangupAction", "hangup message: " . $tropoJson );
	}
	public function continueAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo ( "LinemntController", "continueAction", "instructor answer the call，creating conference :" . $tropoJson );
		$tropo = new Tropo ();
		$tropo->say ( "Welcome to Mjs Application! Please waiting for join the conference" );
		$confOptions = array (
				"name" => "conference",
				"id" => "mjsconf" . $_GET ( "mntid" ),
				"mute" => false,
				"allowSignals" => array (
						"playremind",
						"exit" 
				) 
		);
		$tropo->conference ( null, $confOptions );
		$tropo->renderJSON ();
	}
	public function incompleteAction() {
		$tropoJson = file_get_contents ( "php://input" );
		
		$this->logger->logInfo ( "LinemntController", "incompleteAction", "incomplete message: " . $tropoJson );
		$result = new Result ( $tropoJson );
		$callModel = new Application_Model_Call ();
		$session = $callModel->findSessionIdByMntCallsessionIdAndUpdateCallTimes ( $result->getSessionId () );
		$this->logger->logInfo ( "LinemntController", "incompleteAction", "session id: " . $session ["inx"] );
		$sessionModel = new Application_Model_Session ();
		$row = $sessionModel->getSessionForCallBySessionId ( $session ["inx"] );
		$this->logger->logInfo ( "LinemntController", "incompleteAction", "row: " . $row ["inx"] );
		
		$paramArr = array ();
		$paramArr ["sessionid"] = $row ["inx"];
		$paramArr ["stuphone"] = $row ["b_phone"];
		$paramArr ["stuid"] = $row ["b_inx"];
		$paramArr ["mntphone"] = $row ["c_phone"];
		$paramArr ["mntid"] = $row ["c_inx"];
		$paramArr ["trlphone"] = $row ["d_phone"];
		$paramArr ["trlid"] = $row ["d_inx"];
		// 调用打电话应用并创建call记录
		$this->logger->logInfo ( "LinemntController", "incompleteAction", "call instructor for : " . $session ["party1CallRes"] . " times" );
		sleep ( 10 );
		$this->logger->logInfo ( "LinemntController", "incompleteAction", "sleep 10 seconds " );
		$troposervice = new TropoService ();
		$troposervice->callmnt ( $paramArr );
	}
	public function errorAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo ( "LinemntController", "errorAction", "hangup message: " . $tropoJson );
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
	protected function sendNotification() {
		$this->logger->logInfo ( "LinemntController", "sendNotification", "send email to 3 part, cause  instructor" );
	}
}

