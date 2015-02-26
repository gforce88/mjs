<?php
require_once 'log/LoggerFactory.php';
require_once 'tropo/tropo.class.php';
require_once 'util/HttpUtil.php';
require_once 'service/TropoService.php';
require_once 'service/EmailService.php';
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
		// 更新call mnt的 tropo sessionId
		$id = $callModel->updateMntCallSession ( $params ["sessionid"], $session->getId () );
		$times = $callModel->checkMntCallTimes ( $params );
		// 判断拨号次数是否达到3次
		if ($times > 3) {
			$this->logger->logInfo ( "LinemntController", "indexAction", "instructor didn't answer the call for 3times" );
			$this->sendNotification ( $params ["sessionid"] );
		} else {
			$this->logger->logInfo ( "LinemntController", "indexAction", "call instructor:" . $params ["mntphone"] );
			$tropo = new Tropo ();
			$this->logger->logInfo ( "LinemntController", "indexAction---notify", " notify value is--------------- :". $params ["notify"]);
			$tropo->call ( $params ["mntphone"] );
			
			if ($params ["notify"] == "1") { // 判断是否是提示电话
				$tropo->on ( array (
						"event" => "continue",
						"next" => "/linemnt/notify"
				) );
			} else {// 会议电话，先拨Instructor
				$tropo->on ( array (
						"event" => "continue",
						"next" => "/linemnt/welcome",
// 						"say" => "Welcome to Mjs Application! Please hold on for joining the conference." 
						"say" => "http://165.225.149.30/sound/01_call_student.mp3" 
				) );
			}
			// 电话未拨通
			$tropo->on ( array (
					"event" => "incomplete",
					"next" => "/linemnt/incomplete" 
			) );
			$tropo->renderJSON ();
		}
	}
	public function notifyAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo ( "LinemntController", "nofityAction", "notify message: " . $tropoJson );
		$tropo = new Tropo ();
		$tropo->say("http://165.225.149.30/sound/remind_call.mp3");
		$tropo->hangup();
		$tropo->renderJSON ();
		
	}
	public function hangupAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo ( "LinemntController", "hangupAction", "hangup message: " . $tropoJson );
	}
	public function welcomeAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo ( "LinemntController", "welcomeAction", "welcome message: " . $tropoJson );
		$result = new Result ( $tropoJson );
		// 找到session call 将 call的inx做为conference的id 并建立会议
		$callModel = new Application_Model_Call ();
		$row = $callModel->findSessionIdByMntCallsessionIdAndRecordTime ( $result->getSessionId () );
		$this->logger->logInfo ( "LinemntController", "welcomeAction", "session id: " . $result->getSessionId () );
		$tropo = new Tropo ();
		$confOptions = array (
				"name" => "conference",
				"id" => "mjsconf" . $row ["inx"],
				"mute" => false
 				,
				"allowSignals" => array (
						"stunoanswer",
						"trlnoanswer",
						"hangup",
						"continue"
				) 
		);
		$tropo->on ( array (
				"event" => "stunoanswer",
				"next" => "/linemnt/stunoanswer"
 		) );
 		$tropo->on ( array (
				"event" => "trlnoanswer",
				"next" => "/linemnt/trlnoanswer"
 		) );
		
		$tropo->on ( array (
				"event" => "hangup",
				"next" => "/linemnt/hangup" 
		) );
		$tropo->on ( array (
				"event" => "continue",
				"next" => "/linemnt/conference" 
		) );
		$tropo->conference ( null, $confOptions );
		$tropo->renderJSON ();
		// call student
		$sessionModel = new Application_Model_Session ();
		$row = $sessionModel->getSessionForCallBySessionId ( $row ["inx"] );
		$paramArr = array ();
		$paramArr ["sessionid"] = $row ["inx"];
		$paramArr ["stuphone"] = $row ["b_phone"];
		$paramArr ["stuid"] = $row ["b_inx"];
		$paramArr ["mntphone"] = $row ["c_phone"];
		$paramArr ["mntid"] = $row ["c_inx"];
		$paramArr ["trlphone"] = $row ["d_phone"];
		$paramArr ["trlid"] = $row ["d_inx"];
		$troposervice = new TropoService ();
		$troposervice->callstu ( $paramArr );
		$this->logger->logInfo ( "LinemntController", "welcomeAction", "call student phone:--- " . $paramArr ["stuphone"] );
	}
	
	public function stunoanswerAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo ( "LinemntController", "stunoanswerAction", "student not answer : " . $tropoJson );
		$tropo = new Tropo ();
		$tropo->say("http://165.225.149.30/sound/03_no_answer_student.mp3");
		//$tropo->say("student not answer, the conference is end");
		$tropo->renderJSON ();
	
	}
	
	public function trlnoanswerAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo ( "LinemntController", "nofityAction", "translator not answer: " . $tropoJson );
		$tropo = new Tropo ();
		$tropo->say("http://165.225.149.30/sound/03_no_answer_translator.mp3");
		//$tropo->say("translator not answer, the conference is end");
		$tropo->renderJSON ();
	
	}
	
	public function conferenceAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo ( "LinemntController", "conferenceAction", "conferenceAction message: " . $tropoJson );
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
		sleep ( 5 );
		$this->logger->logInfo ( "LinemntController", "incompleteAction", "sleep 5 seconds " );
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
		$paramArr ["notify"] = $session->getParameters ( "notify" );
		return $paramArr;
	}
	protected function sendNotification($callinx = null) {
		$this->logger->logInfo ( "LinemntController", "sendNotification", "send email to 3 part, cause  instructor" );

		//更新session状态为cancel
		$sessionModel = new Application_Model_Session ();
		$sessionModel->changeSessionToCancel($callinx);

		$callModel = new Application_Model_Call ();
		$call = $callModel->find ( $callinx )->current ();
		
		$instructorModel = new Application_Model_Instructor ();
		$instructorEmail = $instructorModel->find ( $call ["party1Inx"] )->current ()->email;
		
		$studentModel = new Application_Model_Student ();
		$studentEmail = $studentModel->find ( $call ["party2Inx"] )->current ()->email;
		
		$translatorModel = new Application_Model_Translator ();
		$translatorEmail = "";
		if ($call ["party3Inx"] != null) {
			$translatorEmail = $translatorModel->find ( $call ["party3Inx"] )->current ()->email;
		}
		$mailcontent = "先生が三回でも電話に出なかったため、補習授業をキャンセルした。";
		$emailService = new EmailService ();
		$emailService->sendEmail ( $studentEmail, null, null, $mailcontent, "先生が三回でも電話に出なかったため、補習授業をキャンセルした。" );
		$emailService->sendEmail ( null, $instructorEmail, null, $mailcontent, "先生が三回でも電話に出なかったため、補習授業をキャンセルした。" );
		$emailService->sendEmail ( null, null, $translatorEmail, $mailcontent, "先生が三回でも電話に出なかったため、補習授業をキャンセルした。" );
	}
}

