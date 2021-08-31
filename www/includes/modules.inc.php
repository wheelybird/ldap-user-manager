<?php

 #Modules and how they can be accessed.

 #access:
 #auth = need to be logged-in to see it
 #hidden_on_login = only visible when not logged in
 #admin = need to be logged in as an admin to see it

if (isset($LDAP['account_additional_attributes']) && isset($LDAP['account_attributes_personal'])) {
  $MODULES = array(
    'log_in'                => 'hidden_on_login',
    'account_manager'       => 'admin',
    'change_password'       => 'auth',
    'additional_attributes' => 'auth',
    'log_out'               => 'auth',
  );
}

else {
  $MODULES = array(
    'log_in'          => 'hidden_on_login',
    'account_manager' => 'admin',
    'change_password' => 'auth',
    'log_out'         => 'auth',
  );
}

if ($ACCOUNT_REQUESTS_ENABLED == TRUE) {
  $MODULES['request_account'] = 'hidden_on_login';
}

?>
