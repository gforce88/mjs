<?php
require_once 'log/LoggerFactory.php';
require_once 'tropo/tropo.class.php';
require_once 'util/HttpUtil.php';
require_once 'service/TropoService.php';
class TimerController extends Zend_Controller_Action {
	protected $logger;
	public function init() {
		$this->logger = LoggerFactory::getSysLogger ();
		$this->httpUtil = new HttpUtil ();
		$this->setting = Zend_Registry::get ( "TROPO_SETTING" );
		$this->_helper->viewRenderer->setNeverRender ();
	}
	
	// 会议电话
	// 每10分钟run一次电话到点拨号job调用，当前时间开始前1分钟和后10分钟之内的session
	public function indexAction() {
		$sessionModel = new Application_Model_Session ();
		$start = date ( 'Y-m-d H:i:s', strtotime ( " -1 ,mins" ) );
		$end = date ( "Y-m-d H:i:s", strtotime ( " +10 mins" ) );
		echo ceil((strtotime ( " 2015-02-04 12:00:00" )-time())/60);
// 		echo $start;
// 		echo "<br/>";
// 		echo $end;
// 		echo "<br/>";
// 		echo strtotime ( $start );
// 		echo "<br/>";
// 		echo strtotime ( " +20 seconds" );
// 		echo "<br/>";
		echo date_default_timezone_get ();
		$sessions = $sessionModel->getWillStartingSession ( $start, $end );
		$this->logger->logInfo ( "TimerController", "indexAction", "start:" . $start . " end:" . $end );
		foreach ( $sessions as $row ) {
			$troposervice = new TropoService ();
			$paramArr = array ();
			$paramArr ["sessionid"] = $row ["inx"];
			$paramArr ["stuphone"] = $row ["b_phone"];
			$paramArr ["stuid"] = $row ["b_inx"];
			$paramArr ["mntphone"] = $row ["c_phone"];
			$paramArr ["mntid"] = $row ["c_inx"];
			$paramArr ["trlphone"] = $row ["d_phone"];
			$paramArr ["trlid"] = $row ["d_inx"];
			
			// 调用打电话应用并创建call记录
			$callModel = new Application_Model_Call ();
			$existRow = $callModel->find ( $row ["inx"] )->current ();
			if ($existRow) {
			} else {
				$callModel->createCall ( $paramArr );
				$troposervice->callmnt ( $paramArr );
			}
			$this->logger->logInfo ( "TimerController", "indexAction", "it is the session call time" . $start );
		}
	}
	
	// 提示session
	// 每10秒钟run一次电话到点拨号job调用，当前时间开始和后10秒之内的session
	public function reMindAction() {
		$sessionModel = new Application_Model_Session ();
		$start = date ( 'Y-m-d H:i:s');
		$end = date ( "Y-m-d H:i:s", strtotime ( " +10 seconds" ) );
		$sessions = $sessionModel->getWillStartingSession ( $start, $end );
		foreach ( $sessions as $row ) {
			$troposervice = new TropoService ();
			$paramArr = array ();
			$paramArr ["sessionid"] = $row ["inx"];
			$paramArr ["stuphone"] = $row ["b_phone"];
			$paramArr ["stuid"] = $row ["b_inx"];
			$paramArr ["mntphone"] = $row ["c_phone"];
			$paramArr ["mntid"] = $row ["c_inx"];
			$paramArr ["trlphone"] = $row ["d_phone"];
			$paramArr ["trlid"] = $row ["d_inx"];
			//通过nofify参数来标记是否是remind
			$paramArr ["notify"] = "1";
			
			// 调用打电话应用,只做提示 所以不创建call记录
			$callModel = new Application_Model_Call ();
			$existRow = $callModel->find ( $row ["inx"] )->current ();
			if ($existRow) {
			} else {
// 				$callModel->createCall ( $paramArr );
				$troposervice->callmnt ( $paramArr );
			}
			$this->logger->logInfo ( "TimerController", "indexAction", "it is the session call time" . $start );
			echo "call instructor ";
		}
		echo "time is :" . $start;
	}
}

