<?php
/**
  * Install or update cms_ldap module
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

require_once(dirname(__FILE__).'/../../cms_rc_admin.php');

//check if module is already installed (if so, it is an update)
$installed = false;
$codenames = CMS_modulesCatalog::getAllCodenames(true);
if (isset($modules['cms_ldap'])) {
	$installed = true;
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