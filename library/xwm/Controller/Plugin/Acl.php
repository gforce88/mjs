<?php

/**
 * 权限控制插件
 * @author xuweiming
 *
 */

class xwm_Controller_Plugin_Acl extends Zend_Controller_Plugin_Abstract
{
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		$acl = new Zend_Acl();
		//添加角色
		$acl->addRole('guest');
		$acl->addRole('user','guest');
		//添加资源
		$acl->addResource('land');
		$acl->addResource('index');
		$acl->addResource('session');
		$acl->addResource('student');
		$acl->addResource('login');
		$acl->addResource('report');
		$acl->addResource('linemnt');
		$acl->addResource('linestu');
		$acl->addResource('linetrl');
		$acl->addResource('timer');
		
		//匿名用户权限
		$acl->deny('guest',null,null);
// 		$acl->deny('guest',null,null);
		$acl->allow('guest',array('login'),array('index'));
		$acl->allow('guest',array('linemnt'),null);
		$acl->allow('guest',array('linestu'),null);
		$acl->allow('guest',array('linetrl'),null);
		$acl->allow('guest',array('report'),null);
		$acl->allow('guest',array('timer'),null);
		
		//登录用户权限
		$acl->allow('user',null,null);
		
		//当前用户
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$identity = $auth->getIdentity();
			//$role = strtolower($identity->role);
			$role = 'user';
		}else{
			$role = 'guest';
		}
		$controller = $request->getControllerName();
		$action = $request->getActionName();
		try {
			if(!$acl->isAllowed($role,$controller,$action)){
				if($role == 'guest'){
					$request->setControllerName('login');
					$request->setActionName('index');
				}else{
					$request->setControllerName('error');
					$request->setActionName('error');
				}
			}
		}catch (Exception $e) {   
			$request->setControllerName('error');
			$request->setActionName('pagenotfound');
		}
	}
}