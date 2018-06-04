<?php

include_once("web_functions.inc.php");
include_once("ldap_functions.inc.php");
include_once("module_functions.inc.php");
validate_setup_cookie();

set_page_access("setup");

render_header();

$invalid_password = FALSE;
$mismatched_passwords = FALSE;
$invalid_username = FALSE;
$weak_password = FALSE;

if (isset($_POST['create_account'])) {

 $ldap_connection = open_ldap_connection();

 $first_name = stripslashes($_POST['first_name']);
 $last_name = stripslashes($_POST['last_name']);
 $username = stripslashes($_POST['username']);
 $password = $_POST['password'];

 if (!is_numeric($_POST['pass_score']) or $_POST['pass_score'] < 3) { $weak_password = TRUE; }
 if (preg_match("/\"|'/",$password)) { $invalid_password = TRUE; }
 if ($_POST['password'] != $_POST['password_match']) { $mismatched_passwords = TRUE; }
 if (!preg_match("/$USERNAME_REGEX/",$username)) { $invalid_username = TRUE; }

 if (     isset($first_name)
      and isset($last_name)
      and isset($username)
      and isset($password)
      and !$mismatched_passwords
      and !$weak_password
      and !$invalid_password
      and !$invalid_username ) {

  $ldap_connection = open_ldap_connection();

  $new_account = ldap_new_account($ldap_connection, $first_name, $last_name, $username, $password);
  $member_add = ldap_add_member_to_group($ldap_connection, $LDAP['admins_group'], $username);
  
  if ($new_account and $member_add) {
   ?>
   <div class="alert alert-success">
   <p class="text-center">Account created.</p>
   </div>
   <form action='/log_in'>
    <p align="center">
     <input type='submit' class="btn btn-success" value='Finished'>
    </p>
   </form>
   <?php
   render_footer();
   exit(0);
  }
  else {
   if (!$new_account) { ?>
    <div class="alert alert-warning">
     <p class="text-center">Couldn't create the account.</p>
    </div>
    <?php 
   }

   if (!$member_add) { ?>
    <div class="alert alert-warning">
     <p class="text-center">Couldn't add the account to the admin group.</p>
    </div>
    <?php 
   }
   ?>
   <form action='/setup/run_checks.php'>
    <p align="center">
     <input type='submit' class="btn btn-danger" value='Start again'>
    </p>
   </form>
   <?php
   render_footer();
   exit(0);
  
  }
 }

}


if ($weak_password) { ?>
<div class="alert alert-warning">
 <p class="text-center">The password wasn't strong enough.</p>
</div>
<?php }

if ($invalid_password) {  ?>
<div class="alert alert-warning">
 <p class="text-center">The password contained invalid characters.</p>
</div>
<?php }

if ($mismatched_passwords) {  ?>
<div class="alert alert-warning">
 <p class="text-center">The passwords didn't match.</p>
</div>
<?php }


if ($invalid_username) {  ?>
<div class="alert alert-warning">
 <p class="text-center">The username is invalid.</p>
</div>
<?php }

render_js_username_generator('first_name','last_name','username','username_div');
?>
<script src="//cdnjs.cloudflare.com/ajax/libs/zxcvbn/1.0/zxcvbn.min.js"></script>
<script type="text/javascript" src="/js/zxcvbn-bootstrap-strength-meter.js"></script>
<script type="text/javascript">$(document).ready(function(){	$("#StrengthProgressBar").zxcvbnProgressBar({ passwordInput: "#password" });});</script>
<script>
 function check_passwords_match() {

   if (document.getElementById('password').value != document.getElementById('confirm').value ) {
       document.getElementById('password_div').classList.add("has-error");
       document.getElementById('confirm_div').classList.add("has-error");
   }
   else {
    document.getElementById('password_div').classList.remove("has-error");
    document.getElementById('confirm_div').classList.remove("has-error");
   }
  }
</script>
     
<div class="container">
 <div class="col-sm-8">

  <div class="panel panel-default">
   <div class="panel-heading text-center">New administrator account</div>
   <div class="panel-body text-center">

    <form class="form-horizontal" action="" method="post">

     <input type="hidden" name="create_account">
     <input type="hidden" id="pass_score" value="0" name="pass_score">

     <div class="form-group">
      <label for="first_name" class="col-sm-4 control-label">First name</label>
      <div class="col-sm-6">
       <input type="text" class="form-control" id="first_name" name="first_name" <?php if (isset($first_name)){ print " value='$first_name'"; } ?> onkeyup="update_username()">
      </div>
     </div>

     <div class="form-group">
      <label for="last_name" class="col-sm-4 control-label">Last name</label>
      <div class="col-sm-6">
       <input type="text" class="form-control" id="last_name" name="last_name" <?php if (isset($last_name)){ print " value='$last_name'"; } ?> onkeyup="update_username()">
      </div>
     </div>

     <div class="form-group" id="username_div">
      <label for="username" class="col-sm-4 control-label">Username</label>
      <div class="col-sm-6">
       <input type="text" class="form-control" id="username" name="username" <?php if (isset($username)){ print " value='$username'"; } ?> onkeyup="check_username_validity(document.getElementById('username').value)">
      </div>
     </div>

     <div class="form-group" id="password_div">
      <label for="password" class="col-sm-4 control-label">Password</label>
      <div class="col-sm-6">
       <input type="password" class="form-control" id="password" name="password">
      </div>
     </div>


      <div class="col-sm-4 "></div>
      <div class="col-sm-6 progress">
       <div id="StrengthProgressBar" class="progress-bar"></div>
      </div>


     <div class="form-group" id="confirm_div">
      <label for="password" class="col-sm-4 control-label">Confirm</label>
      <div class="col-sm-6">
       <input type="password" class="form-control" id="confirm" name="password_match" onkeyup="check_passwords_match()">
      </div>
     </div>

     <div class="form-group">
       <button type="submit" class="btn btn-warning">Create account</button>
     </div>

    </form>

   </div>
  </div>

 </div>
</div>
<?php



render_footer();

?>
