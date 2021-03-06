<?php
class Application_Model_Student extends Zend_Db_Table_Abstract {
	protected $_name = 'students';
	public function getStudents($where = array(), $order = null, $limit = null) {
		$select = $this->select ();
		if (count ( $where ) > 0) {
			foreach ( $where as $key => $value ) {
				$select->where ( $key . ' = ?', $value );
			}
		}
		if ($order) {
			$select->order ( $order );
		}
		if ($limit) {
			$select->limit ( $limit );
		}
		$row = $this->fetchAll ( $select );
		if ($row) {
			return $row;
		} else {
			return null;
		}
	}
	
	public function getValidStudent($inx) {
		$select = $this->select ();
		$select->where ( 'inx = ?', $inx );
		$select->where ( 'acctStatus = ?', '1' );
		$row = $this->fetchAll ( $select );
		if ($row) {
			return $row;
		} else {
			return null;
		}
	}
	
	public function customQuery() {
		$sql = "select * from students where inx=?";
		return $this->getAdapter ()->query ( $sql, array (
				'1' 
		) );
	}
	
	// 创建学生
	public function createStudent($studentData) {
		$row = $this->createRow ();
		if (count ( $studentData ) > 0) {
			$row->firstName = $studentData["firstName"];
			$row->lastName = $studentData["lastName"];
			$row->email = $studentData["email"];
			$row->phone = $studentData["phone"];
			$row->acctCreateDate = date ( 'Y-m-d H:i:s' );
			$row->membershipStartDate = date('Y-m-d',strtotime($studentData["startdate"]));
			$row->membershipDur = $studentData["membershipDur"];
			$row->totalMonthlyMins = $studentData["totalMonthlyMins"];
			$row->acctStatus = 1;
			$row->minsRemaining = $studentData ['totalMonthlyMins'];
			$row->save ();
			return $row->inx;
		} else {
			return null;
		}
	}
	
	// 更新学生
	public function updateStudent($studentData) {
		$row = $this->find($studentData ['inx'])->current();
		if (count ( $studentData ) > 0) {
			$row->firstName = $studentData["firstName"];
			$row->lastName = $studentData["lastName"];
			$row->email = $studentData["email"];
			$row->phone = $studentData["phone"];
			$row->membershipStartDate = date('Y-m-d H:i:s',strtotime($studentData["startdate"]));
			$row->membershipDur = $studentData["membershipDur"];
			$row->minsRemaining = $studentData["totalMonthlyMins"];
			$row->acctStatus = 1;
			$row->acctStatus = $studentData ['acctStatus'];
			$row->save ();
			return $row->inx;
		} else {
			return null;
		}
	}
	
	//更新minsRemaining
	public function updateMinsRemaining($inx,$usedmins){
		$row = $this->find($inx)->current();
		$row->minsRemaining=$row->minsRemaining-$usedmins;
		$row->save();
		return $row->inx;
	}
	
	
	public function findNeedUpdateStudent(){
		$sql = "select inx
			from students t
			where period_diff(date_format(now(),'%Y%m'),date_format(t.membershipStartDate,'%Y%m')) <=t.membershipDur 
				and t.acctStatus = 1";
		return $this->getAdapter ()->query ( $sql, array () );
	}
	public function findTmmConfigueDefault(){
		$sql = "select d_value from configue_default where d_key='tmm'";
		return $this->getAdapter ()->query ( $sql, array () );
	}
}

