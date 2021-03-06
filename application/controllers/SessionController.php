<?php
require_once 'log/LoggerFactory.php';
require_once 'tropo/tropo.class.php';
require_once 'util/HttpUtil.php';
require_once 'service/TropoService.php';
require_once 'service/EmailService.php';
class SessionController extends Zend_Controller_Action {
	private $logger;
	public function init() {
		$translate = new Zend_Translate ( 'ini', APPLICATION_PATH . "/language/English.ini", 'US' );
		$translate->addTranslation ( APPLICATION_PATH . "/language/Jp.ini", 'JP' );
		$filename = APPLICATION_PATH . "/configs/application.ini";
		$config = new Zend_Config_Ini ( $filename, 'production' );
		$this->app = Zend_Registry::get ( "APP_SETTING" );
		$translate->setlocale ( $config->mjs->locale );
		$this->logger = LoggerFactory::getSysLogger ();
		$this->view->translate = $translate;
	}
	public function indexAction() {
		$this->logger->logInfo ( "SessionController", "indexAction", " list Session start---------" );
		$sessionmodel = new Application_Model_Session ();
		$sessions = $sessionmodel->getSessionList ();
		$this->logger->logInfo ( "SessionController", "indexAction", " list Session start---2222------" . count ( $sessions ) );
		$this->view->sessionlist = $sessions;
		$this->logger->logInfo ( "SessionController", "indexAction", " list Session end -----333-----" );
	}
	
	// 创建session
	public function createAction() {
		$this->logger->logInfo ( "SessionController", "createAction", " enter create Session" );
		if ($this->getRequest ()->isPost ()) {
			$params = $this->_request->getPost ();
			$scheduleStartDate = $params ["startDate"];
			$scheduleStartTime = $params ["startTime"];
			$inputTime = strtotime ( $scheduleStartDate . " " . $scheduleStartTime );
			$current = time ();
			if ($inputTime > $current) {
				if ($this->checkStudentRemainMin ( $params )) {
					$instructorModel = new Application_Model_Instructor ();
					$instructorId = $instructorModel->saveOrupdateInstructor ( $params );
					
					$translatorId = "";
					$translatorEmail = "";
					$translatorModel = new Application_Model_Translator ();
					if (($params ['tFirstName'] != "") && ($params ['tLastName'] != "")) {
						$translatorId = $translatorModel->saveOrupdateTranslator ( $params );
					}
					$sessionModel = new Application_Model_Session ();
					$sessionInx = $sessionModel->createSession ( $params, $instructorId, $translatorId );
					
					$instructorEmail = $instructorModel->find ( $instructorId )->current ()->email;
					if ($translatorId != "") {
						$translatorEmail = $translatorModel->find ( $translatorId )->current ()->email;
					}
					$studentModel = new Application_Model_Student ();
					$studentEmail = $studentModel->find ( $params ["studentId"] )->current ()->email;
					$session = $sessionModel->find ( $sessionInx )->current ();
					
					$instructor = $instructorModel->find ( $instructorId )->current ();
					$student = $studentModel->find ( $params ["studentId"] )->current ();
					$translator = "";
					if ($translatorId != "") {
						$translator = $translatorModel->find ( $translatorId )->current ();
					}
					
					// $mailcontent = "session start date is: " .
					// $session->scheduleStartTime . " session end date is :" .
					// $session->scheduleEndTime;
					$mailcontent = "MJSメンタリングサービスです。<p/>
					
					お世話になっております。<p/>

					メンタリングサービスに予約が登録されたので、お知らせいたします。<p/>
					予約時間になりましたらサービスより電話がありますので、<p/>
					ご準備をよろしくお願いいたします。<p/>
					
					
					生徒名： " . $student->firstName . " " . $student->lastName . " <p/>
					
					メンター名： " . $instructor->firstName . " " . $instructor->lastName . " <p/>
					
					通訳名： " . $translator->firstName . " " . $translator->lastName . " <p/>
					
					予約日時：" . $session->scheduleStartTime . "<p/>
					
					以上です。";
					$emailService = new EmailService ();
					$emailService->sendEmail ( $studentEmail, null, null, $mailcontent, "メンタリングサービス予約登録完了のお知らせ" );
					$emailService->sendEmail ( null, $instructorEmail, null, $mailcontent, "メンタリングサービス予約登録完了のお知らせ" );
					$emailService->sendEmail ( null, null, $translatorEmail, $mailcontent, "メンタリングサービス予約登録完了のお知らせ" );
					// 如果session创建时间在10分钟之内 立刻开始拨号
					if ($inputTime < strtotime ( " +10 mins" )) {
						$sessionModel = new Application_Model_Session ();
						$row = $sessionModel->getSessionForCallBySessionId ( $sessionInx );
						$paramArr = array ();
						$paramArr ["sessionid"] = $row ["inx"];
						$paramArr ["stuphone"] = $row ["b_phone"];
						$paramArr ["stuid"] = $row ["b_inx"];
						$paramArr ["mntphone"] = $row ["c_phone"];
						$paramArr ["mntid"] = $row ["c_inx"];
						$paramArr ["trlphone"] = $row ["d_phone"];
						$paramArr ["trlid"] = $row ["d_inx"];
						$paramArr ["notify"] = "1";
						$troposervice = new TropoService ();
						// 调用打电话应用并创建call记录
						$callModel = new Application_Model_Call ();
						$existRow = $callModel->find ( $row ["inx"] )->current ();
						if ($existRow) {
						} else {
							$troposervice->callmnt ( $paramArr );
							$troposervice->callstu ( $paramArr );
							$troposervice->calltrl ( $paramArr );
						}
						$this->logger->logInfo ( "SessionController", "createAction", " session created with in 10 mins call instructor" );
					}
					
					$this->view->resultmessage = $this->view->translate->_ ( "sescreated" );
				} else {
					$this->view->resultmessage = $this->view->translate->_ ( "stuminnotenough" );
				}
			} else {
				$this->view->resultmessage = $this->view->translate->_ ( "pasttimenotallow" );
			}
		}
	}
	// 修改session
	public function editAction() {
		$this->logger->logInfo ( "SessionController", "editAction", " enter edit Session" );
		if ($this->getRequest ()->isPost ()) {
			$params = $this->_request->getPost ();
			
			$callModel = new Application_Model_Call ();
			$existRow = $callModel->find ( $params ["inx"] )->current ();
			// 如果拨过号则不能修改
			if ($existRow) {
				$this->view->resultmessage = $this->view->translate->_ ( "nomodifystartedsession" );
			} else {
				$scheduleStartDate = $params ["startDate"];
				$scheduleStartTime = $params ["startTime"];
				$inputTime = strtotime ( $scheduleStartDate . " " . $scheduleStartTime );
				$instructorOldEmail = "";
				$translatorOldEmail = "";
				$current = time ();
				if ($inputTime > $current) {
					if ($this->checkStudentRemainMin ( $params ) && $this->checkSessionStatus ( $params )) {
						// 获取原来的session
						$sessionModel = new Application_Model_Session ();
						$oldSession = $sessionModel->find ( $inx = $params ["inx"] )->current ();
						
						// 获取原来的老师email
						$instructorModel = new Application_Model_Instructor ();
						$instructorOld = $instructorModel->find ( $oldSession->instructorInx )->current ();
						$instructorOldEmail = $instructorOld->email;
						
						// 获取原来的翻译email
						$translatorModel = new Application_Model_Translator ();
						$translatorOldEmail = null;
						if ($oldSession->translatorInx != null) {
							$translatorOld = $translatorModel->find ( $oldSession->translatorInx )->current ();
							$translatorOldEmail = $translatorOld->email;
						}
						
						// 根据名字更新或增加老师
						$instructorId = $instructorModel->saveOrupdateInstructor ( $params );
						
						// 根据名字更新或增加翻译
						$translatorId = null;
						if (($params ['tFirstName'] != "") && ($params ['tLastName'] != "")) {
							$translatorId = $translatorModel->saveOrupdateTranslator ( $params );
						}
						
						$oldSessionDate = $oldSession->scheduleStartTime;
						$sessionInx = $sessionModel->updateSession ( $params, $instructorId, $translatorId );
						$session = $sessionModel->find ( $sessionInx )->current ();
						
						// 获取新的老师邮箱
						$instructorEmail = $instructorModel->find ( $instructorId )->current ()->email;
						
						// 获取新的翻译邮箱
						$translatorEmail = "";
						if ($translatorId != "") {
							$translatorEmail = $translatorModel->find ( $translatorId )->current ()->email;
						}
						// 获取学生邮箱
						$studentModel = new Application_Model_Student ();
						$studentEmail = $studentModel->find ( $params ["studentId"] )->current ()->email;
						
						$this->logger->logInfo ( "SessionController", "editAction", " studentId:" . $params ["studentId"] );
						$session = $sessionModel->find ( $sessionInx )->current ();
						
						$instructor = $instructorModel->find ( $instructorId )->current ();
						$student = $studentModel->find ( $params ["studentId"] )->current ();
						$translator = "";
						if ($translatorId != "") {
							$translator = $translatorModel->find ( $translatorId )->current ();
						}
						$this->logger->logInfo ( "SessionController", "editAction", " firstName:" . $student->firstName );
						// $mailcontent = "session start date is: " .
						// $session->scheduleStartTime . " session end date is
						// :" .
						// $session->scheduleEndTime;
						$mailcontent = "MJSメンタリングサービスです。<p/>
						お世話になっております。<p/>

						ご予約いただいていた、下記予約の変更が完了したことをお知らせいたします。<p/>
				
					変更前予約日時：" . $oldSessionDate . " <p/>
				
					変更後予約日時：" . $session->scheduleStartTime . " <p/>
				
					生徒名： " . $student->firstName . "  " . $student->lastName . " <p/>
			
					メンター名： " . $instructor->firstName . " " . $instructor->lastName . " <p/>
			
					通訳名：" . $translator->firstName . " " . $translator->lastName . " <p/>
				
					以上です。";
						
						$emailService = new EmailService ();
						$emailService->sendEmail ( $studentEmail, null, null, $mailcontent, "メンタリング予約時間変更完了" );
						$emailService->sendEmail ( null, $instructorEmail, null, $mailcontent, "メンタリング予約時間変更完了" );
						$emailService->sendEmail ( null, null, $translatorEmail, $mailcontent, "メンタリング予約時間変更完了" );
						$this->logger->logInfo ( "SessionController", "editAction", " instructorOldEmail:" . $instructorOldEmail );
						$this->logger->logInfo ( "SessionController", "editAction", " instructorEmail:" . $instructorEmail );
						$this->logger->logInfo ( "SessionController", "editAction", " translatorOldEmail:" . $translatorOldEmail );
						$this->logger->logInfo ( "SessionController", "editAction", " translatorEmail:" . $translatorEmail );
						$this->logger->logInfo ( "SessionController", "editAction", " translatorEmail:" . ($translatorOldEmail != $translatorEmail) );
						
						if ($instructorOldEmail != $instructorEmail) {
							$mailcontent = "お疲れ様です,<p/>
		
							以前手配した" . $session->scheduleStartTime . " 補習授業を取消しました<p/>
					
							ありがとうございます。";
							
							// $studentEmail=null;
							// $translatorOldEmail=null;
							$emailService->sendEmail ( null, $instructorOldEmail, null, $mailcontent, "補習授業時間を取消しました" );
						}
						if ($translatorOldEmail != null) {
							if ($translatorOldEmail != $translatorEmail) {
								$mailcontent = "お疲れ様です,<p/>
								
								以前手配した" . $session->scheduleStartTime . " 補習授業を取消しました<p/>
				
								ありがとうございます。";
								// $studentEmail=null;
								// $instructorOldEmail=null;
								$emailService->sendEmail ( null, null, $translatorOldEmail, $mailcontent, "補習授業時間を取消しました" );
							}
						}
						// 如果session创建时间在10分钟之内 立刻开始提示
						if ($inputTime < strtotime ( " +10 mins" )) {
							$sessionModel = new Application_Model_Session ();
							$row = $sessionModel->getSessionForCallBySessionId ( $sessionInx );
							$paramArr = array ();
							$paramArr ["sessionid"] = $row ["inx"];
							$paramArr ["stuphone"] = $row ["b_phone"];
							$paramArr ["stuid"] = $row ["b_inx"];
							$paramArr ["mntphone"] = $row ["c_phone"];
							$paramArr ["mntid"] = $row ["c_inx"];
							$paramArr ["trlphone"] = $row ["d_phone"];
							$paramArr ["trlid"] = $row ["d_inx"];
							$paramArr ["notify"] = "1";
							$troposervice = new TropoService ();
							// 调用打电话应用并创建call记录
							$callModel = new Application_Model_Call ();
							$existRow = $callModel->find ( $row ["inx"] )->current ();
							if ($existRow) {
							} else {
								$troposervice->callmnt ( $paramArr );
								$troposervice->callstu ( $paramArr );
								$troposervice->calltrl ( $paramArr );
							}
							$this->logger->logInfo ( "SessionController", "editAction", " session edit with in 10 mins call instructor" );
						}
						$this->view->resultmessage = $this->view->translate->_ ( "sesupdate" );
						$this->_redirect ( "/session/index" );
					} else {
						if (! $this->checkSessionStatus ( $params )) {
							$this->view->resultmessage = $this->view->translate->_ ( "sessioncannotupdate" );
						} else {
							$this->view->resultmessage = $this->view->translate->_ ( "stuminnotenough" );
						}
					}
				} else {
					$this->view->resultmessage = $this->view->translate->_ ( "pasttimenotallow" );
				}
			}
		} else {
			// find studentinfo
			$studentInx = $this->_getParam ( "studentinx" );
			$student = new Application_Model_Student ();
			$studentlist = $student->find ( $studentInx );
			$this->view->studentlist = $studentlist [0];
			// find instructorinfo
			$instructorInx = $this->_getParam ( "instructorinx" );
			$instructor = new Application_Model_Instructor ();
			$instructorlist = $instructor->find ( $instructorInx );
			$this->view->instructorlist = $instructorlist [0];
			// find translatorinfo
			$translatorInx = $this->_getParam ( "translatorinx" );
			if ($translatorInx != null) {
				$translator = new Application_Model_Translator ();
				$translatorlist = $translator->find ( $translatorInx );
				$this->view->translatorlist = $translatorlist [0];
			}
			// find sessioninfo
			$sessionInx = $this->_getParam ( "sessioninx" );
			$session = new Application_Model_Session ();
			$sessionlist = $session->find ( $sessionInx );
			$this->view->session = $sessionlist [0];
		}
	}
	public function checkSessionCanDelete($inx) {
		$sessionmodel = new Application_Model_Session ();
		$session = $sessionmodel->find ( $inx )->current ();
		$tag = strtotime ( $session->scheduleStartTime ) > time ();
		$this->logger->logInfo ( "SessionController", "checkSessionCanDelete", " tag:" . $inx . $session->scheduleStartTime );
		return $tag;
	}
	
	// 删除session
	public function deleteAction() {
		$sessioninx = $this->getParam ( "inx" );
		$this->_helper->viewRenderer->setNeverRender ();
		$data = array ();
		if (! $this->checkSessionCanDelete ( $sessioninx )) {
			$data = array (
					"err" => 0 
			);
		} else {
			$sessionmodel = new Application_Model_Session ();
			$session = $sessionmodel->find ( $sessioninx )->current ();
			$studentinx = $session->studentInx;
			$instructorinx = $session->instructorInx;
			$translatorinx = $session->translatorInx;
			$studentModel = new Application_Model_Student ();
			$studentEmail = $studentModel->find ( $studentinx )->current ()->email;
			$instructorModel = new Application_Model_Instructor ();
			$instructorEmail = $instructorModel->find ( $instructorinx )->current ()->email;
			$translatorEmail = "";
			if ($translatorinx != null) {
				$translatorModel = new Application_Model_Translator ();
				$translatorEmail = $translatorModel->find ( $translatorinx )->current ()->email;
			}
			
			$mailcontent = "MJSメンタリングサービスです。<p/>お世話になっております。<p/>

				ご予約いただいていた、下記予約のキャンセルが完了したことをお知らせいたします。
		
				予約日時：" . $session->scheduleStartTime . " <p/>
		
				以上です。";
			$emailService = new EmailService ();
			$emailService->sendEmail ( $studentEmail, null, null, $mailcontent, "メンタリング予約キャンセルのお知らせ" );
			$emailService->sendEmail ( null, $instructorEmail, null, $mailcontent, "メンタリング予約キャンセルのお知らせ" );
			$emailService->sendEmail ( null, null, $translatorEmail, $mailcontent, "メンタリング予約キャンセルのお知らせ" );
			
			$sessionmodel->deleteSession ( $sessioninx );
			// $this->redirect ( "/session" );
			$data = array (
					"success" => 0 
			);
		}
		$this->_helper->json ( $data, true, false, true );
	}
	
	// 判断学生的剩余时间是否足够
	private function checkStudentRemainMin($params = array()) {
		$sessionDate = $params ["startDate"];
		$studentModel = new Application_Model_Student ();
		$row = $studentModel->find ( $params ["studentId"] )->current ();
		$startdate = $row->membershipStartDate;
		$dur = $row->membershipDur;
		$a = strtotime ( "+" . $dur . " months -1 days", strtotime ( $startdate ) );
		$b = strtotime ( $sessionDate );
		
		$this->logger->logInfo ( "SessionController", "checkStudentRemainMin","a--->". date ( 'Y-m-d H:i:s',$a) );
		$this->logger->logInfo ( "SessionController", "checkStudentRemainMin","b--->".date ( 'Y-m-d H:i:s',$b) );
		
		$remainMin = $row->minsRemaining;
		$this->logger->logInfo ( "SessionController", "checkStudentRemainMin", "remainMIn: " . $remainMin . " dur:" . $params ['dur'] );
		return ($remainMin > $params ["dur"]) && ($a >= $b);
	}
	
	// 判断已经结束的session 和小于当前时间的session都不能改
	private function checkSessionStatus($params = array()) {
		$sessionModel = new Application_Model_Session ();
		$session = $sessionModel->find ( $params ["inx"] )->current ();
		if ($session->actualEndTime) {
			return false;
		}
		if (strtotime ( $session->scheduleStartTime ) < time ()) {
			return false;
		}
		return true;
	}
}







