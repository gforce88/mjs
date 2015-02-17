<?php
require_once 'log/LoggerFactory.php';
class Application_Model_Session extends Zend_Db_Table_Abstract {
	protected $_name = 'tutorsessions';
	public function getSessionList() {
		$sql = "select 
		b.inx b_inx,b.firstName b_firstName,b.lastName b_lastName,b.phone b_phone,
		c.inx c_inx,c.firstName c_firstName,c.lastName c_lastName,c.phone c_phone,
		d.inx d_inx,d.firstName d_firstName,d.lastName d_lastName,d.phone d_phone,
		a.inx inx,a.scheduleStartTime a_scheduleStartTime,a.scheduleEndTime a_scheduleEndTime,a.actualEndTime a_actualEndTime,
		a.iscancelled iscancelled 
		from tutorsessions a 
		left join students b on a.studentInx = b.inx
		left join instructors c on a.instructorInx = c.inx
		left join translators d on a.translatorInx = d.inx
		order by a.scheduleStartTime desc";
		return $this->getAdapter ()->query ( $sql, array () );
	}
	public function getSessionForCallBySessionId($sessionId = null) {
		$sql = "select
		b.inx b_inx,b.firstName b_firstName,b.lastName b_lastName,b.phone b_phone,
		c.inx c_inx,c.firstName c_firstName,c.lastName c_lastName,c.phone c_phone,
		d.inx d_inx,d.firstName d_firstName,d.lastName d_lastName,d.phone d_phone,
		a.inx inx,a.scheduleStartTime a_scheduleStartTime,a.scheduleEndTime a_scheduleEndTime,a.actualEndTime a_actualEndTime
		from tutorsessions a
		left join students b on a.studentInx = b.inx
		left join instructors c on a.instructorInx = c.inx
		left join translators d on a.translatorInx = d.inx
		where a.inx = " . $sessionId . "
		order by a.inx";
		return $this->getAdapter ()->fetchRow ( $sql, array () );
	}
	public function getReportSessionList($startDate = null, $endDate = null) {
		$sql = "select
		b.inx b_inx,b.firstName b_firstName,b.lastName b_lastName,b.phone b_phone,
		c.inx c_inx,c.firstName c_firstName,c.lastName c_lastName,c.phone c_phone,
		d.inx d_inx,d.firstName d_firstName,d.lastName d_lastName,d.phone d_phone,
		a.inx inx,a.scheduleStartTime a_scheduleStartTime,a.scheduleEndTime a_scheduleEndTime,a.actualEndTime a_actualEndTime
		from tutorsessions a
		left join students b on a.studentInx = b.inx
		left join instructors c on a.instructorInx = c.inx
		left join translators d on a.translatorInx = d.inx
		where a.actualEndTime IS NOT NULL
		and a.actualEndTime > '" . $startDate . "' 
		and a.actualEndTime < '" . $endDate . "' 
		order by b.inx";
		$logger = LoggerFactory::getSysLogger ();
		$logger->logInfo ( "Application_Model_Session", "getReportSessionList", "sql is : " . $sql );
		return $this->getAdapter ()->query ( $sql, array () );
	}
	public function getWillStartingSession($startDate = null, $endDate = null) {
		$sql = "select
		b.inx b_inx,b.firstName b_firstName,b.lastName b_lastName,b.phone b_phone,
		c.inx c_inx,c.firstName c_firstName,c.lastName c_lastName,c.phone c_phone,
		d.inx d_inx,d.firstName d_firstName,d.lastName d_lastName,d.phone d_phone,
		a.inx inx,a.scheduleStartTime a_scheduleStartTime,a.scheduleEndTime a_scheduleEndTime,a.actualEndTime a_actualEndTime
		from tutorsessions a
		left join students b on a.studentInx = b.inx
		left join instructors c on a.instructorInx = c.inx
		left join translators d on a.translatorInx = d.inx
		where a.actualEndTime IS NULL
		and a.scheduleStartTime between '" . $startDate . "' and '" . $endDate . "'
		order by b.inx";
		$logger = LoggerFactory::getSysLogger ();
		$logger->logInfo ( "Application_Model_Session", "getWillStartingSession", "sql is : " . $sql );
		return $this->getAdapter ()->query ( $sql, array () );
	}
	public function deleteSession($inx = null) {
		$row = $this->find ( $inx )->current ();
		if ($row) {
			$row->delete ();
		}
	}
	public function createSession($params = array(), $instructorId = null, $translatorId = null) {
		$newrow = $this->createRow ();
		$newrow->studentInx = $params ["studentId"];
		$newrow->instructorInx = $instructorId;
		if ($translatorId != null) {
			$newrow->translatorInx = $translatorId;
		}
		$scheduleStartDate = $params ["startDate"];
		$scheduleStartTime = $params ["startTime"];
		$newrow->scheduleStartTime = date ( 'Y-m-d H:i:s', strtotime ( $scheduleStartDate . " " . $scheduleStartTime ) );
		$newrow->scheduleEndTime = date ( 'Y-m-d H:i:s', strtotime ( "+" . $params ['dur'] . " Minute", strtotime ( $scheduleStartDate . " " . $scheduleStartTime ) ) );
		// $newrow->actualEndTime = date ( 'Y-m-d H:i:s', strtotime ( "+" .
		// $params ['dur'] . " Minute", strtotime ( $scheduleStartDate . " " .
		// $scheduleStartTime ) ) );
		$newrow->timezone = date_default_timezone_get ();
		$newrow->save ();
		return $newrow->inx;
	}
	public function updateSession($params = array(), $instructorId = null, $translatorId = null) {
		$inx = $params ["inx"];
		$newrow = $this->find ( $inx )->current ();
		$newrow->studentInx = $params ["studentId"];
		$newrow->instructorInx = $instructorId;
		$newrow->translatorInx = $translatorId;
		$scheduleStartDate = $params ["startDate"];
		$scheduleStartTime = $params ["startTime"];
		$newrow->scheduleStartTime = date ( 'Y-m-d H:i:s', strtotime ( $scheduleStartDate . " " . $scheduleStartTime ) );
		$newrow->scheduleEndTime = date ( 'Y-m-d H:i:s', strtotime ( "+" . $params ['dur'] . " Minute", strtotime ( $scheduleStartDate . " " . $scheduleStartTime ) ) );
		// $newrow->actualEndTime = date ( 'Y-m-d H:i:s', strtotime ( "+" .
		// $params ['dur'] . " Minute", strtotime ( $scheduleStartDate . " " .
		// $scheduleStartTime ) ) );
		$newrow->timezone = date_default_timezone_get ();
		$newrow->save ();
		return $newrow->inx;
	}
	// 课程结束更新结束时间
	public function finishSession($sessionId = null) {
		$row = $this->find ( $sessionId )->current ();
		$row->actualEndTime = date ( 'Y-m-d H:i:s' );
		$row->save ();
		return $row->inx;
	}
	public function findSessionsWhenCallEnd($inx, $type) {
		$select = $this->select ();
		$start = date ( "Y-m-01 00:00:00" );
		$end = strtotime ( " +1 months" );
		$end = date ( "Y-m-01 00:00:00", $end );
		if ($type == "mnt") {
			$select->where ( ' instructorInx = ?', $inx );
		} else {
			$select->where ( ' translatorInx = ?', $inx );
		}
		$select->where ( ' actualEndTime > ?', $start );
		$select->where ( ' actualEndTime < ?', $end );
		$row = $this->fetchAll ( $select );
		if ($row) {
			return $row;
		} else {
			return null;
		}
	}

	//电话3次不接改session状态为cancel
	public function changeSessionToCancel($sessionId = null){
		$row = $this->find ( $sessionId )->current ();
		$row->iscancelled = 1;
		$row->save();
		return $row->inx;

	}
}

