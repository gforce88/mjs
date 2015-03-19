<?php
require_once 'log/LoggerFactory.php';
require_once 'tropo/tropo.class.php';
require_once 'util/HttpUtil.php';
require_once 'service/TropoService.php';
require_once 'service/EmailService.php';
class LinestuController extends Zend_Controller_Action {
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
		$this->logger->logInfo ( "LinestuController", "indexAction", "recieve from tropo server message is: " . $tropoJson );
		$session = new Session ( $tropoJson );
		$this->logger->logInfo ( "LinestuController", "indexAction", "session  is: " . $session->getId () );
		$params = $this->initSessionParameters ( $session );
		
		$callModel = new Application_Model_Call ();
		$callModel->updateStuCallSession ( $params ["sessionid"], $session->getId () );
		
		if ($callModel->checkStuCallTimes ( $params ) > 3) {
			$this->logger->logInfo ( "LinestuController", "indexAction", "student didn't answer the call for 3times" );
			// 邮件通知
			$this->sendNotification ( $params ["sessionid"] );
			// 发消息给instructor的会议，告诉学生没有参加。
			$troposervice = new TropoService ();
			$call = $callModel->find ( $params ["sessionid"] )->current ();
			$troposervice->stunoanswerRemind ( $call->party1SessionId );
		} else {
			$this->logger->logInfo ( "LinestuController", "indexAction", "call student:" . $params ["stuphone"] );
			$tropo = new Tropo ();
			$tropo->call ( $params ["stuphone"] );
			// 电话接通后
			if ($params ["notify"] == "1") { // 判断是否是提示电话
				$tropo->on ( array (
						"event" => "continue",
						"next" =>$this->app["ctx"].  "/linestu/notify" 
				) );
			} else {
				// 此处判断是否有翻译，根据是否有翻译发出不同的提示
				if ($params ["trlid"] != null) {
					$tropo->on ( array (
							"event" => "continue",
							"next" =>$this->app["ctx"].  "/linestu/welcome",
							"say" => $this->app["hostip"].$this->app["ctx"]."/sound/02_call_translator.mp3" 
					) );
				} else {
					$tropo->on ( array (
							"event" => "continue",
							"next" =>$this->app["ctx"].  "/linestu/welcome",
							"say" => $this->app["hostip"].$this->app["ctx"]."/sound/joining_call.mp3" 
					) );
				}
			}
			// 电话未拨通
			$tropo->on ( array (
					"event" => "incomplete",
					"next" =>$this->app["ctx"].  "/linestu/incomplete" 
			) );
			// tropo应用发生错误
			$tropo->on ( array (
					"event" => "error",
					"next" =>$this->app["ctx"].  "/linestu/error" 
			) );
			
			$tropo->renderJSON ();
		}
	}
	public function notifyAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo ( "LinestuController", "nofityAction", "notify message: " . $tropoJson );
		$tropo = new Tropo ();
		$tropo->say ( $this->app["hostip"].$this->app["ctx"]."/sound/remind_call.mp3" );
		$tropo->hangup ();
		$tropo->renderJSON ();
	}
	public function hangupAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo ( "LinestuController", "hangupAction", "student hangup message: " . $tropoJson );
		$result = new Result ( $tropoJson );
		$callModel = new Application_Model_Call ();
		$call = $callModel->groupEnd ( $result->getSessionId () );
		// 更新session实际结束时间
		$sessionModel = new Application_Model_Session ();
		$sessionModel->finishSession ( $call ["inx"] );
		$session = $sessionModel->find ( $call ["inx"] )->current ();
		// 更新学生记录的时间
		$uesdmins = ceil ( (strtotime ( $session ["actualEndTime"] ) - strtotime ( $session ["scheduleStartTime"] )) / 60 );
		$studentModel = new Application_Model_Student ();
		$studentModel->updateMinsRemaining ( $call ["party2Inx"], $uesdmins );
		
		// 发送session完成的邮件通知
		$this->sendEmailWhenCallEndToStu ( $call ["inx"] );
		sleep ( 5 );
		$this->sendEmailWhenCallEndToMnt ( $call ["inx"] );
		sleep ( 5 );
		$this->sendEmailWhenCallEndToTrl ( $call ["inx"] );
		
		$this->logger->logInfo ( "LinestuController", "hangupAction", "group session is over as student is hangup " );
	}
	public function welcomeAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo ( "LinestuController", "continueAction", "student continue message: " . $tropoJson );
		$result = new Result ( $tropoJson );
		$callModel = new Application_Model_Call ();
		$row = $callModel->findSessionIdByStuCallsessionIdAndRecordTime ( $result->getSessionId () );
		$this->logger->logInfo ( "LinestuController", "welcomeAction", "session id: " . $result->getSessionId () );
		$tropo = new Tropo ();
		$confOptions = array (
				"name" => "conference",
				"id" => "mjsconf" . $row ["inx"],
				"mute" => false
				,
				"allowSignals" => array (
					"trlnoanswer",
					"hangup",
					"continue"
				)				
		);
		$tropo->on ( array (
				"event" => "hangup",
				"next" =>$this->app["ctx"].  "/linestu/hangup" 
		) );
		$tropo->on ( array (
				"event" => "trlnoanswer",
				"next" =>$this->app["ctx"].  "/linestu/trlnoanswer" 
		) );
		$tropo->on ( array (
				"event" => "continue",
				"next" =>$this->app["ctx"].  "/linestu/conference"
		) );
		$tropo->conference ( null, $confOptions );
		$tropo->renderJSON ();
		// call translator
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
		if ($paramArr ["trlid"] != null) {
			$troposervice = new TropoService ();
			$troposervice->calltrl ( $paramArr );
			$this->logger->logInfo ( "LinestuController", "welcomeAction", "call translator phone:--- " . $paramArr ["trlphone"] );
		} else {
			$callModel->groupStart ( $row ["inx"] );
		}
	}
	
	public function conferenceAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo ( "LinestuController", "conferenceAction", "conferenceAction message: " . $tropoJson );
	}
	
	public function trlnoanswerAction() {
		$tropoJson = file_get_contents ( "php://input" );
		$this->logger->logInfo ( "LinestuController", "trlnoanswerAction", "translator not answer: " . $tropoJson );
		$tropo = new Tropo ();
		$tropo->say ( $this->app["hostip"].$this->app["ctx"]."/sound/04_no_answer_translator.mp3" );
		// $tropo->say("translator not answer, the conference is end");
		$tropo->renderJSON ();
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
		$paramArr ["notify"] = $session->getParameters ( "notify" );
		return $paramArr;
	}
	protected function sendNotification($callinx = null) {
		$this->logger->logInfo ( "LinestuController", "sendNotification", "send email to 3 part, cause  instructor" );
		// 更新session状态为cancel
		$sessionModel = new Application_Model_Session ();
		$sessionModel->changeSessionToCancel ( $callinx );
		$sessionStartTime = $sessionModel->find($callinx)->current()->scheduleStartTime;
		$callModel = new Application_Model_Call ();
		$call = $callModel->find ( $callinx )->current ();
		
		$instructorModel = new Application_Model_Instructor ();
		$instructorEmail = $instructorModel->find ( $call ["party1Inx"] )->current ()->email;
		
		$studentModel = new Application_Model_Student ();
		$student = $studentModel->find ( $call ["party2Inx"] )->current ();
		$studentEmail = $student->email;
		$studentName = $student->firstName." ".$student->lastName;
		$translatorModel = new Application_Model_Translator ();
		$translatorEmail = "";
		if ($call ["party3Inx"] != null) {
			$translatorEmail = $translatorModel->find ( $call ["party3Inx"] )->current ()->email;
		}
		$mailcontent = "MJSメンタリングサービスです。<p/>
				お世話になっております。<p/>
				<p/>
				ご登録いただいていた下記予約につき、参加予定者が揃わなかったため<p/>
				自動的にキャンセルとなりました。<p/>
				必要であれば再度の予約申込みをお願いいたします。<p/>
				
				予約日時：".$sessionStartTime."<p/>
				不参加者： 生徒<p/><p/>
				
				以上です。";
		$emailService = new EmailService ();
		$emailService->sendEmail ( $studentEmail, null, null, $mailcontent, "メンタリングキャンセルのお知らせ" );
		$emailService->sendEmail ( null, $instructorEmail, null, $mailcontent, "メンタリングキャンセルのお知らせ" );
		$emailService->sendEmail ( null, null, $translatorEmail, $mailcontent, "メンタリングキャンセルのお知らせ" );
	}
	
	private function sendEmailWhenCallEndToStu($sessioninx) {
		$this->logger->logInfo ( "LinestuController", "sendEmailWhenCallEndToStu", "sendEmailWhenCallEndToStu" );
		// $sessioninx=37;
		$subject = "メンタリング完了のお知らせ";
		
		$sessionModel = new Application_Model_Session ();
		$tempsession = $sessionModel->find ( $sessioninx )->current ();
		$studentModel = new Application_Model_Student();
		$student = $studentModel->find ( $tempsession->studentInx )->current ();
		$studentEmail = $student->email;
		// 查找学生当月参加的session
		$sessions = $sessionModel->findSessionsWhenCallEnd ( $tempsession->studentInx, "stu" );
		$mailcontent = "";
		$totalduration = 0;
		$mailcontent = $mailcontent . "<p>";
		$mailcontent = $mailcontent . "生徒名:" . $student->firstName . " " . $student->lastName . "<br/>";
		$mailcontent = $mailcontent ."----<br/>";
		foreach ( $sessions as $session ) {
			$d2 = strtotime ( $session->scheduleStartTime );
			$d3 = strtotime ( $session->actualEndTime );
			$duration = ceil ( ($d3 - $d2) / 60 );
			//$mailcontent = $mailcontent . "生徒名:" . $student->firstName . " " . $student->lastName . "<br/>----<br/>ご利用時間:" . $duration . " 分<br/>";
			$mailcontent = $mailcontent . "実施日時: ".$session->scheduleStartTime.", ご利用時間: " . $duration . " 分<br/>";
			$totalduration += $duration;
		}
		$mailcontent = $mailcontent . "<br/>当月ご利用時間 : " . $totalduration . " 分";
		$mailcontent = $mailcontent . "</p>";
		$mailcontent = $mailcontent ."<p>以上です。</p>";
		
		$body = file_get_contents ( APPLICATION_PATH . '/configs/mail_session_finish_mnt.html' );
		$body = preg_replace ( '/{content}/', $mailcontent, $body ); // Strip
		
		try {
			$filename = APPLICATION_PATH . "/configs/application.ini";
			$config = new Zend_Config_Ini ( $filename, 'production' );
			$mail = new PHPMailer ( true ); // New instance, with exceptions
			$mail->IsSMTP (); // tell the class to use SMTP
			$mail->CharSet = "utf-8";
			$mail->SMTPAuth = true; // enable SMTP authentication
			$mail->Port = $config->mail->port; // set the SMTP server port
			$mail->Host = $config->mail->host; // SMTP server
			$mail->Username = $config->mail->username; // SMTP server username
			$mail->Password = $config->mail->password; // SMTP server password
			$mail->SMTPSecure = "ssl";
			//$mail->IsSendmail (); // tell the class to use Sendmail
			$mail->AddReplyTo ( $mail->Username, $mail->Username );
			$mail->SetFrom ( $mail->Username, $mail->Username );
			$mail->AddAddress ( $studentEmail );
			$mail->Subject = "=?utf-8?B?" . base64_encode ( $subject ) . "?=";
			$mail->WordWrap = 80; // set word wrap
			$mail->MsgHTML ( $body );
			$mail->IsHTML ( true ); // send as HTML
			$mail->Send ();
		} catch ( phpmailerException $e ) {
			$this->logger->logInfo ( "LinestuController", "sendEmailWhenCallEndToMnt", "err in send email TO MNT" );
		}
	}
	private function sendEmailWhenCallEndToMnt($sessioninx = null) {
		$this->logger->logInfo ( "LinestuController", "sendEmailWhenCallEndToMnt", "sendEmailWhenCallEndToMnt" );
		$subject = "メンタリング完了のお知らせ";
		
		$sessionModel = new Application_Model_Session ();
		$tempsession = $sessionModel->find ( $sessioninx )->current ();
		$instructorModel = new Application_Model_Instructor ();
		$instructor = $instructorModel->find ( $tempsession->instructorInx )->current ();
		$instructorEmail = $instructor->email;
		// 查找老师当月参加的session
		$sessions = $sessionModel->findSessionsWhenCallEnd ( $tempsession->instructorInx, "mnt" );
		$mailcontent = "";
		$studentModel = new Application_Model_Student ();
		$totalduration = 0;
		// Updated email body per request by JP team - GE
		$mailcontent = $mailcontent . "<p>";
		$mailcontent = $mailcontent . "生徒名:" . $student->firstName . " " . $student->lastName . "<br/>";
		$mailcontent = $mailcontent ."----<br/>";
		foreach ( $sessions as $session ) {
			$student = $studentModel->find ( $session->studentInx )->current ();
			$d2 = strtotime ( $session->scheduleStartTime );
			$d3 = strtotime ( $session->actualEndTime );
			$duration = ceil ( ($d3 - $d2) / 60 );
			//$mailcontent = $mailcontent . "生徒名:" . $student->firstName . " " . $student->lastName . "<br/>----<br/>ご利用時間:" . $duration . " 分<br/>";
			$mailcontent = $mailcontent . "実施日時: ".$session->scheduleStartTime.", ご利用時間: " . $duration . " 分<br/>";
			$totalduration += $duration;
		}
		$mailcontent = $mailcontent . "<br/>当月ご利用時間 : " . $totalduration . " 分";
		$mailcontent = $mailcontent . "</p>";
		$mailcontent = $mailcontent ."<p>以上です。</p>";
		
		$body = file_get_contents ( APPLICATION_PATH . '/configs/mail_session_finish_mnt.html' );
		$body = preg_replace ( '/{content}/', $mailcontent, $body ); // Strip
		
		try {
			$filename = APPLICATION_PATH . "/configs/application.ini";
			$config = new Zend_Config_Ini ( $filename, 'production' );
			$mail = new PHPMailer ( true ); // New instance, with exceptions
			$mail->IsSMTP (); // tell the class to use SMTP
			$mail->CharSet = "utf-8";
			$mail->SMTPAuth = true; // enable SMTP authentication
			$mail->Port = $config->mail->port; // set the SMTP server port
			$mail->Host = $config->mail->host; // SMTP server
			$mail->Username = $config->mail->username; // SMTP server username
			$mail->Password = $config->mail->password; // SMTP server password
			$mail->SMTPSecure = "ssl";
			//$mail->IsSendmail (); // tell the class to use Sendmail
			$mail->AddReplyTo ( $mail->Username, $mail->Username );
			$mail->SetFrom ( $mail->Username, $mail->Username );
			$mail->AddAddress ( $instructorEmail );
			$mail->Subject = "=?utf-8?B?" . base64_encode ( $subject ) . "?=";
			$mail->WordWrap = 80; // set word wrap
			$mail->MsgHTML ( $body );
			$mail->IsHTML ( true ); // send as HTML
			$mail->Send ();
		} catch ( phpmailerException $e ) {
			$this->logger->logInfo ( "LinestuController", "sendEmailWhenCallEndToMnt", "err in send email TO MNT" );
		}
		
		// echo $body;
	}
	private function sendEmailWhenCallEndToTrl($sessioninx = null) {
		$this->logger->logInfo ( "LinestuController", "sendEmailWhenCallEndToTrl", "sendEmailWhenCallEndToTrl" );
		$subject = "メンタリング完了のお知らせ";
		$sessionModel = new Application_Model_Session ();
		$tempsession = $sessionModel->find ( $sessioninx )->current ();
		if ($tempsession->translatorInx == null) {
			return;
		}
		$translatorModel = new Application_Model_Translator ();
		$translator = $translatorModel->find ( $tempsession->translatorInx )->current ();
		$translatorEmail = $translator->email;
		
		// 查找翻译当月参加的session
		$sessions = $sessionModel->findSessionsWhenCallEnd ( $tempsession->translatorInx, "trl" );
		
		$this->logger->logInfo ( "LinestuController", "ttAction", count ( $sessions ) );
		$mailcontent = "";
		$studentModel = new Application_Model_Student ();
		$totalduration = 0;
		foreach ( $sessions as $session ) {
			$student = $studentModel->find ( $session->studentInx )->current ();
			$d2 = strtotime ( $session->scheduleStartTime );
			$d3 = strtotime ( $session->actualEndTime );
			$duration = ceil ( ($d3 - $d2) / 60 );
			$mailcontent = $mailcontent . "生徒名:" . $student->firstName . " " . $student->lastName . "---- ご利用時間:" . $duration . " 分<br/>";
			$totalduration += $duration;
		}
		$mailcontent = $mailcontent . "<br/><br/> 当月ご利用時間   :" . $totalduration . " 分<br/> 以上です。";
		
		$body = file_get_contents ( APPLICATION_PATH . '/configs/mail_session_finish_mnt.html' );
		$body = preg_replace ( '/{content}/', $mailcontent, $body ); // Strip
		
		try {
			$filename = APPLICATION_PATH . "/configs/application.ini";
			$config = new Zend_Config_Ini ( $filename, 'production' );
			$mail = new PHPMailer ( true ); // New instance, with exceptions
			$mail->IsSMTP (); // tell the class to use SMTP
			$mail->CharSet = "utf-8";
			$mail->SMTPAuth = true; // enable SMTP authentication
			$mail->Port = $config->mail->port; // set the SMTP server port
			$mail->Host = $config->mail->host; // SMTP server
			$mail->Username = $config->mail->username; // SMTP server username
			$mail->Password = $config->mail->password; // SMTP server password
			$mail->SMTPSecure = "ssl";
			//$mail->IsSendmail (); // tell the class to use Sendmail
			$mail->AddReplyTo ( $mail->Username, $mail->Username );
			$mail->SetFrom ( $mail->Username, $mail->Username );
			$mail->AddAddress ( $translatorEmail );
			$mail->Subject = "=?utf-8?B?" . base64_encode ( $subject ) . "?=";
			$mail->WordWrap = 80; // set word wrap
			$mail->MsgHTML ( $body );
			$mail->IsHTML ( true ); // send as HTML
			$mail->Send ();
		} catch ( phpmailerException $e ) {
		}
	}
}

