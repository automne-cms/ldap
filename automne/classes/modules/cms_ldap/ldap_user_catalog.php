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
  * Class CMS_ldap_userCatalog
  *
  *  Manages the collection of users profiles.
  *
  * @package Automne
  * @subpackage cms_ldap
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

class CMS_ldap_userCatalog extends CMS_grandFather
{
	/**
	  * Returns a CMS_ldap_user when given an ID
	  * Static function.
	  *
	  * @param integer $id The DB ID of the wanted CMS_ldap_user
	  * @param boolean $reset : Reset the local cache (force to reload user from DB)
	  * @return CMS_ldap_user or false on failure to find it
	  * @access public
	  */
	static function getByID($id, $reset = false)
	{
		static $users;
		if (!isset($users[$id]) || $reset) {
			$users[$id] = new CMS_ldap_user($id);
			if ($users[$id]->hasError()) {
				$users[$id] = false;
			}
		}
		return $users[$id];
	}
	
	/**
	  * Returns an active CMS_profile_user with a given user login
	  * Static function.
	  *
	  * @param string $login The user login of the wanted CMS_profile_user
	  * @param boolean $reset : Reset the local cache (force to reload user from DB)
	  * @return CMS_profile_user or false on failure to find it
	  * @access public
	  */
	static function getByLogin($login, $reset = false)
	{
		static $users;
		if (!isset($users[$login]) || $reset) {
			$sql = "
				select
					id_pru
				from
					profilesUsers
				where
					login_pru = '".SensitiveIO::sanitizeSQLString($login)."'
					and deleted_pru='0'
					and active_pru='1'
			";
			$q = new CMS_query($sql);
			if($q->getNumRows() == 1){
				$users[$login] = new CMS_ldap_user($q->getValue('id_pru'));
				if ($users[$login]->hasError()) {
					$users[$login] = false;
				}
			} else {
				$users[$login] = false;
			}
		}
		return $users[$login];
	}
	
	/**
	  * Returns a CMS_ldap_user when given a LDAP dn
	  * 
	  * @param string $dn The LDAP dn to search a user with
	  * @return CMS_profile_user or false on failure
	  * @access public
	  * @static
	  */
	static function getByDN($dn)
	{
		if (trim($dn) != '') {
			$sql = "
				select
					id_pru as id
				from
					profilesUsers,
					profilesLdapUsers
				where
					dn_plu like '".SensitiveIO::sanitizeSQLString($dn)."'
					and profile_plu = id_pru
					and deleted_pru=0
			";
			$q = new CMS_query($sql);
			if ($q->getNumRows() == 1) {
				$obj = new CMS_ldap_user($q->getValue("id"), true);
				if (!$obj->hasError()) {
					return $obj;
				}
			}
		}
		return false;
	}
	
	/**
	  * Checks all the profile users, except $user
	  * to see if LDAP dn doesnt exist. Static function.
	  *
	  * @param CMS_profile_user $user
	  * @param string $dn
	  * @return boolean
	  * @access public
	  */
	static function dnExists($dn, &$user)
	{
		$sql = "
			select
				1
			from
				profilesUsers,
				profilesLdapUsers
			where
				dn_plu like '".SensitiveIO::sanitizeSQLString($dn)."'
				and profile_plu = id_pru
				and deleted_pru = 0
				and id_pru != '".SensitiveIO::sanitizeSQLString($user->getUserId())."'
		";
		$q = new CMS_query($sql);
		return $q->getNumRows() ? true : false;
	}
}
?>