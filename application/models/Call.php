<?php
require_once 'log/LoggerFactory.php';
class Application_Model_Call extends Zend_Db_Table_Abstract {
	protected $_name = 'calls';
	public function createCall($callData) {
		$row = $this->createRow ();
		if (count ( $callData ) > 0) {
			$row->party1Inx = $callData ["mntid"];
			$row->party1CallRes = "1";
			$row->party2Inx = $callData ["stuid"];
			$row->party2CallRes = "1";
			if($callData ["trlid"]!=null){
				$row->party3Inx = $callData ["trlid"];
				$row->party3CallRes = 1;
			}
			$row->inx = $callData ["sessionid"];
			$row->save ();
			return $row->inx;
		} else {
			return null;
		}
	}
	public function updateMntCallSession($callInx = null, $party1SessionId = null) {
		$row = $this->find ( $callInx )->current ();
		if ($row) {
			$row->party1SessionId = $party1SessionId;
			$row->save ();
			return $row->inx;
		}
	}
	public function updateStuCallSession($callInx = null, $party2SessionId = null) {
		$row = $this->find ( $callInx )->current ();
		if ($row) {
			$row->party2SessionId = $party2SessionId;
			$row->save ();
			return $row->inx;
		}
	}
	public function updateTrlCallSession($callInx = null, $party3SessionId = null) {
		$row = $this->find ( $callInx )->current ();
		if ($row) {
			$row->party3SessionId = $party3SessionId;
			$row->save ();
			return $row->inx;
		}
	}
	
	// 检查老师 重拨次数
	public function checkMntCallTimes($callData) {
		$logger = LoggerFactory::getSysLogger ();
		$logger->logInfo ( "Application_Model_Call", "checkMntCallTimes", "session id" . $callData ["sessionid"] );
		$row = $this->find ( $callData ["sessionid"] )->current();
		$logger->logInfo ( "Application_Model_Call", "checkMntCallTimes", "party1CallRes :" .  $row ["party1CallRes"] );
		return $row ["party1CallRes"];
	}
	// 检查学生 重拨次数
	public function checkStuCallTimes($callData) {
		$row = $this->find ( $callData ["sessionid"] )->current();
		return $row ["party2CallRes"];
	}
	// 检查翻译 重拨次数
	public function checkTrlCallTimes($callData) {
		$row = $this->find ( $callData ["sessionid"] )->current();
		return $row ["party3CallRes"];
	}
	
	//老师拨号次数加1
	public function findSessionIdByMntCallsessionIdAndUpdateCallTimes($callSessionId = null) {
		$select = $this->select ();
		$select->where ( 'party1SessionId = ?', $callSessionId );
		$row = $this->fetchRow($select);
		$row->party1CallRes = $row["party1CallRes"]+1;
		$row->save();
		return $row;
	}
	
	//学生拨号次数加1
	public function findSessionIdByStuCallsessionIdAndUpdateCallTimes($callSessionId = null) {
		$select = $this->select ();
		$select->where ( 'party2SessionId = ?', $callSessionId );
		$row = $this->fetchRow($select);
		$row->party2CallRes = $row["party2CallRes"]+1;
		$row->save();
		return $row;
	}
	
	//翻译拨号次数加1
	public function findSessionIdByTrlCallsessionIdAndUpdateCallTimes($callSessionId = null) {
		$select = $this->select ();
		$select->where ( 'party3SessionId = ?', $callSessionId );
		$row = $this->fetchRow($select);
		$row->party3CallRes = $row["party3CallRes"]+1;
		$row->save();
		return $row;
	}
}

