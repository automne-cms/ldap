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
  * Class CMS_ldap_groupCatalog
  *
  * Manages the collection of LDAP groups profiles.
  *
  * @package Automne
  * @subpackage cms_ldap
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

class CMS_ldap_groupCatalog extends CMS_grandFather
{
	/**
	  * Returns a CMS_ldap_usersGroup when given an ID
	  * Static function.
	  *
	  * @param integer $id The DB ID of CMS_ldap_usersGroup
	  * @param boolean $reset Reset groups before loading them
	  * @return CMS_ldap_usersGroup or false on failure
	  * @access public
	  * @static
	  */
	static function getByID($id, $reset = false)
	{
		static $groups;
		if($reset){
			unset($groups);
		}
		if (!isset($groups[$id])) {
			$groups[$id] = new CMS_ldap_usersGroup($id);
			if ($groups[$id]->hasError()) {
				$groups[$id] = false;
			}
		}
		return $groups[$id];
	}
	
	/**
	  * Returns all the userGroup, to which a user belongs to
	  * Returns empty group if no group found
	  * Static function.
	  * 
	  * @param CMS_profile_user|integer $user
	  * @param boolean $returnIds : return array of groups ids instead of CMS_ldap_usersGroup (faster, default : false)
	  * @return array(groupID => CMS_ldap_usersGroup)
	  * @access public
	  */
	static function getGroupsOfUser($user, $returnIds = false, $reset = false) {
		static $userGroups;
		if ($reset) {
			unset($userGroups);
		}
		if (is_a($user,"CMS_profile_user")) {
			$user = $user->getUserId();
		}
		if (!SensitiveIO::isPositiveInteger($user)) {
			return array();
		}
		if (!isset($userGroups)) {
			$sql = "
				select
					userId_gu,
					groupId_gu
				from
					profileUsersByGroup,
					profilesUsersGroups
				where
					groupId_gu = id_prg
				order by label_prg asc
			";
			$q = new CMS_query($sql);
			if ($q->getNumRows()) {
				$userGroups = array();
				while($data = $q->getArray()) {
					$userGroups[$data['userId_gu']][$data['groupId_gu']] = $data['groupId_gu'];
				}
			}
		}
		if (!isset($userGroups[$user])) {
			return array();
		} else {
			if ($returnIds) {
				return $userGroups[$user];
			} else {
				$groups = array();
				foreach($userGroups[$user] as $groupdId) {
					$groups[$groupdId] = CMS_ldap_groupCatalog::getById($groupdId,$reset);
				}
				return $groups;
			}
		}
	}
	
	/**
      * Get all groups DNs infos
      *
      * @return array(groupId => array(dn, filter)) groups dn
      * @access public
      * @static
      */
    static function getGroupsDN() {
		$sql = "
	        select
				id_prg,
				dn_plg,
				filter_plg,
	        	group_plg
	        from
				profilesUsersGroups,
				profilesLdapGroups
	        where
				profile_plg = id_prg
	        order by
				id_prg asc
		";
		$q = new CMS_query($sql);
		$groupsDN = array();
		if ($q->getNumRows()) {
			while ($r = $q->getArray()) {
				$groupsDN[$r['id_prg']] = array('dn' => $r['dn_plg'], 'filter' => $r['filter_plg'], 'group' => $r['group_plg']);
			}
		}
		return $groupsDN;
    }
}
?>