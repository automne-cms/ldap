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
  * Class CMS_ldap_auth
  *
  * Manage ldap user authentification
  *
  * @package Automne
  * @subpackage cms_ldap
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

class CMS_ldap_auth extends CMS_grandFather implements Zend_Auth_Adapter_Interface
{
    var $_params;
	var $_result;
	var $_userDN;
	var $_options = array();
	var $_ldapOptions = array();
	var $_messages = array();
	
	/**
     * Set authentification paramaters
     *
     * @return void
     */
    public function __construct($params)
    {
        $this->_params = $params;
    }
	
    /**
     * Try to authenticate user from :
	 * SSO
	 * Given parameters
	 *
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
		if (isset($this->_params['authType'])) {
			switch ($this->_params['authType']) {
				case 'credentials':
					//Load LDAP options
					$options = CMS_module_cms_ldap::getLdapConfig();
					if ($options) {
						$this->_ldapOptions = $options->ldap->toArray();
						$this->_options = $options->automne->toArray();
						//PARAMS DATAS
						if (isset($this->_params['login']) && isset($this->_params['password']) && $this->_params['login'] && $this->_params['password']) {
							//check token
							if (isset($this->_params['tokenName']) && $this->_params['tokenName']
								&& (!isset($this->_params['token']) || !$this->_params['token'] || !CMS_session::checkToken($this->_params['tokenName'], $this->_params['token']))) {
								
								$this->_messages[] = CMS_auth::AUTH_INVALID_TOKEN;
								$this->_result = new Zend_Auth_Result(Zend_Auth_Result::FAILURE, null, $this->_messages);
							} else {
								try {
									$adapter = new Zend_Auth_Adapter_Ldap(array($this->_ldapOptions), $this->_params['login'], $this->_params['password']);
									$this->_result = $adapter->authenticate();
									//if authentification success
									switch ($this->_result->getCode()) {
										case Zend_Auth_Result::SUCCESS:
											$this->_messages[] = $this->_result->getMessages();
											//get user infos according to options
											$ldap = $adapter->getLdap();
											$acctname = $ldap->getCanonicalAccountName($this->_result->getIdentity(), Zend_Ldap::ACCTNAME_FORM_DN);
											if ($acctname) {
												$hm = $ldap->getEntry($acctname);
												if ($hm) {
													$this->_userDN = $acctname;
												}
											}
											if (!isset($this->_userDN)) {
												$this->_messages[] = CMS_auth::AUTH_INVALID_CREDENTIALS;
												$this->_result = new Zend_Auth_Result(Zend_Auth_Result::FAILURE, null, $this->_messages);
											}
										break;
										case Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND:
									    	//Delete user if this user exist in DB and has an UID
											$ldap = $adapter->getLdap();
											$ldapOptions = $ldap->getOptions();
											$acctname = $ldap->getCanonicalAccountName($this->_params['login'], $ldapOptions['accountCanonicalForm']);
											$invalidUser = CMS_ldap_userCatalog::getByLogin($acctname);
											if ($invalidUser && $invalidUser->getDN()) {
												$module = CMS_modulesCatalog::getByCodename('cms_ldap');
												$parameter = $module->getParameters('DELETE_INVALID_LDAP_USERS');
												if (sensitiveIO::isPositiveInteger($parameter)) {
													$invalidUser->setDeleted(true);
													$invalidUser->setActive(false);
													$log = new CMS_log();
													$log->logMiscAction(CMS_log::LOG_ACTION_PROFILE_USER_EDIT, $invalidUser, "Auto delete invalid LDAP user : ".$invalidUser->getFullName());
												} else {
													$invalidUser->setActive(false);
													$log = new CMS_log();
													$log->logMiscAction(CMS_log::LOG_ACTION_PROFILE_USER_EDIT, $invalidUser, "Auto desactivate invalid LDAP user : ".$invalidUser->getFullName());
												}
												$invalidUser->writeToPersistence();
												unset($invalidUser);
											}
										break;
										case Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID:
									        //nothing for now
										break;
										case Zend_Auth_Result::FAILURE:
										default:
											CMS_grandFather::raiseError('LDAP Authentification return code '.$this->_result->getCode().' with messages '.print_r($this->_result->getMessages(), true));
										break;
									}
								} catch (Exception $e) {
									$this->raiseError($e->getMessage());
								}
							}
						}
					}
				break;
				case 'session':
					//Not handled
				break;
				case 'cookie':
					//Not handled
				break;
				case 'sso':
					//Load LDAP options
					$options = CMS_module_cms_ldap::getLdapConfig();
					if ($options) {
						$this->_ldapOptions = $options->ldap->toArray();
						$this->_options = $options->automne->toArray();
						
						$ssoLogin = '';
						if (defined('MOD_CMS_LDAP_SSO_LOGIN') && MOD_CMS_LDAP_SSO_LOGIN) {
							$ssoLogin = MOD_CMS_LDAP_SSO_LOGIN;
						} elseif (defined('MOD_CMS_LDAP_SSO_FUNCTION') && MOD_CMS_LDAP_SSO_FUNCTION) {
							if (is_callable(MOD_CMS_LDAP_SSO_FUNCTION, false)) {//check if function/method name exists.
								if (io::strpos(MOD_CMS_LDAP_SSO_FUNCTION, '::') !== false) {//static method call
									$method = explode('::', MOD_CMS_LDAP_SSO_FUNCTION);
									$ssoLogin = call_user_func(array($method[0], $method[1]));
								} else { //function call
									$ssoLogin = call_user_func(MOD_CMS_LDAP_SSO_FUNCTION);
								}
							} else {
								$this->raiseError('Cannot call SSO method/function: '.MOD_CMS_LDAP_SSO_FUNCTION);
							}
						}
						if ($ssoLogin) {
							try {
								//get user infos according to options
								$ldap = new Zend_Ldap($this->_ldapOptions);
								$acctname = $ldap->getCanonicalAccountName($ssoLogin, Zend_Ldap::ACCTNAME_FORM_DN);
								if ($acctname) {
									$hm = $ldap->getEntry($acctname);
									if ($hm) {
										$this->_userDN = $acctname;
										$this->_messages[] = CMS_auth::AUTH_SSOLOGIN_VALID;
										$this->_result = new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $ssoLogin, $this->_messages);
										return $this->_result;
									}
								}
								if (!isset($this->_userDN)) {
									$this->_messages[] = CMS_auth::AUTH_SSOLOGIN_INVALID_USER;
									$this->_result = new Zend_Auth_Result(Zend_Auth_Result::FAILURE, null, $this->_messages);
								}
							} catch (Exception $e) {
								$this->raiseError($e->getMessage());
							}
						}
					}
				break;
				default:
					CMS_grandFather::raiseError('Unknown authType: '.$this->_params['authType']);
				break;
			}
		}
		//No result founded
		if (!$this->_result) {
			$this->_messages[] = CMS_auth::AUTH_MISSING_CREDENTIALS;
			$this->_result = new Zend_Auth_Result(Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND, null, $this->_messages);
		}
		return $this->_result;
    }
    
    /**
	  * Get CMS_profile_user from his Id
	  * 
	  * @param mixed $userId the user id to get
	  * @return CMS_profile_user, false otherwise
	  * @access public
	  */
    function getUser($userId) {
		if (io::isPositiveInteger($userId)) { //userId is a CMS_profile_user id
			$user = CMS_profile_usersCatalog::getByID($userId);
		} else { //userId is a CMS_profile_user id
			$user = CMS_profile_usersCatalog::getByLogin($userId);
		}
		//If user is founded and auth adapter can update user : update it
		//ex : LDAP login and Automne user
		if (isset($user) && $user) {
			$user = $this->updateUser($user);
		} else {
			//If user is not founded but auth adapter can create user : try to create it
			//ex : LDAP login but no Automne user
			$user = $this->createUser();
		}
		return $user;
    }
	
	/**
	  * Update users infos and groups according to LDAP infos
	  *
	  * @param CMS_profile_user $user : the user to update
	  * @return CMS_profile_user : the updated user
	  * @access public
	  */
	public function updateUser($user) {
		//can we update the user infos ?
		if (!isset($this->_options['updateAutomneUsersInfos']) || !$this->_options['updateAutomneUsersInfos']) {
			return $user;
		}
		//do we have user infos to update it ?
		if (!$this->_userDN || !$this->_result->getIdentity()) {
			return $user;
		}
		//load CMS_ldap_user
		$user = CMS_ldap_userCatalog::getByID($user->getUserId());
		
		if (!$user->updateUserInfos() || $user->hasError()) {
			//user has an error : desactivate it
			$user->setActive(false);
			//and write it
			$user->writeToPersistence();
		}
		return $user;
	}
	
	/**
	  * Create CMS_profile_user according to LDAP infos
	  *
	  * @return CMS_profile_user : the user or false if an error occur
	  * @access public
	  */
	public function createUser() {
		//can we create the missing user ?
		if (!isset($this->_options['createMissingUser']) || !$this->_options['createMissingUser']) {
			return false;
		}
		//do we have user infos to create it ?
		if (!$this->_userDN || !$this->_result->getIdentity()) {
			return false;
		}
		//get default user group if any
		$defaultGroup = '';
		if ($this->_getDefaultGroup()) {
			// Use default if given
			$defaultGroup = $this->_getDefaultGroup();
		}
		//instanciate new user
		$user = new CMS_ldap_user();
		// Check login and DN to be unique
		if(CMS_profile_usersCatalog::loginExists($this->_result->getIdentity(), $user)
				|| CMS_ldap_userCatalog::dnExists($this->_userDN, $user)) {
			$this->raiseError('User login ('.$this->_result->getIdentity().') or dn ('.$this->_userDN.') already in use, can\'t register twice.');
			return false;
		}
		//set login
		$user->setLogin($this->_result->getIdentity());
		//DN (and get all user infos from LDAP)
		if (!$user->setDN($this->_userDN, true)) {
			$this->raiseError('Failed to add dn '.$this->_userDN.' to user '.$this->_result->getIdentity());
			return false;
		}
		//add default groups
		if (is_a($defaultGroup, 'CMS_profile_usersGroup') && !$defaultGroup->hasError()) {
			// Add user to its group
			if (!$defaultGroup->addToUserAndWriteToPersistence($user)) {
				$this->raiseError("Failed add default group to user ".$this->_result->getIdentity());
			}
		}
		if ($user->hasError()) {
			return false;
		}
		//user is successfully created so activate user
		$user->setActive(true);
		//and write it
		$user->writeToPersistence();
		
		return $user;
	}
	
	/**
      * Get default LDAP group 
	  * Default : APPLICATION_CMS_LDAP_DEFAULT_GROUP or nothing;
      *
      * @return string
      * @access private
      */
	private function _getDefaultGroup() {
		$module = CMS_modulesCatalog::getByCodename('cms_ldap');
		$parameter = $module->getParameters('DEFAULT_CREATED_USERS_GROUP');
		if (sensitiveIO::isPositiveInteger($parameter)) {
			return CMS_profile_usersGroupsCatalog::getByID($parameter);
		} else {
			return false;
		}
	}
}
?>