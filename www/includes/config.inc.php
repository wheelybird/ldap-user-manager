<?php

 #Mandatory

 $LDAP['uri'] = getenv('LDAP_URI');
 $LDAP['base_dn'] = getenv('LDAP_BASE_DN');
 $LDAP['admins_group'] = getenv('LDAP_ADMINS_GROUP');
 $LDAP['admin_bind_dn'] = getenv('LDAP_ADMIN_BIND_DN');
 $LDAP['admin_bind_pwd'] = getenv('LDAP_ADMIN_BIND_PWD');


 #Optional

 $LDAP['group_ou'] = (getenv('LDAP_GROUP_OU') ? getenv('LDAP_GROUP_OU') : 'groups');
 $LDAP['user_ou'] = (getenv('LDAP_USER_OU') ? getenv('LDAP_USER_OU') : 'people');

 $LDAP['group_membership_attribute'] = (getenv('LDAP_GROUP_MEMBERSHIP_ATTRIBUTE') ? getenv('LDAP_GROUP_MEMBERSHIP_ATTRIBUTE') : 'uniquemember');
 $LDAP['group_membership_uses_uid'] = ((strcmp(getenv('LDAP_GROUP_MEMBERSHIP_USES_UID'),'TRUE') == 0) ? TRUE : FALSE);

 $LDAP['account_attribute'] = (getenv('LDAP_ACCOUNT_ATTRIBUTE') ? getenv('LDAP_ACCOUNT_ATTRIBUTE') : 'uid');
 $LDAP['require_starttls'] = ((strcmp(getenv('LDAP_REQUIRE_STARTTLS'),'TRUE') == 0) ? TRUE : FALSE);

 $DEFAULT_USER_GROUP = (getenv('DEFAULT_USER_GROUP') ? getenv('DEFAULT_USER_GROUP') : 'everybody');
 $DEFAULT_USER_SHELL = (getenv('DEFAULT_USER_SHELL') ? getenv('DEFAULT_SHELL') : '/bin/bash');
 $EMAIL_DOMAIN = (getenv('EMAIL_DOMAIN') ? getenv('EMAIL_DOMAIN') : Null);

 $LOGIN_TIMEOUT_MINS = (getenv('SESSION_TIMEOUT') ? getenv('SESSION_TIMEOUT') : 10);
 $SITE_NAME = (getenv('SITE_NAME') ? getenv('SITE_NAME') : 'LDAP user manager');

 $USERNAME_FORMAT = (getenv('USERNAME_FORMAT') ? getenv('USERNAME_FORMAT') : '{first_name}-{last_name}');
 $USERNAME_REGEX = '^[a-z][a-zA-Z0-9\._-]{3,32}$';
 #We'll use the username regex for groups too.


 ###

 $LDAP['group_dn'] = "ou=${LDAP['group_ou']},${LDAP['base_dn']}";
 $LDAP['user_dn'] = "ou=${LDAP['user_ou']},${LDAP['base_dn']}";

 ###

 $errors = "";

 if (empty($LDAP['uri'])) {
  $errors .= "<div class='alert alert-warning'><p class='text-center'>LDAP_URI isn't set</p></div>\n";
 }
 if (empty($LDAP['base_dn'])) {
  $errors .= "<div class='alert alert-warning'><p class='text-center'>LDAP_BASE_DN isn't set</p></div>\n";
 }
 if (empty($LDAP['admin_bind_dn'])) {
  $errors .= "<div class='alert alert-warning'><p class='text-center'>LDAP_ADMIN_BIND_DN isn't set</p></div>\n";
 }
 if (empty($LDAP['admin_bind_pwd'])) {
  $errors .= "<div class='alert alert-warning'><p class='text-center'>LDAP_ADMIN_BIND_PWD isn't set</p></div>\n";
 }
 if (empty($LDAP['admins_group'])) {
  $errors .= "<div class='alert alert-warning'><p class='text-center'>LDAP_ADMINS_GROUP isn't set</p></div>\n";
 }

 if ($errors != "") {
  render_header();
  print $errors;
  render_footer();
  exit(1);
 }

 #POSIX accounts
 $min_uid = 2000;
 $min_gid = 2000;


?>
