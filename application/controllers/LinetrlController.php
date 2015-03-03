<?php
require_once 'log/LoggerFactory.php';
require_once 'tropo/tropo.class.php';
require_once 'util/HttpUtil.php';
require_once 'service/EmailService.php';
require_once 'service/TropoService.php';
class LinetrlController extends Zend_Controller_Action {
	protected $logger;
	public function init() {
		$this->logger = LoggerFactory::getTropoLogger ();
		$this->httpUtil = new HttpUtil ();
		$this->setting = Zend_Registry::get ( "TROPO_SETTING" );
		$this->app = Zend_Registry::get ( "APP_SETTING" );
		$this->_helper->viewRenderer->setNeverRender ();
	}
	public function indexAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo ( "LinetrlController", "indexAction", "recieve from tropo server message is: " . $tropoJson );
		$session = new Session ( $tropoJson );
		$this->logger->logInfo ( "LinestuController", "indexAction", "session  is: " . $session->getId () );
		$params = $this->initSessionParameters ( $session );
		
		$callModel = new Application_Model_Call ();
		$callModel->updateTrlCallSession ( $params ["sessionid"], $session->getId () );
		
		if ($callModel->checkTrlCallTimes ( $params ) > 3) {
			$this->logger->logInfo ( "LinetrlController", "indexAction", "translator didn't answer the call for 3 times" );
			// 邮件通知
			$this->sendNotification ( $params ["sessionid"] );
			
			// 发消息给instructor的会议，告诉学生没有参加。
			$troposervice = new TropoService ();
			$call = $callModel->find ( $params ["sessionid"] )->current ();
			
			$troposervice->trlnoanswerRemind ( $call->party1SessionId, $call->party2SessionId );
		} else {
			$this->logger->logInfo ( "LinetrlController", "indexAction", "call translator:" . $params ["trlphone"] );
			$tropo = new Tropo ();
			$tropo->call ( $params ["trlphone"] );
			// 电话接通后
			if ($params ["notify"] == "1") { // 判断是否是提示电话
				$tropo->on ( array (
						"event" => "continue",
						"next" => $this->app ["ctx"] . "/linetrl/notify" 
				) );
			} else {
				$tropo->on ( array (
						"event" => "continue",
						"next" => $this->app ["ctx"] . "/linetrl/welcome",
						"say" => $this->app["hostip"].$this->app["ctx"]."/sound/joining_call.mp3" 
				) );
			}
			// 电话未拨通
			$tropo->on ( array (
					"event" => "incomplete",
					"next" => $this->app ["ctx"] . "/linetrl/incomplete" 
			) );
			// tropo应用发生错误
			$tropo->on ( array (
					"event" => "error",
					"next" => $this->app ["ctx"] . "/linetrl/error" 
			) );
			$tropo->renderJSON ();
		}
	}
	public function notifyAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo ( "LinetrlController", "nofityAction", "notify message: " . $tropoJson );
		$tropo = new Tropo ();
		$tropo->say ( $this->app["hostip"].$this->app["ctx"]."/sound/remind_call.mp3" );
		$tropo->hangup ();
		$tropo->renderJSON ();
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
		$row = $callModel->findSessionIdByTrlCallsessionIdAndRecordTime ( $result->getSessionId () );
		$this->logger->logInfo ( "LinetrlController", "welcomeAction", "session id: " . $result->getSessionId () );
		// 记录会议开始时间
		$callModel->groupStart ( $row ["inx"] );
		
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
				"next" => $this->app ["ctx"] . "/linetrl/hangup" 
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
		$this->logger->logInfo ( "LinetrlController", "sendNotification", "send email to 3 part, cause  instructor" );
		
		// 更新session状态为cancel
		$sessionModel = new Application_Model_Session ();
		$sessionModel->changeSessionToCancel ( $callinx );
		
		$sessionStartTime = $sessionModel->find($callinx)->current()->scheduleStartTime;
		$callModel = new Application_Model_Call ();
		$call = $callModel->find ( $callinx )->current ();
		
		$instructorModel = new Application_Model_Instructor ();
		$instructorEmail = $instructorModel->find ( $call ["party1Inx"] )->current ()->email;
		
		$studentModel = new Application_Model_Student ();
		$studentEmail = $studentModel->find ( $call ["party2Inx"] )->current ()->email;
		
		$translatorModel = new Application_Model_Translator ();
		$translatorEmail = "";
		$translatorName = "";
		if ($call ["party3Inx"] != null) {
			$translator = $translatorModel->find ( $call ["party3Inx"] )->current ();
			$translatorEmail = $translator->email;
			$translatorName = $translator->firstName." ".$translator->lastName;
		}
		$mailcontent = "MJSメンタリングサービスです。<p/>
				お世話になっております。<p/>
				<p/>
				ご登録いただいていた下記予約につき、参加予定者が揃わなかったため<p/>
				自動的にキャンセルとなりました。<p/>
				必要であれば再度の予約申込みをお願いいたします。<p/>
				
				予約日時：<<".$sessionStartTime.">><p/>
				不参加者： <<通訳>><p/><p/>
				
				以上です。";
		$emailService = new EmailService ();
		$emailService->sendEmail ( $studentEmail, null, null, $mailcontent, "メンタリングキャンセルのお知らせ" );
		$emailService->sendEmail ( null, $instructorEmail, null, $mailcontent, "メンタリングキャンセルのお知らせ" );
		$emailService->sendEmail ( null, null, $translatorEmail, $mailcontent, "メンタリングキャンセルのお知らせ" );
	}
}

