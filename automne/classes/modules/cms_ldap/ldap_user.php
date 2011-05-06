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
  * Class CMS_ldap_user
  *
  * Manage ldap user
  *
  * @package Automne
  * @subpackage cms_ldap
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

class CMS_ldap_user extends CMS_profile_user
{
	/**
	  * LDAP distinguished name
	  *
	  * @var string
	  * @access private
	  */
	protected $_dn;
	
	/**
	  * Constructor.
	  * Loads all Id variables if
	  *
	  * @param integer $id id of profile in DB
	  * @return  void
	  * @access public
	  */
    function __construct($id = false)
    {
        //Load profile DN
		if (SensitiveIO::isPositiveInteger($id)) {
			$sql = "
				select
					*
				from
					profilesLdapUsers
				where
					profile_plu='$id'
			";
			$q = new CMS_query($sql);
			if ($q->getNumRows()) {
				$data = $q->getArray();
				
				$this->_dn = $data["dn_plu"];
			}
		}
		parent::__construct($id);
    }
	
	/**
	  * Get DN
	  *
	  * @return string
	  * @access public
	  */
	function getDN()
	{
		return $this->_dn;
	}
	
	/**
	  * Set DN
	  *
	  * @param string $dn
	  * @param boolean $updateUserInfos : Automaticaly update user infos on DN change (default : true)
	  * @return boolean
	  * @access public
	  */
	function setDN($dn, $updateUserInfos = true)
	{
		$this->_dn = $dn;
		if ($this->_dn) {
			if (CMS_ldap_userCatalog::dnExists($this->_dn, $this)) {
				$this->raiseError("Failed to set DN for user : this DN already exists: ".$this->_dn);
				return false;
			}
			if ($updateUserInfos) {
				if (!$this->updateUserInfos()) {
					$this->raiseError("Failed to get LDAP user infos from given DN: ".$this->_dn);
					return false;
				}
			}
		}
		return true;
	}
	
	/**
	  * Update users infos and groups according to LDAP infos
	  *
	  * @return boolean
	  * @access public
	  */
	function updateUserInfos() {
		//do we have dn to update it ?
		if (!$this->_dn) {
			$this->raiseError("Error during updating infos for user ".$this->getLogin()." : cannot update infos without a valid DN");
			return false;
		}
		//Load LDAP options
		$options = CMS_module_cms_ldap::getLdapConfig();
		if (!$options) {
			$this->raiseError("Error during updating infos for user ".$this->getLogin()." : cannot get LDAP options");
			return false;
		}
		$ldapOptions = $options->ldap->toArray();
		$atmOptions = $options->automne->toArray();
		
		$userInfos = array();
		try {
			//get user infos according to options
			$ldap = new Zend_Ldap($ldapOptions);
			//check if DN exists
			if (!$ldap->exists($this->_dn)) {
				//no user founded for DN. Delete user ???
				return false;
			}
			$hm = $ldap->getEntry($this->_dn);
			if ($hm) {
				if (isset($atmOptions['account'])) {
					foreach ($atmOptions['account'] as $key => $value) {
						if ($value && isset($hm[$value][0])) {
							if ($key == 'login') {
								$ldapOptions = $ldap->getOptions();
								$userInfos[$key] = $ldap->getCanonicalAccountName($hm[$value][0], $ldapOptions['accountCanonicalForm']);
							} else {
								$userInfos[$key] = $hm[$value][0];
							}
						}
					}
				}
			} else {
				//no user founded for DN. Delete user ???
				return false;
			}
		} catch (Exception $e) {
			$this->raiseError($e->getMessage());
		}
		//Update user infos
		
		//login
		if (isset($userInfos['login']) && !CMS_profile_usersCatalog::loginExists($userInfos['login'], $this)) {
			$this->setLogin($userInfos['login']);
		}
		
		//lastname
		if (isset($userInfos['lastname'])) {
			$this->setLastName($userInfos['lastname']);
		}
		//firstname
		if (isset($userInfos['firstname'])) {
			$this->setFirstName($userInfos['firstname']);
		}
		//contact datas
		$userCD = $this->getContactData();
		foreach ($userInfos as $property => $value) {
			if (!in_array($property, array('lastname', 'firstname', 'dn', 'login'))) {
				$userCD->setValue($property, $value);
			}
		}
		$this->setContactData($userCD);
		if (!$this->writeToPersistence()  || $this->hasError()) {
			$this->raiseError("Error during updating infos for user ".$this->getLogin());
			return false;
		}
		//update all LDAP related groups
		if (!$this->_associateGroups($options)) {
			$this->raiseError("Error during update of user groups for user ".$this->getLogin());
			return false;
		}
		return true;
	}
	
	/**
	  * Update users groups according to LDAP infos
	  *
	  * @param Zend_Config $options : the current cms_ldap config
	  * @return boolean
	  * @access private
	  */
	private function _associateGroups($options) {
		$ldapOptions = $options->ldap->toArray();
		$atmOptions = $options->automne->toArray();
		
		// Get user LDAP groups
		$LDAPGroups = array();
		$groupsDNs = CMS_ldap_groupCatalog::getGroupsDN();
		if ($groupsDNs) {
			//Then for each groups, check if user is a part of it in the LDAP
			try {
				//get user infos according to options
				$ldap = new Zend_Ldap($ldapOptions);
				$acctname = $ldap->getCanonicalAccountName($this->getLogin(), Zend_Ldap::ACCTNAME_FORM_USERNAME);
				//Create default filter using accountFilterFormat option and username
				$options = $ldap->getOptions();
				$accountFilterFormat = $options['accountFilterFormat'];
				$defaultFilter = sprintf ($accountFilterFormat , $acctname);
				//Create group filter fomat
				$canonicalForm = isset($atmOptions['ldapGroupCanonicalForm']) ? $atmOptions['ldapGroupCanonicalForm'] : Zend_Ldap::ACCTNAME_FORM_DN;
				$accdn = $this->_dn;
				if (isset($atmOptions['ldapGroupFilterFormat'])) {
					$groupFilter = sprintf($atmOptions['ldapGroupFilterFormat'], $acctname);
				} else {
					$groupFilter = sprintf('(member=%s)', $acctname);
				}
				foreach ($groupsDNs as $groupId => $groupInfos) {
					$result = true;
					//first, check group if exists
					if ($groupInfos['group']) {
						$dn = $groupInfos['group'];
						$filter = $groupFilter;
						//check if group match user
						$result = $ldap->count($filter, $dn, Zend_Ldap::SEARCH_SCOPE_SUB);
					}
					//then check base dn and filter
					if ($result) {
						$dn = $groupInfos['dn'] ? $groupInfos['dn'] : null;
						$filter = $groupInfos['filter'] ? '(&'.$defaultFilter.$groupInfos['filter'].')' : $defaultFilter;
						//check if group match user
						$result = $ldap->count($filter, $dn, Zend_Ldap::SEARCH_SCOPE_SUB);
					}
					if ($result) {
						$LDAPGroups[$groupId] = CMS_profile_usersGroupsCatalog::getByID($groupId);
					}
				}
			} catch (Exception $e) {
				$this->raiseError($e->getMessage());
				return false;
			}
		}
		
		//get all current user groups
		$currentGroups = CMS_ldap_groupCatalog::getGroupsOfUser($this);
		//user has no groups and we need to add some from LDAP
		if (!$currentGroups && $LDAPGroups) {
			//first reset profile clearances
			$this->resetClearances();
			//then add user to all LDAP groups
			foreach ($LDAPGroups as $groupID => $LDAPGroup) {
				$this->addGroup($groupID);
			}
		}
		//user has groups and we need to remove all groups with LDAP relation
		if ($currentGroups && !$LDAPGroups) {
			foreach ($currentGroups as $currentGroup) {
				if ($currentGroup->isLdapRelated()) {
					if ($currentGroup->removeUser($this)) {
						$currentGroup->writeToPersistence();
					}
				}
			}
		}
		//user has groups and we need to update them
		if ($currentGroups && $LDAPGroups) {
			//first reset profile clearances
			$this->resetClearances();
			//add all LDAP related groups
			foreach ($LDAPGroups as $groupID => $LDAPGroup) {
				$this->addGroup($LDAPGroup);
			}
			//then add all classic groups again
			foreach ($currentGroups as $groupID => $currentGroup) {
				//if group has is not LDAP related, add it
				if (!$currentGroup->isLdapRelated()) {
					$this->addGroup($currentGroup);
				} elseif (!isset($LDAPGroups[$groupID])) {
					if ($currentGroup->removeUser($this)) {
						$currentGroup->writeToPersistence();
					}
				}
			}
		}
		//then write user profile into persistence
		$this->writeToPersistence();
		
		return true;
	}
	
	/**
	  * Writes the user Data into persistence
	  *
	  * @return boolean true on success, false on failure
	  * @access public
	  */
	function writeToPersistence() {
		$return = parent::writeToPersistence();
		//write user DN
		$sql_fields = "
			dn_plu='".SensitiveIO::sanitizeSQLString($this->_dn)."',
			profile_plu='".SensitiveIO::sanitizeSQLString($this->_userId)."'
		";
		$sql = "
			replace into
				profilesLdapUsers
			set
				".$sql_fields."
		";
		$q = new CMS_query($sql);
		if ($q->hasError()) {
			return false;
		}
		return $return;
	}
}
?>