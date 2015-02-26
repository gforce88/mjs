<?php
require_once 'log/LoggerFactory.php';
require_once 'phpmailer/class.phpmailer.php';
class EmailService {
	private $logger;
	public function __construct() {
		$this->logger = LoggerFactory::getSysLogger ();
	}
	public function sendEmail($studentEmail, $instructorEmail, $translatorEmail, $mailcontent, $subject) {
		$loginfo = $studentEmail . "-" . $instructorEmail . "-" . $translatorEmail;
		$this->logger->logInfo ( "EmailService", "sendEmail", $loginfo );
		try {
			$filename = APPLICATION_PATH . "/configs/application.ini";
			$config = new Zend_Config_Ini ( $filename, 'production' );
			$mail = new PHPMailer ( true ); // New instance, with exceptions
			$body = file_get_contents ( APPLICATION_PATH . '/configs/mail_groupfail.html' );
			$body = preg_replace ( '/mailcontent/', $mailcontent, $body ); // Strip
			$mail->IsSMTP (); // tell the class to use SMTP
			$mail->CharSet = "utf-8";
			$mail->SMTPAuth = true; // enable SMTP authentication
			$mail->Port = $config->mail->port; // set the SMTP server port
			$mail->Host = $config->mail->host; // SMTP server
			$mail->Username = $config->mail->username; // SMTP server username
			$mail->Password = $config->mail->password; // SMTP server password
			$mail->IsSendmail (); // tell the class to use Sendmail
			$mail->AddReplyTo ( $mail->Username, $mail->Username );
			$mail->SetFrom ( $mail->Username, $mail->Username );
			if ($studentEmail != null) {
				$mail->AddAddress ( $studentEmail );
			}
			if ($instructorEmail != null) {
				$mail->AddAddress ( $instructorEmail );
			}
			if ($translatorEmail != null) {
				$mail->AddAddress ( $translatorEmail );
			}
			$mail->Subject = "=?utf-8?B?" . base64_encode ( $subject ) . "?=";
			$mail->AltBody = "To view the message, please use an HTML compatible email viewer!"; // optional,
			$mail->WordWrap = 80; // set word wrap
			$mail->MsgHTML ( $body );
			$mail->IsHTML ( true ); // send as HTML
			$mail->Send ();
		} catch ( phpmailerException $e ) {
		}
	}
}
