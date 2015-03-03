<?php
require_once 'log/LoggerFactory.php';
class StudentController extends Zend_Controller_Action {
	private $logger;
	public function init() {
		$translate = new Zend_Translate ( 'ini', APPLICATION_PATH . "/language/English.ini", 'US' );
		$translate->addTranslation ( APPLICATION_PATH . "/language/Jp.ini", 'JP' );
		$filename=APPLICATION_PATH ."/configs/application.ini";
		$config = new Zend_Config_Ini($filename,'production');
		$translate->setlocale ( $config->mjs->locale );
		$this->view->translate = $translate;
		$this->app = Zend_Registry::get ( "APP_SETTING" );
		$this->logger = LoggerFactory::getSysLogger();
	}
	public function indexAction() {
		$student = new Application_Model_Student ();
		$studentlist = $student->getStudents ();
		$this->view->studentlist = $studentlist;
	}
	public function editAction() {
		if ($this->getRequest ()->isPost ()) {
			$params = $this->_request->getPost ();
			$this->view->params = $params;
			$student = new Application_Model_Student ();
			$result = $student->updateStudent ( $params );
			if ($result != null) {
				$this->view->resultmessage = $this->view->translate->_("stumessageupdate");
				$this->_redirect ( "/student/index" );
			}
		} else {
			$inx = $this->_getParam ( "inx" );
			$student = new Application_Model_Student ();
			$studentlist = $student->find ( $inx );
			$this->view->studentlist = $studentlist [0];
		}
	}
	public function createAction() {
		// 创建新学生
		if ($this->getRequest ()->isPost ()) {
			$params = $this->_request->getPost ();
			$this->view->params = $params;
			$student = new Application_Model_Student ();
			$result = $student->createStudent ( $params );
			if ($result != null) {
				$this->view->resultmessage = $this->view->translate->_("stumessagecreate");
			}
		}
		// 进入到创建页
		$tmmvalue = new Application_Model_Configvalue ();
		$tmm = $tmmvalue->getDefaultValueByKey ( 'tmm' );
		$this->view->tmm = $tmm;
	}
	public function findstujsonAction() {
		$inx = $this->_getParam ( "inx" );
		$student = new Application_Model_Student ();
		$studentlist = $student->getValidStudent ( $inx );
		
		if (count($studentlist)>0) {
			$this->view->studentlist = $studentlist [0];
			$data = $studentlist [0];
		}else{
			$data = array("err"=>0);
		}
		$this->_helper->json ( $data, true, false, true );
	}
}





