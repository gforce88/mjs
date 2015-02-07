<?php
class Application_Model_Translator extends Zend_Db_Table_Abstract {
	protected $_name = 'translators';
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
	
	// 根据firstname lastname创建或者增加translator
	public function saveOrupdateTranslator($where = array()) {
		$select = $this->select ();
		$select->where ( 'firstName= ?', $where ['tFirstName'] );
		$select->where ( 'lastName= ?', $where ['tLastName'] );
		$row = $this->fetchRow ( $select );
		if (count ( $row ) == 0) {
			$newrow = $this->createRow ();
			$newrow->email = $where ["tEmail"];
			$newrow->phone = $where ["tPhone"];
			$newrow->firstName = $where ["tFirstName"];
			$newrow->lastName = $where ["tLastName"];
			$newrow->acctCreateDate = date ( 'Y-m-d H:i:s' );
			$id = $newrow->save ();
			return $id;
		} else {
			$newrow = $this->find ( $row ["inx"] )->current ();
			$newrow->email = $where ["tEmail"];
			$newrow->phone = $where ["tPhone"];
			$newrow->save ();
			return $newrow->inx;
		}
	}
	
	
	public function findTranslatorEmail($where = array()){
		$select = $this->select ();
		$select->where ( 'firstName= ?', $where ['mFirstName'] );
		$select->where ( 'lastName= ?', $where ['mLastName'] );
		$row = $this->fetchRow ( $select );
		if (count($row)!=0) {
			return $row->email;
		} else {
			return null;
	
		}
	}
}

