<?php
class Application_Model_User extends Zend_Db_Table_Abstract {
	protected $_name = 'user';
	public function getUserEmail() {
		$select = $this->select ();
		$row = $this->fetchRow ( $select );
		if ($row) {
			return $row->username;
		} else {
			return null;
		}
	}
}

