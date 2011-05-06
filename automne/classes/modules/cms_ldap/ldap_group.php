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
  * Class CMS_ldap_usersGroup
  *
  * Manage ldap user's group
  *
  * @package Automne
  * @subpackage cms_ldap
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

class CMS_ldap_usersGroup extends CMS_profile_usersGroup
{
	/**
	  * LDAP group vars
	  *
	  * @var string
	  * @access private
	  */
	protected $_dn;
	protected $_filter;
	protected $_group;
	
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
					profilesLdapGroups
				where
					profile_plg='$id'
			";
			$q = new CMS_query($sql);
			if ($q->getNumRows()) {
				$data = $q->getArray();
				
				$this->_dn		= $data["dn_plg"];
				$this->_filter	= $data["filter_plg"];
				$this->_group	= $data["group_plg"];
			}
		}
		parent::__construct($id);
    }
	
	/**
	  * Does the current group is LDAP related ?
	  *
	  * @return boolean
	  * @access public
	  */
	function isLdapRelated() {
		return $this->_dn || $this->_filter || $this->_group;
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
	  * @return boolean
	  * @access public
	  */
	function setDN($dn)
	{
		$this->_dn = $dn;
		return true;
	}
	
	/**
	  * Get Filter
	  *
	  * @return string
	  * @access public
	  */
	function getFilter()
	{
		return $this->_filter;
	}
	
	/**
	  * Set Filter
	  *
	  * @param string $filter
	  * @return boolean
	  * @access public
	  */
	function setFilter($filter)
	{
		if ($filter) {
			if (substr($filter, 0, 1) != '(') {
				$filter = '('.$filter;
			}
			if (substr($filter, -1) != ')') {
				$filter = $filter.')';
			}
		}
		$this->_filter = $filter;
		return true;
	}
	
	/**
	  * Get group
	  *
	  * @return string
	  * @access public
	  */
	function getGroup()
	{
		return $this->_group;
	}
	
	/**
	  * Set Group
	  *
	  * @param string $group
	  * @return boolean
	  * @access public
	  */
	function setGroup($group)
	{
		$this->_group = $group;
		return true;
	}
	
	/**
	  * Writes the group Datas into persistence
	  *
	  * @return boolean true on success, false on failure
	  * @access public
	  */
	function writeToPersistence() {
		$return = parent::writeToPersistence();
		if (!$this->isLdapRelated()) {
			return $return;
		}
		//write user DN
		$sql_fields = "
			dn_plg='".SensitiveIO::sanitizeSQLString($this->_dn)."',
			filter_plg='".SensitiveIO::sanitizeSQLString($this->_filter)."',
			group_plg='".SensitiveIO::sanitizeSQLString($this->_group)."',
			profile_plg='".SensitiveIO::sanitizeSQLString($this->_groupId)."'
		";
		$sql = "
			replace into
				profilesLdapGroups
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