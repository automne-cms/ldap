#----------------------------------------------------------------
#
# Messages content for module cms_ldap
# English Messages
#
#----------------------------------------------------------------

DELETE FROM messages WHERE module_mes = 'cms_ldap' and language_mes = 'en';

INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(1, 'cms_ldap', 'en', 'LDAP');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(2, 'cms_ldap', 'en', 'If the Distinguished Name field (DN) is completed, the user will be attached to the LDAP account corresponding to the Distinguished Name. His ID and password will be those of his LDAP account. The user data will be updated from the LDAP at every login.');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(3, 'cms_ldap', 'en', 'DN of the LDAP account');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(4, 'cms_ldap', 'en', 'Distinguished Name');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(5, 'cms_ldap', 'en', 'If one of the following value is present, then this group is controlled by the LDAP. Users who compose it will automatically be associated or disassociated with respect to their properties described below.');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(6, 'cms_ldap', 'en', 'Base DN at which the group focuses');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(7, 'cms_ldap', 'en', 'Filter to which the group focuses');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(8, 'cms_ldap', 'en', 'Filter');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(9, 'cms_ldap', 'en', 'LDAP User Group');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(10, 'cms_ldap', 'en', 'LDAP Group');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(11, 'cms_ldap', 'en', 'LDAP authentication');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(12, 'cms_ldap', 'en', 'LDAP Link');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(13, 'cms_ldap', 'en', 'The DN is invalid, does not exist or has an error...');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(14, 'cms_ldap', 'en', 'Base DN');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(15, 'cms_ldap', 'en', 'Default group');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(16, 'cms_ldap', 'en', 'User group Id associated with the default users created from LDAP. Leave blank if there is none.');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(17, 'cms_ldap', 'en', 'Daily verification of LDAP users');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(18, 'cms_ldap', 'en', 'Check daily that the LDAP Automne user database are still valid. If they are no longer valid, they will be disabled. If this option is not enabled, users will remain in the Automne database until manual removal.');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(19, 'cms_ldap', 'en', 'Delete invalid users');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(20, 'cms_ldap', 'en', 'If this option is enabled, invalid users of the Automne database will be deleted instead of being disabled.');
