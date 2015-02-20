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
	// 每分钟run一次电话到点拨号job调用，查到当前时间 前后5秒之内的所有session
	public function indexAction() {
		$sessionModel = new Application_Model_Session ();
		$start = date ( 'Y-m-d H:i:s', strtotime ( " -5 seconds" ));
		$end = date ( "Y-m-d H:i:s", strtotime ( " +5 seconds" ) );
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
			$b_acctStatus = $row["b_acctStatus"];
			if($b_acctStatus==0){//如果学生状态为suspend,删除session
				$sessionmodel->deleteSession ( $row ["inx"] );
			}
			// 调用打电话应用并创建call记录
			$callModel = new Application_Model_Call ();
			$existRow = $callModel->find ( $row ["inx"] )->current ();
			if ($existRow) {
			} else {
				echo "start session at ";
				$callModel->createCall ( $paramArr );
				$troposervice->callmnt ( $paramArr );
			}
			$this->logger->logInfo ( "TimerController", "indexAction", "it is the session call time" . $start );
		}
		echo "index crontime :".$start."---".$end."\n";
	}
	
	// 提示session
	// 每分钟run一次 查找10分钟之后的session
	public function remindAction() {
		$sessionModel = new Application_Model_Session ();
		$start = date ( 'Y-m-d H:i:s' , strtotime ( " +9 mins +55 seconds" ) );
		$end = date ( "Y-m-d H:i:s", strtotime ( " +10 mins +5 seconds" ) );
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
				echo "remind started : ";
				$troposervice->callmnt ( $paramArr );
				$troposervice->callstu ( $paramArr );
				$troposervice->calltrl ( $paramArr );
			}
			$this->logger->logInfo ( "TimerController", "indexAction", "it is the session call time" . $start );
			echo "call instructor ";
		}
		echo "remind crontime is :" . $start." and ". $end."\n";
	}
	
	public function ttAction(){
		echo $start = date("Y-m-01 00:00:00");
		echo $end = strtotime(" +1 months");
		echo date("Y-m-01 00:00:00",$end);
		echo "<br />";
		echo strtotime($time);
		echo "<br />";
		
		
		
		echo "<br />";
		echo "<br />";
	}
}

