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
		//copy module parameters file and ldap config file
		if (CMS_file::copyTo(PATH_TMP_FS.PATH_PACKAGES_WR.'/modules/cms_ldap_rc.xml', PATH_PACKAGES_FS.'/modules/cms_ldap_rc.xml')
			&& CMS_file::copyTo(PATH_TMP_FS.PATH_MAIN_WR.'/config/ldap.ini', PATH_MAIN_FS.'/config/ldap.ini')) {
			CMS_file::chmodFile(FILES_CHMOD, PATH_PACKAGES_FS.'/modules/cms_ldap_rc.xml');
			CMS_file::chmodFile(FILES_CHMOD, PATH_MAIN_FS.'/config/ldap.ini');
			echo "Module LDAP installation : Installation done.<br /><br />";
		} else {
			echo "Module LDAP installation : INSTALLATION ERROR ! Can not copy parameters file or LDAP config file ...<br />";
		}
	} else {
		echo "Module LDAP installation : INSTALLATION ERROR ! Problem in SQL syntax (SQL tables file) ...<br />";
	}
} else {
	//load destination module parameters
	$module = CMS_modulesCatalog::getByCodename('cms_ldap');
	$moduleParameters = $module->getParameters(false,true);
	if (!is_array($moduleParameters)) {
		$moduleParameters = array();
	}
	//load the XML data of the source the files
	$sourceXML = new CMS_file(PATH_TMP_FS.PATH_PACKAGES_WR.'/modules/cms_ldap_rc.xml');
	$domdocument = new CMS_DOMDocument();
	try {
		$domdocument->loadXML($sourceXML->readContent("string"));
	} catch (DOMException $e) {}
	$paramsTags = $domdocument->getElementsByTagName('param');
	$sourceParameters = array();
	foreach ($paramsTags as $aTag) {
		$name = ($aTag->hasAttribute('name')) ? $aTag->getAttribute('name') : '';
		$type = ($aTag->hasAttribute('type')) ? $aTag->getAttribute('type') : '';
		$sourceParameters[$name] = array(CMS_DOMDocument::DOMElementToString($aTag, true),$type);
	}
	//merge the two tables of parameters
	$resultParameters = array_merge($sourceParameters,$moduleParameters);
	//set new parameters to the module
	if ($module->setAndWriteParameters($resultParameters)) {
		echo 'Modules parameters successfully merged<br />';
		echo "Module LDAP installation : Already installed : update done.<br />";
	} else {
		echo "UPDATE ERROR ! Problem for merging modules parameters ...<br /><br />";
	}
}
?>