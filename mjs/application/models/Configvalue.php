<?php
class Application_Model_Configvalue extends Zend_Db_Table_Abstract {
	protected $_name = 'configue_default';
	public function getDefaultValueByKey($key = null) {
		$select = $this->select ();
		$select->where ( 'd_key = ?', $key);
		$row = $this->fetchRow ( $select );
		
		if ($row) {
			return $row;
		} else {
			return null;
		}
	}
	
}

