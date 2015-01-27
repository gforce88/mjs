<?php
require_once 'log/LoggerFactory.php';
class LoginController extends Zend_Controller_Action {
	private $logger;
	public function init() {
		/* Initialize action controller here */
		$this->logger = LoggerFactory::getSysLogger();
		$translate = new Zend_Translate ( 'ini', APPLICATION_PATH . "/language/English.ini", 'US' );
		$translate->addTranslation ( APPLICATION_PATH . "/language/Jp.ini", 'JP' );
		$filename = APPLICATION_PATH . "/configs/application.ini";
		$config = new Zend_Config_Ini ( $filename, 'production' );
		$translate->setlocale ( $config->mjs->locale );
		$this->view->translate = $translate;
	}
	public function indexAction() {
		$this->logger->logInfo("LoginController", "indexAction", "  get in to the login index page");
		if ($this->getRequest ()->isPost ()) {
			$username = $this->_request->username;
			$password = $this->_request->password;
			
			if ($username == null && $password == null) {
				$this->view->loginMessage = $this->view->translate->_ ( "usernamepasswordmessage" );
			} else {
				// 取得默认数据库适配器
				$db = Zend_Db_Table::getDefaultAdapter ();
				// 实例化一个auth适配器
				$authAdapter = new Zend_Auth_Adapter_DbTable ( $db, 'user', 'username', 'password' );
				// 设置用户名 密码
				$authAdapter->setIdentity ( $username );
				$authAdapter->setCredential ( $password );
				// 认证
				$result = $authAdapter->authenticate ();
				if ($result->isValid ()) {
					$auth = Zend_Auth::getInstance ();
					// 存储用户信息
					$storge = $auth->getStorage ();
					$storge->write ( $authAdapter->getResultRowObject ( array (
							'inx',
							'username' 
					) ) );
					$this->_redirect ( "/land" );
				} else {
					$this->view->loginMessage = $this->view->translate->_ ( "upnotvalid" );
				}
			}
		}
		$locale = new Zend_Locale ();
		$this->view->locale = $locale;
	}
	public function logoutAction() {
		$authAdapter = Zend_Auth::getInstance ();
		$authAdapter->clearIdentity ();
		$this->redirect ( "/" );
	}
}





