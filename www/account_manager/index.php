<?php

set_include_path( ".:" . __DIR__ . "/../includes/");

include_once "web_functions.inc.php";
include_once "ldap_functions.inc.php";
include_once "module_functions.inc.php";
set_page_access("admin");

render_header("$ORGANISATION_NAME account manager");
render_submenu();

$ldap_connection = open_ldap_connection();

if (isset($_POST['delete_user'])) {

 ?>
 <script>
    window.setTimeout(function() {
                                  $(".alert").fadeTo(500, 0).slideUp(500, function(){ $(this).remove(); });
                                 }, 4000);
 </script>
 <?php

 $this_user = $_POST['delete_user'];
 $this_user = urldecode($this_user);

 $del_user = ldap_delete_account($ldap_connection,$this_user);

 if ($del_user) {
 ?>
 <div class="alert alert-success" role="alert">
  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="TRUE">&times;</span></button>
  <p class="text-center">User <strong><?php print $this_user; ?> was deleted.</p>
 </div>
 <?php
 }
 else {
 ?>
 <div class="alert alert-danger" role="alert">
  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="TRUE">&times;</span></button>
  <p class="text-center">User <strong><?php print $this_user; ?></strong> wasn't deleted.</p>
 </div>
 <?php
 }


}
#'
$people = ldap_get_user_list($ldap_connection);

?>
<div class="container">
 <form action="<?php print $THIS_MODULE_PATH; ?>/new_user.php" method="post">
  <span class="badge badge-secondary" style="font-size:1.9rem;"><?php print count($people);?> account<?php if (count($people) != 1) { print "s"; }?></span>  &nbsp; <button id="add_group" class="btn btn-default" type="submit">New user</button>
 </form>
 <table class="table table-striped">
  <thead>
   <tr>
     <th>Account name</th>
     <th>First name</th>
     <th>Last name</th>
     <th>Email</th>
     <th>Member of</th>
   </tr>
  </thead>
 <tbody>
<?php
foreach ($people as $account_identifier => $attribs){

 $group_membership = ldap_user_group_membership($ldap_connection,$account_identifier);

 print " <tr>\n   <td><a href='${THIS_MODULE_PATH}/show_user.php?account_identifier=" . urlencode($account_identifier) . "'>$account_identifier</a></td>\n";
 print "   <td>" . $people[$account_identifier]['givenname'] . "</td>\n";
 print "   <td>" . $people[$account_identifier]['sn'] . "</td>\n";
 print "   <td>" . $people[$account_identifier]['mail'] . "</td>\n";
 print "   <td>" . implode(", ", $group_membership) . "</td>\n";
 print " </tr>\n";
}
?>
  </tbody>
 </table>
</div>
<?php

ldap_close($ldap_connection);
render_footer();
?>
