<?php
/**
  * Install or update cms_ldap module
  * @author S�bastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

require_once(dirname(__FILE__).'/../../cms_rc_admin.php');

//check if ASE is already installed (if so, it is an update)
$sql = "show tables";
$q = new CMS_query($sql);
$installed = false;
while ($table = $q->getValue(0)) {
	if ($table == 'profilesLdapUsers') {
		$installed = true;
	}
}
if (!$installed) {
	echo "Module LDAP installation : Not installed : Launch installation ...<br />";
	if (CMS_patch::executeSqlScript(PATH_MAIN_FS.'/sql/mod_cms_ldap.sql',true)) {
		CMS_patch::executeSqlScript(PATH_MAIN_FS.'/sql/mod_cms_ldap.sql',false);
		echo "Module LDAP installation : Installation done.<br /><br />";
	} else {
		echo "Module LDAP installation : INSTALLATION ERROR ! Problem in SQL syntax (SQL tables file) ...<br />";
	}
} else {
	echo "Module LDAP installation : Already installed : update done.<br />";
}
?>