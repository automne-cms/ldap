#----------------------------------------------------------------
#
# Messages content for module cms_ldap
# French Messages
#
#----------------------------------------------------------------

DELETE FROM messages WHERE module_mes = 'cms_ldap' and language_mes = 'fr';

INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(1, 'cms_ldap', 'fr', 'LDAP');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(2, 'cms_ldap', 'fr', 'Si le champ Nom Distingu&eacute; (DN) est rempli, l\'utilisateur sera rattach&eacute; au compte LDAP correspondant &agrave; ce Nom Distingu&eacute;. Son Identifiant et son mot de passe seront ceux de son compte LDAP. Ses donn&eacute;es utilisateur seront mises &agrave; jour depuis le LDAP &agrave; chaque connexion.');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(3, 'cms_ldap', 'fr', 'DN du compte LDAP');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(4, 'cms_ldap', 'fr', 'Nom Distingu&eacute;');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(5, 'cms_ldap', 'fr', 'Si l\'une des valeur ci-dessous est pr&eacute;sente, alors ce groupe utilisateur sera asservi au LDAP. Les utilisateurs qui le composent seront automatiquement associ&eacute;s ou d&eacute;sassoci&eacute;s en fonction de leur respect des propri&eacute;t&eacute;s d&eacute;crites ci-dessous.');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(6, 'cms_ldap', 'fr', 'Base DN auquel le groupe s\'attache');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(7, 'cms_ldap', 'fr', 'Filtre auquel le groupe s\'attache');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(8, 'cms_ldap', 'fr', 'Filtre');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(9, 'cms_ldap', 'fr', 'Groupe d\'utilisateurs LDAP');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(10, 'cms_ldap', 'fr', 'Groupe LDAP');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(11, 'cms_ldap', 'fr', 'Identification LDAP');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(12, 'cms_ldap', 'fr', 'Liaison LDAP');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(13, 'cms_ldap', 'fr', 'Le DN est invalide, n\'existe pas ou présente une erreur ...');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(14, 'cms_ldap', 'fr', 'Base DN');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(15, 'cms_ldap', 'fr', 'Groupe par défaut');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(16, 'cms_ldap', 'fr', 'Identifiant du groupe utilisateur associé par défaut aux utilisateurs créés depuis le LDAP. Laisser vide si il n\'y en a pas.');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(17, 'cms_ldap', 'fr', 'Vérification quotidienne des utilisateurs LDAP');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(18, 'cms_ldap', 'fr', 'Vérifier quotidiennement que les utilisateurs LDAP de la base Automne sont toujours valides. Si ils ne sont plus valides, ils seront désactivés. Si cette option n\'est pas activée, les utilisateurs resteront dans la base Automne jusqu\'à suppression manuelle.');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(19, 'cms_ldap', 'fr', 'Supprimer les utilisateurs invalides');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(20, 'cms_ldap', 'fr', 'Si cette option est active, les utilisateurs invalides de la base Automne seront supprimés au lieu d\'être désactivés.');
