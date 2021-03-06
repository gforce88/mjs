<?php
require_once 'log/LoggerFactory.php';
require_once 'phpmailer/class.phpmailer.php';
class ReportController extends Zend_Controller_Action {
	private $logger;
	public function init() {
		$translate = new Zend_Translate ( 'ini', APPLICATION_PATH . "/language/English.ini", 'US' );
		$translate->addTranslation ( APPLICATION_PATH . "/language/Jp.ini", 'JP' );
		$filename = APPLICATION_PATH . "/configs/application.ini";
		$config = new Zend_Config_Ini ( $filename, 'production' );
		$translate->setlocale ( $config->mjs->locale );
		$this->logger = LoggerFactory::getSysLogger ();
		$this->view->translate = $translate;
		$this->_helper->viewRenderer->setNeverRender ();
	}
	public function indexAction() {
		// $currentdate = date("Y-m-d", strtotime("2015-01-18 -7 days"));
		$startDate = date ( "Y-m-d", strtotime ( " -1 weeks" ) );
		$endDate = date ( "Y-m-d", strtotime ( " +1 days" ) );
		echo $startDate . "<br/>";
		echo $endDate;
		$this->logger->logInfo ( "ReportController", "indexAction", $startDate . "---" . $endDate );
		$sessionModel = new Application_Model_Session ();
		$sessionList = $sessionModel->getReportSessionList ( $startDate, $endDate );
		if (count ( $sessionList ) > 0) {
			$userModel = new Application_Model_User ();
			$userEmail = $userModel->getUserEmail ();
			$this->sendReportEmail ( $userEmail, $sessionList );
		}
		// $userModel = new Application_Model_User();
		// $userEmail = $userModel->getUserEmail();
		// $this->view->resultmessage = $userEmail;
	}


	public function resetAction() {
		$studentModel = new Application_Model_Student();
		//$tmm = $studentModel->findTmmConfigueDefault();
		//$tmmvalue = "";
		//foreach ($tmm as $row){
		//	$tmmvalue=$row["d_value"];
		//}
		$tmm = "";
		$updatestudents = $studentModel->findNeedUpdateStudent();
		foreach ($updatestudents as $stuinx){
			$stu = $studentModel->find($stuinx)->current();
			//$stu->totalMonthlyMins = $tmmvalue;
			$stu->minsRemaining = $stu->totalMonthlyMins;
			$stu->save();
			$this->logger->logInfo ( "ReportController", "resetAction", "student accountId----".$stu->inx."--- reseted " );
		}
	}


	
	// ????????????????????????
	private function sendReportEmail($userEmail, $sessionList) {
		$loginfo = "report will be sent to " . $userEmail;
		$mailcontent = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body><table><tr><td>Student Id</td><td>Student Name</td><td>Student Phone</td>
    			<td>Mentor Name</td><td>Mentor Phone</td><td>Translator Name</td><td>Translator Phone</td>
    			<td>Session Id</td><td>Session Date</td><td>Session Duration</td></tr>";
		foreach ( $sessionList as $row ) {
			$d1 = strtotime ( $row ["a_actualEndTime"] );
			$d2 = strtotime ( $row ["a_scheduleStartTime"] );
			$sessionduration = ceil(($d1 - $d2) / 60);
			$mailcontent = $mailcontent . "<tr><td>" . $row ["b_inx"] . "</td><td>" . $row ["b_firstName"] . $row ["b_lastName"] . "</td><td>" . $row ["b_phone"] . "</td><td>" . $row ["c_firstName"] . $row ["c_lastName"] . "</td><td>" . $row ["c_phone"] . "</td><td>" . $row ["d_firstName"] . $row ["d_lastName"] . "</td><td>" . $row ["d_phone"] . "</td><td>" . $row ["inx"] . "</td><td>" . $row ["a_scheduleStartTime"] . "</td><td>" . $sessionduration . "</td></tr>";
		}
		$mailcontent = $mailcontent . "</table></body></html>";
		$this->logger->logInfo ( "SessionController", "sendReportEmail", $mailcontent );
		
		try {
			$filename = APPLICATION_PATH . "/configs/application.ini";
			$config = new Zend_Config_Ini ( $filename, 'production' );
			$mail = new PHPMailer ( true ); // New instance, with exceptions
			                                // enabled
			$body = file_get_contents ( APPLICATION_PATH . '/configs/mail_report.html' );
			// $body = preg_replace ( '/\\\\/', '', $body ); // Strip
			$body = preg_replace ( '/mailcontent/', $mailcontent, $body ); // Strip
			                                                               // backslashes
			$mail->IsSMTP (); // tell the class to use SMTP
			$mail->SMTPAuth = true; // enable SMTP authentication
			$mail->Port = $config->mail->port; // set the SMTP server port
			$mail->Host = $config->mail->host; // SMTP server
			$mail->Username = $config->mail->username; // SMTP server username
			$mail->Password = $config->mail->password; // SMTP server password
			
			$mail->CharSet = "utf-8";
			$mail->SMTPAuth = true;     // turn on SMTP authentication
				
			$mail->SMTPSecure = 'ssl';
			
			//$mail->IsSendmail (); // tell the class to use Sendmail
			
			$mail->AddReplyTo ( $mail->Username, $mail->Username );
			$mail->SetFrom ( $mail->Username, $mail->Username );
			
			$mail->AddAddress ($userEmail );
			$mail->AddAddress($config->admin->first);
			$mail->AddAddress($config->admin->second);
			$mail->AddAddress($config->admin->third);
			$mail->AddAddress("daihuan@topmoon.com.cn");
			
			$mail->Subject = " Session Report ";
			
			$mail->AltBody = "To view the message, please use an HTML compatible email viewer!"; // optional,
			                                                                                     // comment
			                                                                                     // out
			                                                                                     // and
			                                                                                     // test
			$mail->WordWrap = 80; // set word wrap
			
			$mail->MsgHTML ( $body );
			
			$mail->IsHTML ( true ); // send as HTML
			
			$mail->Send ();
			// echo 'Message has been sent.';
		} catch ( phpmailerException $e ) {
			// echo $e->errorMessage ();
		}
		$this->logger->logInfo ( "SessionController", "sendReportEmail", "mail has send for report " );
	}
}

