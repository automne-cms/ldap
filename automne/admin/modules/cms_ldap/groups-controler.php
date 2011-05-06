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
  * PHP controler : Receive actions on groups
  * Used accross an Ajax request to process one group action
  * 
  * @package Automne
  * @subpackage cms_ldap
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

require_once(dirname(__FILE__).'/../../../../cms_rc_admin.php');

define("MESSAGE_PAGE_GROUP_UNKNOWN", 494);
define("MESSAGE_PAGE_DATA_SAVED_GROUP", 496);

//Controler vars
$action = sensitiveIO::request('action', array('update-group'));
$groupId = sensitiveIO::request('groupId', 'sensitiveIO::isPositiveInteger');

//Identity vars
$dn = sensitiveIO::request('dn');
$filter = sensitiveIO::request('filter');
$groupData = sensitiveIO::request('group');

//load interface instance
$view = CMS_view::getInstance();
//set default display mode for this page
$view->setDisplayMode(CMS_view::SHOW_JSON);
//This file is an admin file. Interface must be secure
$view->setSecure();

//load user if any
if ($groupId) {
	$group = CMS_ldap_groupCatalog::getByID($groupId);
	if (!$group || $group->hasError()) {
		CMS_grandFather::raiseError('Unknown group for given Id : '.$groupId);
		$cms_message = $cms_language->getMessage(MESSAGE_PAGE_GROUP_UNKNOWN);
		$view->setActionMessage($cms_message);
		$view->show();
	}
} else {
	CMS_grandFather::raiseError('Unknown group for given Id : '.$groupId);
	$cms_message = $cms_language->getMessage(MESSAGE_PAGE_GROUP_UNKNOWN);
	$view->setActionMessage($cms_message);
	$view->show();
}

//check user rights
if (!$cms_user->hasAdminClearance(CLEARANCE_ADMINISTRATION_EDITUSERS)) {
	CMS_grandFather::raiseError('User has no groups management rights ...');
	$view->show();
}

$cms_message = '';

switch ($action) {
	case 'update-group':
		$group->setDN($dn);
		$group->setFilter($filter);
		$group->setGroup($groupData);
		$group->writeToPersistence();
		$log = new CMS_log();
		$log->logMiscAction(CMS_log::LOG_ACTION_PROFILE_GROUP_EDIT, $cms_user, "Edit LDAP datas for group : ".$group->getLabel());
		$cms_message = $cms_language->getMessage(MESSAGE_PAGE_DATA_SAVED_GROUP);
		
	break;
}

//set user message if any
if ($cms_message) {
	$view->setActionMessage($cms_message);
}
$view->show();
?>