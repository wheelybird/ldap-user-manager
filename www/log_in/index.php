<?php

set_include_path( ".:" . __DIR__ . "/../includes/");

include "web_functions.inc.php";
include "ldap_functions.inc.php";

if (isset($_GET["unauthorised"])) { $display_unauth = TRUE; }
if (isset($_GET["session_timeout"])) { $display_logged_out = TRUE; }
if (isset($_GET["redirect_to"])) { $redirect_to = $_GET["redirect_to"]; }

if (isset($_GET['logged_out'])) {
?>
<div class="alert alert-warning">
<p class="text-center">You've been automatically logged out because you've been inactive for over
<?php print $SESSION_TIMEOUT; ?> minutes. Click on the 'Log in' link to get back into the system.</p>
</div>
<?php
}


if (isset($_POST["user_id"]) and isset($_POST["password"])) {

 $ldap_connection = open_ldap_connection();
 $user_auth = ldap_auth_username($ldap_connection,$_POST["user_id"],$_POST["password"]);
 $is_admin = ldap_is_group_member($ldap_connection,$LDAP['admins_group'],$_POST["user_id"]);

 ldap_close($ldap_connection);

 if ($user_auth != FALSE) {

  set_passkey_cookie($user_auth,$is_admin);
  if (isset($_POST["redirect_to"])) {
   header("Location: //${_SERVER['HTTP_HOST']}" . base64_decode($_POST['redirect_to']) . "\n\n");
  }
  else {

   if ($IS_ADMIN) { $default_module = "account_manager"; } else { $default_module = "change_password"; }
   header("Location: //${_SERVER['HTTP_HOST']}${SERVER_PATH}$default_module?logged_in\n\n");
  }
 }
 else {
  header("Location: //${_SERVER['HTTP_HOST']}${THIS_MODULE_PATH}/index.php?invalid\n\n");
 }

}
else {

 render_header("$ORGANISATION_NAME account manager - log in");

 ?>
<div class="container">
 <div class="col-sm-8">

  <div class="panel panel-default">
   <div class="panel-heading text-center">Log in</div>
   <div class="panel-body text-center">

   <?php if (isset($display_unauth)) { ?>
   <div class="alert alert-warning">
    Please log in to continue
   </div>
   <?php } ?>

   <?php if (isset($display_logged_out)) { ?>
   <div class="alert alert-warning">
    You were logged out because your session expired. Log in again to continue.
   </div>
   <?php } ?>

   <?php if (isset($_GET["invalid"])) { ?>
   <div class="alert alert-warning">
    The username and/or password are unrecognised.
   </div>
   <?php } ?>

   <form class="form-horizontal" action='' method='post'>
    <?php if (isset($redirect_to) and ($redirect_to != "")) { ?><input type="hidden" name="redirect_to" value="<?php print $redirect_to; ?>"><?php } ?>

    <div class="form-group">
     <label for="username" class="col-sm-4 control-label">Username</label>
     <div class="col-sm-6">
      <input type="text" class="form-control" id="user_id" name="user_id">
     </div>
    </div>

    <div class="form-group">
     <label for="password" class="col-sm-4 control-label">Password</label>
     <div class="col-sm-6">
      <input type="password" class="form-control" id="confirm" name="password">
     </div>
    </div>

    <div class="form-group">
     <button type="submit" class="btn btn-default">Log in</button>
    </div>

   </form>
  </div>
 </div>
</div>
<?php
}
render_footer();
?>
