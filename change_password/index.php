<?php

include_once("web_functions.inc.php");
include_once("ldap_functions.inc.php");

set_page_access("user");

if (isset($_POST['change_password'])) {

 if (!is_numeric($_POST['pass_score']) or $_POST['pass_score'] < 3) { $not_strong_enough = 1; }
 if (preg_match("/\"|'/",$_POST['password'])) { $invalid_chars = 1; }
 if ($_POST['password'] != $_POST['password_match']) { $mismatched = 1; }

 if (!isset($mismatched) and !isset($not_strong_enough) and !isset($invalid_chars) ) {

  $ldap_connection = open_ldap_connection();
  ldap_change_password($ldap_connection,$USER_ID,$_POST['password']) or die("change_ldap_password() failed.");

  render_header("Password changed");
  ?>
  <div class="alert alert-success">
  <p class="text-center">Your password has been changed.</p>
  </div>
  <?php
  render_footer();
  exit(0);
 }

}

render_header('Change your LDAP password');

if (isset($not_strong_enough)) { ?>
<div class="alert alert-warning">
 <p class="text-center">The password wasn't strong enough.</p>
</div>
<?php }

if (isset($invalid_chars)) {  ?>
<div class="alert alert-warning">
 <p class="text-center">The password contained invalid characters.</p>
</div>
<?php }

if (isset($mismatched)) {  ?>
<div class="alert alert-warning">
 <p class="text-center">The passwords didn't match.</p>
</div>
<?php }

?>

<script src="//cdnjs.cloudflare.com/ajax/libs/zxcvbn/1.0/zxcvbn.min.js"></script>
<script type="text/javascript" src="/js/zxcvbn-bootstrap-strength-meter.js"></script>
<script type="text/javascript">$(document).ready(function(){	$("#StrengthProgressBar").zxcvbnProgressBar({ passwordInput: "#password" });});</script>

<div class="container">
 <div class="col-sm-8">

  <div class="panel panel-default"> 
   <div class="panel-heading text-center">Change password</div>
   <div class="panel-body text-center">
   
    <form class="form-horizontal" action='' method='post'>

     <input type='hidden' id="change_password" name="change_password">
     <input type='hidden' id="pass_score" value="0" name="pass_score">
     
     <div class="form-group" id="password_div">
      <label for="password" class="col-sm-4 control-label">Password</label>
      <div class="col-sm-6">
       <input type="password" class="form-control" id="password" name="password">
      </div>
     </div>

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

     <div class="form-group" id="confirm_div">
      <label for="password" class="col-sm-4 control-label">Confirm</label>
      <div class="col-sm-6">
       <input type="password" class="form-control" id="confirm" name="password_match" onkeyup="check_passwords_match()">
      </div>
     </div>

     <div class="form-group">
       <button type="submit" class="btn btn-default">Change password</button>
     </div>
     
    </form>

    <div class="progress">
     <div id="StrengthProgressBar" class="progress progress-bar"></div>
    </div>

   </div>
  </div>

 </div>
</div>
<?php

render_footer();

?>

