<?php

set_include_path( ".:" . __DIR__ . "/../includes/");

include_once "web_functions.inc.php";
include_once "ldap_functions.inc.php";
include_once "module_functions.inc.php";

validate_setup_cookie();
set_page_access("setup");

render_header($ORGANISATION_NAME . ' ' . t('setup.header.title'));

$show_finish_button = TRUE;

$ldap_connection = open_ldap_connection();

?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
      var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
      var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
      });
    });
</script>
<div class="row mb-3">
  <form action="<?php print $THIS_MODULE_PATH; ?>/setup_ldap.php" method="post">
  <input type="hidden" name="fix_problems">


    <div class='container'>

     <div class="card">
      <div class="card-header"><?php print t('setup.card.ldap_connection_tests'); ?></div>
      <div class="card-body">
       <ul class="list-group">
<?php

#Can we connect?  The open_ldap_connection() function will call die() if we can't.
print $li_good . t('setup.check.connected', array('uri' => $LDAP['uri'])) . "</li>\n";

#TLS?
if ($LDAP['connection_type'] != "plain") {
 print $li_good . t('setup.check.encrypted_connection', array('uri' => $LDAP['uri'], 'type' => $LDAP['connection_type'])) . "</li>\n";
}
else {
 print $li_warn . t('setup.check.starttls_unavailable', array('uri' => $LDAP['uri'])) . " ";
 print "<a href='#' data-bs-toggle='popover' data-bs-trigger='hover focus' title='" . t('setup.popover.starttls.title') . "' data-bs-content='" . t('setup.popover.starttls.content') . "'>";
 print t('setup.what_is_this') . "</a></li>\n";
}


?>
       </ul>
      </div>
     </div>

     <div class="card">
      <div class="card-header"><?php print t('setup.card.rfc2307bis_check'); ?></div>
      <div class="card-body">
       <ul class="list-group">
<?php

$bis_detected = ldap_detect_rfc2307bis($ldap_connection);

if ($bis_detected == TRUE) {

 if ($LDAP['forced_rfc2307bis'] == TRUE) {
  print $li_warn . t('setup.check.rfc2307bis_forced') . " ";
 }
 else {
  print $li_good . t('setup.check.rfc2307bis_available') . " ";
 }
 print "<a href='#' data-bs-toggle='popover' data-bs-trigger='hover focus' title='" . t('setup.popover.rfc2307bis.title') . "' data-bs-content='" . t('setup.popover.rfc2307bis.content') . "'>";
 print t('setup.what_is_this') . "</a>";
 print "</li>\n";

}
else {

 print $li_warn . t('setup.check.rfc2307bis_missing') . " ";
 print "<a href='#' data-bs-toggle='popover' data-bs-trigger='hover focus' title='" . t('setup.popover.rfc2307bis_short.title') . "' data-bs-content='" . t('setup.popover.rfc2307bis_short.content') . "'>";
 print t('setup.what_is_this') . "</a>";
 print "</li>\n";

}


?>
       </ul>
      </div>
     </div>

     <div class="card">
      <div class="card-header"><?php print t('setup.card.mfa_totp_check'); ?></div>
      <div class="card-body">
       <ul class="list-group">
<?php

// Check MFA configuration and schema availability
if ($MFA_FEATURE_ENABLED == TRUE) {

 $totp_objectclass = $TOTP_ATTRS['objectclass'];
 $totp_secret_attr = $TOTP_ATTRS['secret'];
 $totp_status_attr = $TOTP_ATTRS['status'];
 $totp_enrolled_attr = $TOTP_ATTRS['enrolled_date'];
 $totp_scratch_attr = $TOTP_ATTRS['scratch_codes'];

 // Check if objectClass exists in schema
 $oc_search = @ldap_read($ldap_connection, "cn=subschema", "(objectClass=*)", array("objectClasses"));
 $schema_found = false;
 $missing_attrs = array();

 if ($oc_search) {
  $schema_entry = ldap_get_entries($ldap_connection, $oc_search);
  if (isset($schema_entry[0]['objectclasses'])) {
   foreach ($schema_entry[0]['objectclasses'] as $oc) {
    if (stripos($oc, $totp_objectclass) !== false) {
     $schema_found = true;
     break;
    }
   }
  }

  // Check for attributes
  if ($schema_found) {
   $attr_search = @ldap_read($ldap_connection, "cn=subschema", "(objectClass=*)", array("attributeTypes"));
   if ($attr_search) {
    $attr_entry = ldap_get_entries($ldap_connection, $attr_search);
    if (isset($attr_entry[0]['attributetypes'])) {
     $found_attrs = array();
     foreach ($attr_entry[0]['attributetypes'] as $attr) {
      if (stripos($attr, "NAME '$totp_secret_attr'") !== false) $found_attrs[] = $totp_secret_attr;
      if (stripos($attr, "NAME '$totp_status_attr'") !== false) $found_attrs[] = $totp_status_attr;
      if (stripos($attr, "NAME '$totp_enrolled_attr'") !== false) $found_attrs[] = $totp_enrolled_attr;
      if (stripos($attr, "NAME '$totp_scratch_attr'") !== false) $found_attrs[] = $totp_scratch_attr;
     }

     $required_attrs = array($totp_secret_attr, $totp_status_attr, $totp_enrolled_attr, $totp_scratch_attr);
     $missing_attrs = array_diff($required_attrs, $found_attrs);
    }
   }
  }
 }

 if ($schema_found && empty($missing_attrs)) {
  print $li_good . t('setup.check.mfa_schema_present', array('objectclass' => $totp_objectclass)) . " ";
  print "<a href='#' data-bs-toggle='popover' data-bs-trigger='hover focus' title='" . t('setup.popover.mfa_schema.title') . "' data-bs-content='" . t('setup.popover.mfa_schema.content') . "'>";
  print t('setup.what_is_this') . "</a></li>\n";
 }
 elseif ($schema_found && !empty($missing_attrs)) {
  print $li_warn . t('setup.check.mfa_schema_missing_attrs', array('objectclass' => $totp_objectclass, 'attrs' => implode(', ', $missing_attrs))) . "<br>\n";
  print t('setup.check.mfa_not_functional_until_attrs') . " ";
  print "<a href='#' data-bs-toggle='popover' data-bs-trigger='hover focus' title='" . t('setup.popover.mfa_missing_attrs.title') . "' data-bs-content='" . t('setup.popover.mfa_missing_attrs.content') . "'>";
  print t('setup.what_is_this') . "</a></li>\n";
 }
 else {
  print $li_warn . t('setup.check.mfa_schema_missing', array('objectclass' => $totp_objectclass)) . "<br>\n";
  print t('setup.check.mfa_not_functional_until_schema') . " ";
  print "<a href='#' data-bs-toggle='popover' data-bs-trigger='hover focus' title='" . t('setup.popover.mfa_missing_schema.title') . "' data-bs-content='" . t('setup.popover.mfa_missing_schema.content') . "'>";
  print t('setup.what_is_this') . "</a></li>\n";
 }

}
else {
 print $li_good . t('setup.check.mfa_disabled') . "</li>\n";
}

?>
       </ul>
      </div>
     </div>

     <div class="card">
      <div class="card-header"><?php print t('setup.card.ldap_ou_checks'); ?></div>
      <div class="card-body">
       <ul class="list-group">
<?php

$group_filter = "(&(objectclass=organizationalUnit)(ou={$LDAP['group_ou']}))";
$ldap_group_search = ldap_search($ldap_connection, "{$LDAP['base_dn']}", $group_filter);
$group_result = ldap_get_entries($ldap_connection, $ldap_group_search);

if ($group_result['count'] != 1) {

 print $li_fail . t('setup.check.group_ou_missing', array('group_dn' => $LDAP['group_dn'])) . " ";
 print "<a href='#' data-bs-toggle='popover' data-bs-trigger='hover focus' title='" . $LDAP['group_dn'] . "' data-bs-content='" . t('setup.popover.group_ou.content') . "'>";
 print t('setup.what_is_this') . "</a>";
 print "<label class='float-end'><input type='checkbox' name='setup_group_ou' class='float-end' checked>" . t('setup.create_checkbox') . "&nbsp;</label>";
 print "</li>\n";
 $show_finish_button = FALSE;

}
else {
 print $li_good . t('setup.check.group_ou_present', array('group_dn' => $LDAP['group_dn'])) . "</li>";
}

$user_filter  = "(&(objectclass=organizationalUnit)(ou={$LDAP['user_ou']}))";
$ldap_user_search = ldap_search($ldap_connection, "{$LDAP['base_dn']}", $user_filter);
$user_result = ldap_get_entries($ldap_connection, $ldap_user_search);

if ($user_result['count'] != 1) {

 print $li_fail . t('setup.check.user_ou_missing', array('user_dn' => $LDAP['user_dn'])) . " ";
 print "<a href='#' data-bs-toggle='popover' data-bs-trigger='hover focus' title='" . $LDAP['user_dn'] . "' data-bs-content='" . t('setup.popover.user_ou.content') . "'>";
 print t('setup.what_is_this') . "</a>";
 print "<label class='float-end'><input type='checkbox' name='setup_user_ou' class='float-end' checked>" . t('setup.create_checkbox') . "&nbsp;</label>";
 print "</li>\n";
 $show_finish_button = FALSE;

}
else {
 print $li_good . t('setup.check.user_ou_present', array('user_dn' => $LDAP['user_dn'])) . "</li>";
}

?>
       </ul>
      </div>
     </div>

     <div class="card">
      <div class="card-header"><?php print t('setup.card.ldap_groups_settings'); ?></div>
      <div class="card-body">
       <ul class="list-group">
<?php

$gid_filter  = "(&(objectclass=device)(cn=lastGID))";
$ldap_gid_search = ldap_search($ldap_connection, "{$LDAP['base_dn']}", $gid_filter);
$gid_result = ldap_get_entries($ldap_connection, $ldap_gid_search);

if ($gid_result['count'] != 1) {

 print $li_warn . t('setup.check.last_gid_missing') . " ";
 print "<a href='#' data-bs-toggle='popover' data-bs-trigger='hover focus' title='cn=lastGID,{$LDAP['base_dn']}' data-bs-content='" . t('setup.popover.last_gid.content') . "'>";
 print t('setup.what_is_this') . "</a>";
 print "<label class='float-end'><input type='checkbox' name='setup_last_gid' class='float-end' checked>" . t('setup.create_checkbox') . "&nbsp;</label>";
 print "</li>\n";
 $show_finish_button = FALSE;

}
else {
 print $li_good . t('setup.check.last_gid_present') . "</li>";
}


$uid_filter  = "(&(objectclass=device)(cn=lastUID))";
$ldap_uid_search = ldap_search($ldap_connection, "{$LDAP['base_dn']}", $uid_filter);
$uid_result = ldap_get_entries($ldap_connection, $ldap_uid_search);

if ($uid_result['count'] != 1) {

 print $li_warn . t('setup.check.last_uid_missing') . " ";
 print "<a href='#' data-bs-toggle='popover' data-bs-trigger='hover focus' title='cn=lastUID,{$LDAP['base_dn']}' data-bs-content='" . t('setup.popover.last_uid.content') . "'>";
 print t('setup.what_is_this') . "</a>";
 print "<label class='float-end'><input type='checkbox' name='setup_last_uid' class='float-end' checked>" . t('setup.create_checkbox') . "&nbsp;</label>";
 print "</li>\n";
 $show_finish_button = FALSE;

}
else {
 print $li_good . t('setup.check.last_uid_present') . "</li>";
}


$defgroup_filter  = "(&(objectclass=posixGroup)({$LDAP['group_attribute']}={$DEFAULT_USER_GROUP}))";
$ldap_defgroup_search = ldap_search($ldap_connection, "{$LDAP['base_dn']}", $defgroup_filter);
$defgroup_result = ldap_get_entries($ldap_connection, $ldap_defgroup_search);

if ($defgroup_result['count'] != 1) {

 print $li_warn . t('setup.check.default_group_missing', array('group' => $DEFAULT_USER_GROUP)) . " ";
 print "<a href='#' data-bs-toggle='popover' data-bs-trigger='hover focus' title='" . t('setup.popover.default_group.title') . "' data-bs-content='" . t('setup.popover.default_group.content', array('group' => $DEFAULT_USER_GROUP)) . "'>";
 print t('setup.what_is_this') . "</a>";
 print "<label class='float-end'><input type='checkbox' name='setup_default_group' class='float-end' checked>" . t('setup.create_checkbox') . "&nbsp;</label>";
 print "</li>\n";
 $show_finish_button = FALSE;

}
else {
 print $li_good . t('setup.check.default_group_present', array('group' => $DEFAULT_USER_GROUP)) . "</li>";
}


$adminsgroup_filter  = "(&(objectclass=posixGroup)({$LDAP['group_attribute']}={$LDAP['admins_group']}))";
$ldap_adminsgroup_search = ldap_search($ldap_connection, "{$LDAP['base_dn']}", $adminsgroup_filter);
$adminsgroup_result = ldap_get_entries($ldap_connection, $ldap_adminsgroup_search);

if ($adminsgroup_result['count'] != 1) {

 print $li_fail . t('setup.check.admin_group_missing', array('group' => $LDAP['admins_group'])) . " ";
 print "<a href='#' data-bs-toggle='popover' data-bs-trigger='hover focus' title='" . t('setup.popover.admin_group.title') . "' data-bs-content='" . t('setup.popover.admin_group.content', array('group' => $LDAP['admins_group'])) . "'>";
 print t('setup.what_is_this') . "</a>";
 print "<label class='float-end'><input type='checkbox' name='setup_admins_group' class='float-end' checked>" . t('setup.create_checkbox') . "&nbsp;</label>";
 print "</li>\n";
 $show_finish_button = FALSE;

}
else {
 print $li_good . t('setup.check.admin_group_present', array('group' => $LDAP['admins_group'])) . "</li>";

 $admins = ldap_get_group_members($ldap_connection,$LDAP['admins_group']);

 if (count($admins) < 1) {
  print $li_fail . t('setup.check.admin_group_empty') . "</li>";
  $show_finish_button = FALSE;
 }
}





?>
       </ul>
      </div>
     </div>

<?php

// Only show LDAP storage check if it's enabled
if ($USE_LDAP_AS_DB == TRUE) {
?>
     <div class="card">
      <div class="card-header"><?php print t('setup.card.ldap_storage_data'); ?></div>
      <div class="card-body">
       <ul class="list-group">
<?php

// Check for applications OU
$apps_ou_filter = "(&(objectclass=organizationalUnit)(ou=applications))";
$ldap_apps_search = ldap_search($ldap_connection, "{$LDAP['base_dn']}", $apps_ou_filter);
$apps_result = ldap_get_entries($ldap_connection, $ldap_apps_search);

if ($apps_result['count'] != 1) {
 print $li_warn . t('setup.check.apps_ou_missing', array('base_dn' => $LDAP['base_dn'])) . " ";
 print "<a href='#' data-bs-toggle='popover' data-bs-trigger='hover focus' title='" . t('setup.popover.apps_ou.title') . "' data-bs-content='" . t('setup.popover.apps_ou.content') . "'>";
 print t('setup.what_is_this') . "</a>";
 print "<label class='float-end'><input type='checkbox' name='setup_apps_ou' class='float-end' checked>" . t('setup.create_checkbox') . "&nbsp;</label>";
 print "</li>\n";
 $show_finish_button = FALSE;
}
else {
 print $li_good . t('setup.check.apps_ou_present', array('base_dn' => $LDAP['base_dn'])) . "</li>";
}

// Check for cn=luminary entry
$luminary_filter = "(&(objectclass=device)(cn=luminary))";
$ldap_luminary_search = ldap_search($ldap_connection, "{$LDAP['base_dn']}", $luminary_filter);
$luminary_result = ldap_get_entries($ldap_connection, $ldap_luminary_search);

if ($luminary_result['count'] != 1) {
 print $li_warn . t('setup.check.luminary_entry_missing') . " ";
 print "<a href='#' data-bs-toggle='popover' data-bs-trigger='hover focus' title='" . t('setup.popover.luminary_entry.title') . "' data-bs-content='" . t('setup.popover.luminary_entry.content') . "'>";
 print t('setup.what_is_this') . "</a>";
 print "<label class='float-end'><input type='checkbox' name='setup_luminary_entry' class='float-end' checked>" . t('setup.create_checkbox') . "&nbsp;</label>";
 print "</li>\n";
 $show_finish_button = FALSE;
}
else {
 print $li_good . t('setup.check.luminary_entry_present') . "</li>";
}

?>
       </ul>
      </div>
     </div>
<?php
}
?>
<?php

if ($show_finish_button == TRUE) {
?>
     </form>
     <div class='mt-3 text-end'>
      <form action="<?php print "{$SERVER_PATH}log_in"; ?>" class="d-inline">
       <input type='submit' class="btn btn-success" value='<?php print t('setup.button.done'); ?>'>
      </form>
     </div>
<?php
}
else {
?>
     <div class='mt-3 text-end'>
      <input type='submit' class="btn btn-primary" value='<?php print t('setup.button.next'); ?>'>
     </div>
     </form>
<?php
}


?>
 </div>
</div>
<?php

render_footer();

?>