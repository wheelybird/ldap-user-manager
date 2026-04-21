<?php

 #Modules and how they can be accessed.

 #access:
 #auth = need to be logged-in to see it
 #hidden_on_login = only visible when not logged in
 #admin = need to be logged in as an admin to see it

 $MODULES = array(
                    'log_in'          => 'hidden_on_login',
                    'home'            => 'auth',
                    'user_profile'    => 'auth',
                    'change_password' => 'auth',
                    'account_manager' => 'admin',
                    'system_config'   => 'admin',
                  );

 #Module display names (optional - if not set, directory name is used)
 $MODULE_NAMES = array(
                    'log_in'          => t('module.log_in'),
                    'home'            => t('module.home'),
                    'user_profile'    => t('module.user_profile'),
                    'change_password' => t('module.change_password'),
                    'account_manager' => t('module.account_manager'),
                    'system_config'   => t('module.system_config'),
                    'log_out'         => t('module.log_out'),
                    'request_account' => t('module.request_account'),
                    'manage_mfa'      => t('module.manage_mfa'),
                  );

if ($MFA_FEATURE_ENABLED == TRUE) {
  $MODULES['manage_mfa'] = 'auth';
}

if ($ACCOUNT_REQUESTS_ENABLED == TRUE) {
  $MODULES['request_account'] = 'hidden_on_login';
}
if (!$REMOTE_HTTP_HEADERS_LOGIN) {
  $MODULES['log_out'] = 'auth';
}

?>
