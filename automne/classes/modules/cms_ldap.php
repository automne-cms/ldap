<?php
// +----------------------------------------------------------------------+
// | Automne (TM)														  |
// +----------------------------------------------------------------------+
// | Copyright (c) 2000-2010 WS Interactive								  |
// +----------------------------------------------------------------------+
// | Automne is subject to version 2.0 or above of the GPL license.		  |
// | The license text is bundled with this package in the file			  |
// | LICENSE-GPL, and is available through the world-wide-web at		  |
// | http://www.gnu.org/copyleft/gpl.html.								  |
// +----------------------------------------------------------------------+
// | Author: Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>      |
// +----------------------------------------------------------------------+

/**
  *	Ldap configuration file
  *	Default : PATH_MAIN_FS.'/config/ldap.ini'
  */
if (!defined("APPLICATION_CMS_LDAP_CONFIGURATION_FILE")) {
	define("APPLICATION_CMS_LDAP_CONFIGURATION_FILE", PATH_MAIN_FS.'/config/ldap.ini');
}

/**
  *	Ldap default user group
  *	Default : null
  */
if (!defined("APPLICATION_CMS_LDAP_DEFAULT_GROUP")) {
	define("APPLICATION_CMS_LDAP_DEFAULT_GROUP", null);
}

/**
  * Class CMS_module_cms_ldap
  *
  * represent the LDAP module.
  *
  * @package Automne
  * @subpackage cms_ldap
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */
class CMS_module_cms_ldap extends CMS_module
{
	//Messages constants
	const MESSAGE_PAGE_USER_LINK_DESC = 2;
	const MESSAGE_PAGE_DN_DESC = 3;
	const MESSAGE_PAGE_DN = 4;
	const MESSAGE_PAGE_GROUP_LINK_DESC = 5;
	const MESSAGE_PAGE_BASE_DN_DESC = 6;
	const MESSAGE_PAGE_FILTER_DESC = 7;
	const MESSAGE_PAGE_FILTER = 8;
	const MESSAGE_PAGE_GROUP_DESC = 9;
	const MESSAGE_PAGE_GROUP = 10;
	const MESSAGE_PAGE_LDAP_ID = 11;
	const MESSAGE_PAGE_GROUP_LINK = 12;
	const MESSAGE_PAGE_BASE_DN = 14;
	
	const MESSAGE_PARAM_DEFAULT_CREATED_USERS_GROUP = 15;
	const MESSAGE_PARAM_DEFAULT_CREATED_USERS_GROUP_DESC = 16;
	const MESSAGE_PARAM_CHECK_LDAP_USERS_DAILY = 17;
	const MESSAGE_PARAM_CHECK_LDAP_USERS_DAILY_DESC = 18;
	const MESSAGE_PARAM_DELETE_INVALID_LDAP_USERS = 19;
	const MESSAGE_PARAM_DELETE_INVALID_LDAP_USERS_DESC = 20;
	
	/**
	  * Module autoload handler
	  *
	  * @param string $classname the classname required for loading
	  * @return string : the file to use for required classname
	  * @access public
	  */
	function load($classname) {
		static $classes;
		if (!isset($classes)) {
			$classes = array(
				/**
				 * Module main classes
				 */
				'cms_ldap_auth' 		=> PATH_MODULES_FS."/".$this->getCodename()."/auth.php",
				'cms_ldap_user'			=> PATH_MODULES_FS."/".$this->getCodename()."/ldap_user.php",
				'cms_ldap_usercatalog'	=> PATH_MODULES_FS."/".$this->getCodename()."/ldap_user_catalog.php",
				'cms_ldap_groupcatalog'	=> PATH_MODULES_FS."/".$this->getCodename()."/ldap_group_catalog.php",
				'cms_ldap_usersgroup'	=> PATH_MODULES_FS."/".$this->getCodename()."/ldap_group.php",
			);
		}
		$file = '';
		if (isset($classes[io::strtolower($classname)])) {
			$file = $classes[io::strtolower($classname)];
		}
		return $file;
	}
	
	/**
	  * Process the module daily routine
	  *
	  * @return void
	  * @access public
	  */
	function processDailyRoutine() {
		$module = CMS_modulesCatalog::getByCodename('cms_ldap');
		$parameter = $module->getParameters('CHECK_LDAP_USERS_DAILY');
		if (sensitiveIO::isPositiveInteger($parameter)) {
			//get all LDAP users ID and DN
			$users = CMS_ldap_userCatalog::getAll(true, false, false);
			if (!$users) {
				return true;
			}
			//Load LDAP options
			$options = CMS_module_cms_ldap::getLdapConfig();
			if (!$options) {
				$this->raiseError("Daily routine error : cannot get LDAP options");
				return false;
			}
			$ldapOptions = $options->ldap->toArray();
			//load delete param
			$deleteParameter = $module->getParameters('DELETE_INVALID_LDAP_USERS');
			try {
				//get user infos according to options
				$ldap = new Zend_Ldap($ldapOptions);
				if ($ldap) {
					foreach ($users as $userId => $dn) {
						//check if DN exists
						if (!$ldap->exists($dn)) {
							//load invalid user
							$invalidUser = CMS_ldap_userCatalog::getByID($userId);
							if ($invalidUser) {
								if (sensitiveIO::isPositiveInteger($deleteParameter)) {
									$invalidUser->setDeleted(true);
									$invalidUser->setActive(false);
									$log = new CMS_log();
									$log->logMiscAction(CMS_log::LOG_ACTION_PROFILE_USER_EDIT, $invalidUser, "Daily routine : Auto delete invalid LDAP user : ".$invalidUser->getFullName());
								} else {
									$invalidUser->setActive(false);
									$log = new CMS_log();
									$log->logMiscAction(CMS_log::LOG_ACTION_PROFILE_USER_EDIT, $invalidUser, "Daily routine : Auto desactivate invalid LDAP user : ".$invalidUser->getFullName());
								}
								$invalidUser->writeToPersistence();
								unset($invalidUser);
							}
						}
					}
				}
			} catch (Exception $e) {
				$this->raiseError("Daily routine error : ".$e->getMessage());
			}
			return true;
		}
	}
	
	/**
	  * Get current module configuration (LDAP and Automne config)
	  *
	  * @return Zend_Config_Ini
	  * @access public
	  * @static
	  */
	static function getLdapConfig() {
		if (is_file(APPLICATION_CMS_LDAP_CONFIGURATION_FILE)) {
			$config = new Zend_Config_Ini(APPLICATION_CMS_LDAP_CONFIGURATION_FILE, null);
			if (isset($config->ldap) && isset($config->automne)) {
				return $config;
			} else {
				CMS_grandFather::raiseError('Wrong LDAP configuration file : Missing LDAP or Automne infos in file '.APPLICATION_CMS_LDAP_CONFIGURATION_FILE);
				return false;
			}
		} else {
			CMS_grandFather::raiseError('Missing LDAP config file '.APPLICATION_CMS_LDAP_CONFIGURATION_FILE);
			return false;
		}
		return false;
	}
	
	/**
	  * Get the module authentification adapter
	  *
	  * @param array : the authentification params
	  * @return CMS_auth : the module authentification adapter
	  * @access public
	  */
	function getAuthAdapter($params) {
		//create auth adapter with params
		return new CMS_ldap_auth($params);
	}
	
	/**
	  * Get Administration User accordion infos
	  *
	  * @param integer $userId
	  * @param CMS_language $cms_language
	  * @return array
	  * @access public
	  */
	function getUserAccordionProperties($userId, $cms_language) {
		$return = array();
		if (io::isPositiveInteger($userId)) {
			$user = CMS_ldap_userCatalog::getByID($userId);
		} else {
			$user = new CMS_ldap_user();
		}
		$fields = array(array(
			'bodyStyle'		=>	'padding:0 0 10px 0',
			'xtype'			=>	'panel',
			'html'			=>	$cms_language->getMessage(self::MESSAGE_PAGE_USER_LINK_DESC, false, $this->getCodename()),
			'border'		=>	false,
		), array(
			'allowBlank'	=>	true,
			'fieldLabel' 	=>	'<span class="atm-help" ext:qtip="'.io::htmlspecialchars($cms_language->getMessage(self::MESSAGE_PAGE_DN_DESC, false, $this->getCodename())).'">'.$cms_language->getMessage(self::MESSAGE_PAGE_DN, false, $this->getCodename()).'</span>',
			'xtype'			=>	'textfield',
			'name'			=>	'dn',
			'value'			=>	$user->getDN(),
		));
		$return['fields'] = $fields;
		$return['url'] = PATH_ADMIN_MODULES_WR.'/'.$this->getCodename().'/users-controler.php';
		$return['label'] = $cms_language->getMessage(self::MESSAGE_PAGE_LDAP_ID, false, $this->getCodename());
		return $return;
	}
	
	/**
	  * Get Administration Group accordion infos
	  *
	  * @param integer $groupId
	  * @param CMS_language $cms_language
	  * @return array
	  * @access public
	  */
	function getGroupAccordionProperties($groupId, $cms_language) {
		$return = array();
		if (io::isPositiveInteger($groupId)) {
			$group = CMS_ldap_groupCatalog::getByID($groupId);
		} else {
			$group = new CMS_ldap_usersGroup();
		}
		$fields = array(array(
			'bodyStyle'		=>	'padding:0 0 10px 0',
			'xtype'			=>	'panel',
			'html'			=>	$cms_language->getMessage(self::MESSAGE_PAGE_GROUP_LINK_DESC, false, $this->getCodename()),
			'border'		=>	false,
		), array(
			'allowBlank'	=>	true,
			'fieldLabel' 	=>	'<span class="atm-help" ext:qtip="'.io::htmlspecialchars($cms_language->getMessage(self::MESSAGE_PAGE_BASE_DN_DESC, false, $this->getCodename())).'">'.$cms_language->getMessage(self::MESSAGE_PAGE_BASE_DN, false, $this->getCodename()).'</span>',
			'xtype'			=>	'textfield',
			'name'			=>	'dn',
			'value'			=>	$group->getDN(),
		), array(
			'allowBlank'	=>	true,
			'fieldLabel' 	=>	'<span class="atm-help" ext:qtip="'.io::htmlspecialchars($cms_language->getMessage(self::MESSAGE_PAGE_FILTER_DESC, false, $this->getCodename())).'">'.$cms_language->getMessage(self::MESSAGE_PAGE_FILTER, false, $this->getCodename()).'</span>',
			'xtype'			=>	'textfield',
			'name'			=>	'filter',
			'value'			=>	$group->getFilter(),
		), array(
			'allowBlank'	=>	true,
			'fieldLabel' 	=>	'<span class="atm-help" ext:qtip="'.io::htmlspecialchars($cms_language->getMessage(self::MESSAGE_PAGE_GROUP_DESC, false, $this->getCodename())).'">'.$cms_language->getMessage(self::MESSAGE_PAGE_GROUP, false, $this->getCodename()).'</span>',
			'xtype'			=>	'textfield',
			'name'			=>	'group',
			'value'			=>	$group->getGroup(),
		));
		$return['fields'] = $fields;
		$return['url'] = PATH_ADMIN_MODULES_WR.'/'.$this->getCodename().'/groups-controler.php';
		$return['label'] = $cms_language->getMessage(self::MESSAGE_PAGE_GROUP_LINK, false, $this->getCodename());
		return $return;
	}
}
?>