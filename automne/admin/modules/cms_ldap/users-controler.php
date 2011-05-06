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
// | Author: Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>	  |
// +----------------------------------------------------------------------+

/**
  * PHP controler : Receive actions on users
  * Used accross an Ajax request to process one user action
  * 
  * @package Automne
  * @subpackage cms_ldap
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

require_once(dirname(__FILE__).'/../../../../cms_rc_admin.php');

define("MESSAGE_PAGE_NO_USER", 473);
define("MESSAGE_PAGE_USER_DATA_REGISTERED", 479);

define("MESSAGE_ERROR_DN_INVALID", 13);

//Controler vars
$action = sensitiveIO::request('action', array('update-user'));
$userId = sensitiveIO::request('userId', 'sensitiveIO::isPositiveInteger');

//Identity vars
$dn = sensitiveIO::request('dn');

//load interface instance
$view = CMS_view::getInstance();
//set default display mode for this page
$view->setDisplayMode(CMS_view::SHOW_JSON);
//This file is an admin file. Interface must be secure
$view->setSecure();

//load user if any
if ($userId) {
	$user = CMS_ldap_userCatalog::getByID($userId);
	if (!$user || $user->hasError()) {
		CMS_grandFather::raiseError('Unknown user for given Id : '.$userId);
		$cms_message = $cms_language->getMessage(MESSAGE_PAGE_NO_USER);
		$view->setActionMessage($cms_message);
		$view->show();
	}
} else {
	CMS_grandFather::raiseError('Unknown user for given Id : '.$userId);
	$cms_message = $cms_language->getMessage(MESSAGE_PAGE_NO_USER);
	$view->setActionMessage($cms_message);
	$view->show();
}

//is it a personal profile edition ?
$personalProfile = (isset($user) && $user->getUserId() == $cms_user->getUserId());

//check user rights
if (!$personalProfile && !$cms_user->hasAdminClearance(CLEARANCE_ADMINISTRATION_EDITUSERS)) {
	CMS_grandFather::raiseError('User has no users management rights ...');
	$view->show();
} elseif ($personalProfile && !$cms_user->hasAdminClearance(CLEARANCE_ADMINISTRATION_EDITUSERS) && !in_array($action, array('update-user'))) {//define here all actions which can be done by user itself
	CMS_grandFather::raiseError('User has no users management rights ...');
	$view->show();
}

$cms_message = '';

switch ($action) {
	case 'update-user':
		//Can we update the user infos from LDAP ?
		$updateFromLDAP = false;
		//Load LDAP options
		$options = CMS_module_cms_ldap::getLdapConfig();
		if ($options) {
			$atmOptions = $options->automne->toArray();
			if (isset($atmOptions['updateAutomneUsersInfos']) && $atmOptions['updateAutomneUsersInfos']) {
				$updateFromLDAP = true;
			}
		}
		if ($user->setDN($dn, $updateFromLDAP)) {
			$user->writeToPersistence();
			$log = new CMS_log();
			$log->logMiscAction(CMS_log::LOG_ACTION_PROFILE_USER_EDIT, $cms_user, "Edit DN for user : ".$user->getFullName());
			$cms_message = $cms_language->getMessage(MESSAGE_PAGE_USER_DATA_REGISTERED);
		} else {
			$cms_message = $cms_language->getMessage(MESSAGE_ERROR_DN_INVALID, false, $this->getCodename());
		}
	break;
}

//set user message if any
if ($cms_message) {
	$view->setActionMessage($cms_message);
}
$view->show();
?>