##
## Contains declaration for module cms_ldap installation : 
## All table creation (mandatory)
##

# --------------------------------------------------------

--
-- Structure de la table `profilesLdapGroups`
--

DROP TABLE IF EXISTS `profilesLdapGroups`;
CREATE TABLE `profilesLdapGroups` (
  `dn_plg` varchar(255) character set utf8 NOT NULL,
  `filter_plg` varchar(255) character set utf8 NOT NULL,
  `group_plg` varchar(255) character set utf8 NOT NULL,
  `profile_plg` int(11) unsigned NOT NULL,
  UNIQUE KEY `profile_plg` (`profile_plg`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `profilesLdapUsers`
--

DROP TABLE IF EXISTS `profilesLdapUsers`;
CREATE TABLE `profilesLdapUsers` (
  `dn_plu` varchar(255) character set utf8 NOT NULL,
  `profile_plu` int(11) unsigned NOT NULL,
  UNIQUE KEY `profile_plu` (`profile_plu`),
  KEY `dn_plu` (`dn_plu`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

#
# Contenu de la table `modules`
#

INSERT INTO `modules` (`id_mod`, `label_mod`, `codename_mod`, `administrationFrontend_mod`, `hasParameters_mod`, `isPolymod_mod`) VALUES ('', 1, 'cms_ldap', '', 1, 0);