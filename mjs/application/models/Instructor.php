<?php
class Application_Model_Instructor extends Zend_Db_Table_Abstract {
	protected $_name = 'instructors';
	public function isExist($where = array()) {
		$select = $this->select ();
		if (count ( $where ) > 0) {
			foreach ( $where as $key => $value ) {
				$select->where ( $key . ' = ?', $value );
			}
		}
		$row = $this->fetchAll ( $select );
		if ($row) {
			return true;
		} else {
			return false;
		}
	}
	
	// 根据firstname lastname创建或者增加instructor
	public function saveOrupdateInstructor($where = array()) {
		$select = $this->select ();
		$select->where ( 'firstName= ?', $where ['mFirstName'] );
		$select->where ( 'lastName= ?', $where ['mLastName'] );
		$row = $this->fetchRow ( $select );
		if (count($row)==0) {
			$newrow = $this->createRow ();
			$newrow->email = $where ["mEmail"];
			$newrow->phone = $where ["mPhone"];
			$newrow->firstName = $where ["mFirstName"];
			$newrow->lastName = $where ["mLastName"];
			$newrow->acctCreateDate = date ( 'Y-m-d H:i:s' );
			$id = $newrow->save ();
			return $id;
		} else {
			$newrow = $this->find ( $row ["inx"] )->current ();
			$newrow->email = $where ["mEmail"];
			$newrow->phone = $where ["mPhone"];
			$newrow->save ();
			return $newrow->inx;
			
		}
	}
}

