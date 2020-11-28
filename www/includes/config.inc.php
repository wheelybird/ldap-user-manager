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

 $LDAP['nis_schema'] = ((strcasecmp(getenv('LDAP_USES_NIS_SCHEMA'),'TRUE') == 0) ? TRUE : FALSE);

 if ($LDAP['nis_schema'] == TRUE) {
  $default_membership_attribute = 'memberuid';
  $default_group_membership_uses_uid = TRUE;
 }
 else {
  $default_membership_attribute = 'uniquemember';
  $default_group_membership_uses_uid = FALSE;
 }

 $LDAP['group_membership_attribute'] = (getenv('LDAP_GROUP_MEMBERSHIP_ATTRIBUTE') ? getenv('LDAP_GROUP_MEMBERSHIP_ATTRIBUTE') : $default_membership_attribute);
 $LDAP['group_membership_uses_uid'] = ((strcasecmp(getenv('LDAP_GROUP_MEMBERSHIP_USES_UID'),'TRUE') == 0) ? TRUE : $default_group_membership_uses_uid);

 $LDAP['account_attribute'] = 'uid';
 $LDAP['require_starttls'] = ((strcasecmp(getenv('LDAP_REQUIRE_STARTTLS'),'TRUE') == 0) ? TRUE : FALSE);
 $LDAP['ignore_cert_errors'] = ((strcasecmp(getenv('LDAP_IGNORE_CERT_ERRORS'),'TRUE') == 0) ? TRUE : FALSE);

 $DEFAULT_USER_GROUP = (getenv('DEFAULT_USER_GROUP') ? getenv('DEFAULT_USER_GROUP') : 'everybody');
 $DEFAULT_USER_SHELL = (getenv('DEFAULT_USER_SHELL') ? getenv('DEFAULT_USER_SHELL') : '/bin/bash');

 $ORGANISATION_NAME = (getenv('ORGANISATION_NAME') ? getenv('ORGANISATION_NAME') : 'LDAP');
 $SITE_NAME = (getenv('SITE_NAME') ? getenv('SITE_NAME') : "$ORGANISATION_NAME user manager");

 $USERNAME_FORMAT = (getenv('USERNAME_FORMAT') ? getenv('USERNAME_FORMAT') : '{first_name}-{last_name}');
 $USERNAME_REGEX = (getenv('USERNAME_REGEX') ? getenv('USERNAME_REGEX') : '^[a-z][a-zA-Z0-9\._-]{3,32}$');
 #We'll use the username regex for groups too.

 if (getenv('PASSWORD_HASH')) { $PASSWORD_HASH = strtoupper(getenv('PASSWORD_HASH')); }

 $ACCEPT_WEAK_PASSWORDS = ((strcasecmp(getenv('ACCEPT_WEAK_PASSWORDS'),'TRUE') == 0) ? TRUE : FALSE);
 $LOGIN_TIMEOUT_MINS = (getenv('SESSION_TIMEOUT') ? getenv('SESSION_TIMEOUT') : 10);

 $LDAP_DEBUG = ((strcasecmp(getenv('LDAP_DEBUG'),'TRUE') == 0) ? TRUE : FALSE);
 $LDAP_VERBOSE_CONNECTION_LOGS = ((strcasecmp(getenv('LDAP_VERBOSE_CONNECTION_LOGS'),'TRUE') == 0) ? TRUE : FALSE);

 $SESSION_DEBUG = ((strcasecmp(getenv('SESSION_DEBUG'),'TRUE') == 0) ? TRUE : FALSE);

 ###

 $LDAP['group_dn'] = "ou=${LDAP['group_ou']},${LDAP['base_dn']}";
 $LDAP['user_dn'] = "ou=${LDAP['user_ou']},${LDAP['base_dn']}";

 ###

 $SMTP['host'] = getenv('SMTP_HOSTNAME');
 $SMTP['user'] = getenv('SMTP_USERNAME');
 $SMTP['pass'] = getenv('SMTP_PASSWORD');
 $SMTP['port'] = (getenv('SMTP_HOST_PORT') ? getenv('SMTP_HOST_PORT') : 25);
 $SMTP['tls'] = ((strcasecmp(getenv('SMTP_USE_TLS'),'TRUE') == 0) ? TRUE : FALSE);

 $SMTP['debug_level'] = getenv('SMTP_LOG_LEVEL');
 if (!is_numeric($SMTP['debug_level']) or $SMTP['debug_level'] >4 or $SMTP['debug_level'] <0) { $SMTP['debug_level'] = 1; }

 $EMAIL_DOMAIN = (getenv('EMAIL_DOMAIN') ? getenv('EMAIL_DOMAIN') : Null);
 
 $default_email_from_domain = ($EMAIL_DOMAIN ? $EMAIL_DOMAIN : 'ldapusermanger.org');

 $EMAIL['from_address'] = (getenv('EMAIL_FROM_ADDRESS') ? getenv('EMAIL_FROM_ADDRESS') : "admin@" . $default_email_from_domain );
 $EMAIL['from_name'] = (getenv('EMAIL_FROM_NAME') ? getenv('EMAIL_FROM_NAME') : $SITE_NAME );

 $NO_HTTPS = ((strcasecmp(getenv('NO_HTTPS'),'TRUE') == 0) ? TRUE : FALSE);

 ###

 $log_prefix = "";

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
