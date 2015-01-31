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
	public function indexAction() {
		$sessionModel = new Application_Model_Session ();
		$start = date ( 'Y-m-d H:i:s' );
		// $start = date ( "Y-m-d H:i:s", strtotime ( "2015-01-25 11:00:00" ) );
		$end = date ( "Y-m-d H:i:s", strtotime ( " +10 mins" ) );
		echo $start;
		echo "<br/>";
		echo $end;
		echo "<br/>";
		echo date_default_timezone_get ();
		$sessions = $sessionModel->getWillStartingSession ( $start, $end );
		$this->logger->logInfo ( "TimerController", "indexAction", "start:".$start." end:".$end );
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
			
			//调用打电话应用并创建call记录
			$troposervice->callmnt( $paramArr );
			$callModel = new Application_Model_Call ();
			$callModel->createCall($paramArr);
			$this->logger->logInfo ( "TimerController", "indexAction", "it is the session call time".$start );
		}
	}
}

