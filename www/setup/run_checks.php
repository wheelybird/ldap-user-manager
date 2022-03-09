<?php

set_include_path( ".:" . __DIR__ . "/../includes/");

include_once "web_functions.inc.php";
include_once "ldap_functions.inc.php";
include_once "module_functions.inc.php";

validate_setup_cookie();
set_page_access("setup");

render_header("$ORGANISATION_NAME account manager setup");

$show_finish_button = TRUE;

$ldap_connection = open_ldap_connection();

?>
<script>
    $(document).ready(function(){
     $('[data-toggle="popover"]').popover();
    });
</script>
<div class="form-group">
  <form action="<?php print $THIS_MODULE_PATH; ?>/setup_ldap.php" method="post">
  <input type="hidden" name="fix_problems">


    <div class='container'>

     <div class="panel panel-default">
      <div class="panel-heading">LDAP connection tests</div>
      <div class="panel-body">
       <ul class="list-group">
<?php

#Can we connect?  The open_ldap_connection() function will call die() if we can't.
print "$li_good Connected to ${LDAP['uri']}</li>\n";

#TLS?
if ($LDAP['connection_type'] != "plain") {
 print "$li_good Encrypted connection to ${LDAP['uri']} via ${LDAP['connection_type']}</li>\n";
}
else {
 print "$li_warn Unable to connect to ${LDAP['uri']} via StartTLS. ";
 print "<a href='#' data-toggle='popover' title='StartTLS' data-content='";
 print "The connection to the LDAP server works, but encrypted communication can&#39;t be enabled.";
 print "'>What's this?</a></li>\n";
}


?>
       </ul>
      </div>
     </div>

     <div class="panel panel-default">
      <div class="panel-heading">LDAP RFC2307BIS schema check</div>
      <div class="panel-body">
       <ul class="list-group">
<?php

$bis_detected = ldap_detect_rfc2307bis($ldap_connection);

if ($bis_detected == TRUE) {

 if ($LDAP['forced_rfc2307bis'] == TRUE) {
  print "$li_warn FORCE_RFC2307BIS is set to TRUE which means the user manager skipped auto-detecting the RFC2307BIS schema. This could result in errors when creating groups if your LDAP server hasn't actually got the RFC2307BIS schema available. ";
 }
 else {
  print "$li_good The RFC2307BIS schema appears to be available. ";
 }
 print "<a href='#' data-toggle='popover' title='RFC2307BIS schema' data-content='";
 print "The RFC2307BIS schema enhances posixGroups, allowing you to use \"memberOf\" in LDAP searches.";
 print "'>What's this?</a>";
 print "</li>\n";

}
else {

 print "$li_warn The RFC2307BIS schema doesn't appear to be available.<br>\nIf this is incorrect, set FORCE_RFC2307BIS to TRUE, restart the user manager and run the setup again. ";
 print "<a href='#' data-toggle='popover' title='RFC2307BIS' data-content='";
 print "The RFC2307BIS schema enhances posixGroups, allowing for memberOf LDAP searches.";
 print "'>What's this?</a>";
 print "</li>\n";

}


?>
       </ul>
      </div>
     </div>

     <div class="panel panel-default">
      <div class="panel-heading">LDAP OU checks</div>
      <div class="panel-body">
       <ul class="list-group">
<?php

$group_filter = "(&(objectclass=organizationalUnit)(ou=${LDAP['group_ou']}))";
$ldap_group_search = ldap_search($ldap_connection, "${LDAP['base_dn']}", $group_filter);
$group_result = ldap_get_entries($ldap_connection, $ldap_group_search);

if ($group_result['count'] != 1) {

 print "$li_fail The group OU (<strong>${LDAP['group_dn']}</strong>) doesn't exist. ";
 print "<a href='#' data-toggle='popover' title='${LDAP['group_dn']}' data-content='";
 print "This is the Organizational Unit (OU) that the groups are stored under.";
 print "'>What's this?</a>";
 print "<label class='pull-right'><input type='checkbox' name='setup_group_ou' class='pull-right' checked>Create?&nbsp;</label>";
 print "</li>\n";
 $show_finish_button = FALSE;

}
else {
 print "$li_good The group OU (<strong>${LDAP['group_dn']}</strong>) is present.</li>";
}

$user_filter  = "(&(objectclass=organizationalUnit)(ou=${LDAP['user_ou']}))";
$ldap_user_search = ldap_search($ldap_connection, "${LDAP['base_dn']}", $user_filter);
$user_result = ldap_get_entries($ldap_connection, $ldap_user_search);

if ($user_result['count'] != 1) {

 print "$li_fail The user OU (<strong>${LDAP['user_dn']}</strong>) doesn't exist. ";
 print "<a href='#' data-toggle='popover' title='${LDAP['user_dn']}' data-content='";
 print "This is the Organisational Unit (OU) that the user accounts are stored under.";
 print "'>What's this?</a>";
 print "<label class='pull-right'><input type='checkbox' name='setup_user_ou' class='pull-right' checked>Create?&nbsp;</label>";
 print "</li>\n";
 $show_finish_button = FALSE;

}
else {
 print "$li_good The user OU (<strong>${LDAP['user_dn']}</strong>) is present.</li>";
}

?>
       </ul>
      </div>
     </div>

     <div class="panel panel-default">
      <div class="panel-heading">LDAP group and settings</div>
      <div class="panel-body">
       <ul class="list-group">
<?php

$gid_filter  = "(&(objectclass=device)(cn=lastGID))";
$ldap_gid_search = ldap_search($ldap_connection, "${LDAP['base_dn']}", $gid_filter);
$gid_result = ldap_get_entries($ldap_connection, $ldap_gid_search);

if ($gid_result['count'] != 1) {

 print "$li_warn The <strong>lastGID</strong> entry doesn't exist. ";
 print "<a href='#' data-toggle='popover' title='cn=lastGID,${LDAP['base_dn']}' data-content='";
 print "This is used to store the last group ID used when creating a POSIX group.  Without this the highest current group ID is found and incremented, but this might re-use the GID from a deleted group.";
 print "'>What's this?</a>";
 print "<label class='pull-right'><input type='checkbox' name='setup_last_gid' class='pull-right' checked>Create?&nbsp;</label>";
 print "</li>\n";
 $show_finish_button = FALSE;

}
else {
 print "$li_good The <strong>lastGID</strong> entry is present.</li>";
}


$uid_filter  = "(&(objectclass=device)(cn=lastUID))";
$ldap_uid_search = ldap_search($ldap_connection, "${LDAP['base_dn']}", $uid_filter);
$uid_result = ldap_get_entries($ldap_connection, $ldap_uid_search);

if ($uid_result['count'] != 1) {

 print "$li_warn The <strong>lastUID</strong> entry doesn't exist. ";
 print "<a href='#' data-toggle='popover' title='cn=lastUID,${LDAP['base_dn']}' data-content='";
 print "This is used to store the last user ID used when creating a POSIX account.  Without this the highest current user ID is found and incremented, but this might re-use the UID from a deleted account.";
 print "'>What's this?</a>";
 print "<label class='pull-right'><input type='checkbox' name='setup_last_uid' class='pull-right' checked>Create?&nbsp;</label>";
 print "</li>\n";
 $show_finish_button = FALSE;

}
else {
 print "$li_good The <strong>lastUID</strong> entry is present.</li>";
}


$defgroup_filter  = "(&(objectclass=posixGroup)(${LDAP['group_attribute']}=${DEFAULT_USER_GROUP}))";
$ldap_defgroup_search = ldap_search($ldap_connection, "${LDAP['base_dn']}", $defgroup_filter);
$defgroup_result = ldap_get_entries($ldap_connection, $ldap_defgroup_search);

if ($defgroup_result['count'] != 1) {

 print "$li_warn The default group (<strong>$DEFAULT_USER_GROUP</strong>) doesn't exist. ";
 print "<a href='#' data-toggle='popover' title='Default user group' data-content='";
 print "When we add users we need to assign them a default group ($DEFAULT_USER_GROUP). If this doesn&#39;t exist then a new group will be created to match each user account, which may not be desirable.";
 print "'>What's this?</a>";
 print "<label class='pull-right'><input type='checkbox' name='setup_default_group' class='pull-right' checked>Create?&nbsp;</label>";
 print "</li>\n";
 $show_finish_button = FALSE;

}
else {
 print "$li_good The default user group (<strong>$DEFAULT_USER_GROUP</strong>) is present.</li>";
}


$adminsgroup_filter  = "(&(objectclass=posixGroup)(${LDAP['group_attribute']}=${LDAP['admins_group']}))";
$ldap_adminsgroup_search = ldap_search($ldap_connection, "${LDAP['base_dn']}", $adminsgroup_filter);
$adminsgroup_result = ldap_get_entries($ldap_connection, $ldap_adminsgroup_search);

if ($adminsgroup_result['count'] != 1) {

 print "$li_fail The group defining LDAP account administrators (<strong>${LDAP['admins_group']}</strong>) doesn't exist. ";
 print "<a href='#' data-toggle='popover' title='LDAP account administrators group' data-content='";
 print "Only members of this group (${LDAP['admins_group']}) will be able to access the account managment section, so it&#39;s definitely something you&#39;ll want to create.";
 print "'>What's this?</a>";
 print "<label class='pull-right'><input type='checkbox' name='setup_admins_group' class='pull-right' checked>Create?&nbsp;</label>";
 print "</li>\n";
 $show_finish_button = FALSE;

}
else {
 print "$li_good The LDAP account administrators group (<strong>${LDAP['admins_group']}</strong>) is present.</li>";

 $admins = ldap_get_group_members($ldap_connection,$LDAP['admins_group']);

 if (count($admins) < 1) {
  print "$li_fail The LDAP administration group is empty. You can add an admin account in the next section.</li>";
  $show_finish_button = FALSE;
 }
}





?>
       </ul>
      </div>
     </div>
<?php

##############

if ($show_finish_button == TRUE) {
?>
     </form>
     <div class='well'>
      <form action="<?php print "${SERVER_PATH}log_in"; ?>">
       <input type='submit' class="btn btn-success center-block" value='Done'>
      </form>
     </div>
<?php
}
else {
?>
     <div class='well'>
      <input type='submit' class="btn btn-primary center-block" value='Next >'>
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
