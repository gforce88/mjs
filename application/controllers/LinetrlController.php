<?php
require_once 'log/LoggerFactory.php';
require_once 'tropo/tropo.class.php';
require_once 'util/HttpUtil.php';
class LinetrlController extends Zend_Controller_Action
{

	protected $logger;
	public function init() {
		$this->logger = LoggerFactory::getTropoLogger ();
		$this->httpUtil = new HttpUtil ();
		$this->setting = Zend_Registry::get ( "TROPO_SETTING" );
		$this->_helper->viewRenderer->setNeverRender ();
	}
	public function indexAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo("LinetrlController","indexAction","recieve from tropo server message is: ".$tropoJson);
		$session = new Session ( $tropoJson );
		$this->logger->logInfo ( "LinestuController", "indexAction", "session  is: " . $session->getId () );
		$params = $this->initSessionParameters ($session);
		
		$callModel = new Application_Model_Call ();
		$callModel->updateTrlCallSession ( $params ["sessionid"], $session->getId () );
		
		if ($callModel->checkTrlCallTimes ( $params ) > 3) {
			$this->logger->logInfo ( "LinetrlController", "indexAction", "translator didn't answer the call for 3 times" );
			$this->sendNotification ();
		}else{
			$this->logger->logInfo ( "LinetrlController", "indexAction", "call translator:" . $params ["trlphone"] );
			$tropo = new Tropo ();
			$tropo->call ( $params ["trlphone"] );
			// 电话接通后
			$tropo->on ( array (
					"event" => "continue",
					"next" => "/linetrl/welcome",
					"say" => "Welcome to Mjs Application! You will joining the conference soon."
			) );
			// 电话未拨通
			$tropo->on ( array (
					"event" => "incomplete",
					"next" => "/linetrl/incomplete"
			) );
			// tropo应用发生错误
			$tropo->on ( array (
					"event" => "error",
					"next" => "/linetrl/error"
			) );
			$tropo->renderJSON ();
		}
	}

	public function hangupAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo ( "LinetrlController", "hangupAction", "hangup message: " . $tropoJson );
	}
	public function welcomeAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo ( "LinetrlController", "continueAction", "translator continue message: " . $tropoJson );
		$result = new Result ( $tropoJson );
		$callModel = new Application_Model_Call ();
		$row = $callModel->findSessionIdByTrlCallsessionIdAndUpdateCallTimes ( $result->getSessionId () );
		$this->logger->logInfo ( "LinetrlController", "welcomeAction", "session id: " . $result->getSessionId () );
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
		
	}
	public function incompleteAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo ( "LinetrlController", "incompleteAction", "incomplete message: " . $tropoJson );
		
		$result = new Result ( $tropoJson );
		$callModel = new Application_Model_Call ();
		$session = $callModel->findSessionIdByTrlCallsessionIdAndUpdateCallTimes ( $result->getSessionId () );
		$this->logger->logInfo ( "LinetrlController", "incompleteAction", "session id: " . $session ["inx"] );
		$sessionModel = new Application_Model_Session ();
		$row = $sessionModel->getSessionForCallBySessionId ( $session ["inx"] );
		$this->logger->logInfo ( "LinetrlController", "incompleteAction", "row: " . $row ["inx"] );
		
		$paramArr = array ();
		$paramArr ["sessionid"] = $row ["inx"];
		$paramArr ["stuphone"] = $row ["b_phone"];
		$paramArr ["stuid"] = $row ["b_inx"];
		$paramArr ["mntphone"] = $row ["c_phone"];
		$paramArr ["mntid"] = $row ["c_inx"];
		$paramArr ["trlphone"] = $row ["d_phone"];
		$paramArr ["trlid"] = $row ["d_inx"];
		// 调用打电话应用并创建call记录
		$this->logger->logInfo ( "LinetrlController", "incompleteAction", "call translator for : " . $session ["party3CallRes"] . " times" );
		sleep ( 5 );
		$this->logger->logInfo ( "LinetrlController", "incompleteAction", "sleep 5 seconds " );
		$troposervice = new TropoService ();
		$troposervice->calltrl ( $paramArr );
	}
	public function errorAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo ( "LinetrlController", "errorAction", "hangup message: " . $tropoJson );
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

