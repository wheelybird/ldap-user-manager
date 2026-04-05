<?php

##############################################################################
# CONFIG REGISTRY
##############################################################################

include_once "config_registry.inc.php";

##############################################################################
# MODULE DEFINITIONS
##############################################################################

include_once "modules.inc.php";

##############################################################################
# SANITY CHECKING
# Validate that mandatory configurations are set
##############################################################################

$errors = "";

if (empty($LDAP['uri'])) {
  $errors .= "<div class='alert alert-warning'><p class='text-center'>" . t('config.error.ldap_field_not_set', array('field' => 'LDAP_URI')) . "</p></div>\n";
}
if (empty($LDAP['base_dn'])) {
  $errors .= "<div class='alert alert-warning'><p class='text-center'>" . t('config.error.ldap_field_not_set', array('field' => 'LDAP_BASE_DN')) . "</p></div>\n";
}
if (empty($LDAP['admin_bind_dn'])) {
  $errors .= "<div class='alert alert-warning'><p class='text-center'>" . t('config.error.ldap_field_not_set', array('field' => 'LDAP_ADMIN_BIND_DN')) . "</p></div>\n";
}
if (empty($LDAP['admin_bind_pwd'])) {
  $errors .= "<div class='alert alert-warning'><p class='text-center'>" . t('config.error.ldap_field_not_set', array('field' => 'LDAP_ADMIN_BIND_PWD')) . "</p></div>\n";
}
if (empty($LDAP['admins_group'])) {
  $errors .= "<div class='alert alert-warning'><p class='text-center'>" . t('config.error.ldap_field_not_set', array('field' => 'LDAP_ADMINS_GROUP')) . "</p></div>\n";
}

if ($errors != "") {
  render_header(t('config.error.page_title'),false);
  print $errors;
  render_footer();
  exit(1);
}

?>
