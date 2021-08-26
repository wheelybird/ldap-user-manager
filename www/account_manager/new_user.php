<?php

set_include_path( ".:" . __DIR__ . "/../includes/");

include_once "web_functions.inc.php";
include_once "ldap_functions.inc.php";
include_once "module_functions.inc.php";

$attribute_map = ldap_complete_account_attribute_array();

if ( isset($_POST['setup_admin_account']) ) {
 $admin_setup = TRUE;

 validate_setup_cookie();
 set_page_access("setup");

 $completed_action="${SERVER_PATH}log_in";
 $page_title="New administrator account";

 render_header("$ORGANISATION_NAME account manager - setup administrator account", FALSE);

}
else {
 set_page_access("admin");

 $completed_action="${THIS_MODULE_PATH}/";
 $page_title="New account";
 $admin_setup = FALSE;

 render_header("$ORGANISATION_NAME account manager");
 render_submenu();
}

$invalid_password = FALSE;
$mismatched_passwords = FALSE;
$invalid_username = FALSE;
$weak_password = FALSE;
$invalid_email = FALSE;
$disabled_email_tickbox = TRUE;
$invalid_cn = FALSE;
$invalid_account_identifier = FALSE;

$new_account_r = array();

foreach ($attribute_map as $attribute => $attr_r) {
 if (isset($_POST[$attribute])) {
  $$attribute = filter_var($_POST[$attribute], FILTER_SANITIZE_STRING);
 }
 elseif (isset($attr_r['default'])) {
  $$attribute = $attr_r['default'];
 }
 if (isset($$attribute)) { $new_account_r[$attribute] = $$attribute; }
}

##

if (isset($_GET['account_request'])) {

  $givenname=filter_var($_GET['first_name'], FILTER_SANITIZE_STRING);
  $new_account_r['givenname'] = $givenname;

  $sn=filter_var($_GET['last_name'], FILTER_SANITIZE_STRING);
  $new_account_r['sn'] = $sn;

  $uid = generate_username($givenname,$sn);
  $new_account_r['uid'] = $uid;

  if ($ENFORCE_SAFE_SYSTEM_NAMES == TRUE) {
    $cn = "$givenname$sn";
  }
  else {
    $cn = "$givenname $sn";
  }

  $new_account_r['cn'] = $cn;

  $mail=filter_var($_GET['email'], FILTER_SANITIZE_EMAIL);
  if ($mail == "") {
    if (isset($EMAIL_DOMAIN)) {
      $mail = $uid . "@" . $EMAIL_DOMAIN;
      $disabled_email_tickbox = FALSE;
    }
  }
  else {
    $disabled_email_tickbox = FALSE;
  }
  $new_account_r['mail'] = $mail;

}

if (isset($_POST['create_account'])) {

 $password  = $_POST['password'];
 $new_account_r['password'] = $password;
 $account_identifier = $new_account_r[$LDAP["account_attribute"]];

 if (!isset($cn) or $cn == "") { $invalid_cn = TRUE; }
 if ((!isset($account_identifier) or $account_identifier == "") and $invalid_cn != TRUE) { $invalid_account_identifier = TRUE; }
 if ((!is_numeric($_POST['pass_score']) or $_POST['pass_score'] < 3) and $ACCEPT_WEAK_PASSWORDS != TRUE) { $weak_password = TRUE; }
 if (isset($mail) and !is_valid_email($mail)) { $invalid_email = TRUE; }
 if (preg_match("/\"|'/",$password)) { $invalid_password = TRUE; }
 if ($password != $_POST['password_match']) { $mismatched_passwords = TRUE; }
 if ($ENFORCE_SAFE_SYSTEM_NAMES == TRUE and !preg_match("/$POSIX_REGEX/",$account_identifier)) { $invalid_account_identifier = TRUE; }
 if (isset($_POST['send_email']) and isset($mail) and $EMAIL_SENDING_ENABLED == TRUE) { $send_user_email = TRUE; }

 if (     isset($givenname)
      and isset($sn)
      and isset($password)
      and !$mismatched_passwords
      and !$weak_password
      and !$invalid_password
      and !$invalid_account_identifier
      and !$invalid_cn
      and !$invalid_email) {

  $ldap_connection = open_ldap_connection();
  $new_account = ldap_new_account($ldap_connection, $new_account_r);

  if ($new_account) {

    $creation_message = "The account was created.";

    if (isset($send_user_email) and $send_user_email == TRUE) {

      include_once "mail_functions.inc.php";

      $mail_body = parse_mail_text($new_account_mail_body, $password, $account_identifier, $givenname, $sn);
      $mail_subject = parse_mail_text($new_account_mail_subject, $password, $account_identifier, $givenname, $sn);

      $sent_email = send_email($mail,"$givenname $sn",$mail_subject,$mail_body);
      $creation_message = "The account was created";
      if ($sent_email) {
        $creation_message .= " and an email sent to $mail.";
      }
      else {
        $creation_message .= " but unfortunately the email wasn't sent.<br>More information will be available in the logs.";
      }
    }

    if ($admin_setup == TRUE) {
      $member_add = ldap_add_member_to_group($ldap_connection, $LDAP['admins_group'], $account_identifier);
      if (!$member_add) { ?>
       <div class="alert alert-warning">
        <p class="text-center"><?php print $creation_message; ?> Unfortunately adding it to the admin group failed.</p>
       </div>
       <?php
      }
     #Tidy up empty uniquemember entries left over from the setup wizard
     $USER_ID="tmp_admin";
     ldap_delete_member_from_group($ldap_connection, $LDAP['admins_group'], "");
     if (isset($DEFAULT_USER_GROUP)) { ldap_delete_member_from_group($ldap_connection, $DEFAULT_USER_GROUP, ""); }
    }

   ?>
   <div class="alert alert-success">
   <p class="text-center"><?php print $creation_message; ?></p>
   </div>
   <form action='<?php print $completed_action; ?>'>
    <p align="center">
     <input type='submit' class="btn btn-success" value='Finished'>
    </p>
   </form>
   <?php
   render_footer();
   exit(0);
  }
  else {
  ?>
    <div class="alert alert-warning">
     <p class="text-center">Failed to create the account:</p>
     <pre>
     <?php
       print ldap_error($ldap_connection) . "\n";
       ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $detailed_err);
       print $detailed_err;
     ?>
     </pre>
    </div>
    <?php

   render_footer();
   exit(0);

  }

 }

}

$errors="";
if ($invalid_cn) { $errors.="<li>The Common Name is required</li>\n"; }
if ($invalid_account_identifier) {  $errors.="<li>The account identifier (" . $attribute_map[$LDAP['account_attribute']]['label'] . ") is invalid.</li>\n"; }
if ($weak_password) { $errors.="<li>The password is too weak</li>\n"; }
if ($invalid_password) { $errors.="<li>The password contained invalid characters</li>\n"; }
if ($invalid_email) { $errors.="<li>The email address is invalid</li>\n"; }
if ($mismatched_passwords) { $errors.="<li>The passwords are mismatched</li>\n"; }
if ($invalid_username) { $errors.="<li>The username is invalid</li>\n"; }

if ($errors != "") { ?>
<div class="alert alert-warning">
 <p class="text-align: center">
 There were issues creating the account:
 <ul>
 <?php print $errors; ?>
 </ul>
 </p>
</div>
<?php
}

render_js_username_check();
render_js_username_generator('givenname','sn','uid','uid_div');
render_js_cn_generator('givenname','sn','cn','cn_div');
render_js_email_generator('uid','mail');

$tabindex=1;

?>
<script src="<?php print $SERVER_PATH; ?>js/zxcvbn.min.js"></script>
<script type="text/javascript" src="<?php print $SERVER_PATH; ?>js/zxcvbn-bootstrap-strength-meter.js"></script>
<script type="text/javascript">
 $(document).ready(function(){
   $("#StrengthProgressBar").zxcvbnProgressBar({ passwordInput: "#password" });
 });
</script>
<script type="text/javascript" src="<?php print $SERVER_PATH; ?>js/generate_passphrase.js"></script>
<script type="text/javascript" src="<?php print $SERVER_PATH; ?>js/wordlist.js"></script>
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

 function random_password() {

  generatePassword(4,'-','password','confirm');
  $("#StrengthProgressBar").zxcvbnProgressBar({ passwordInput: "#password" });
 }

 function back_to_hidden(passwordField,confirmField) {

  var passwordField = document.getElementById(passwordField).type = 'password';
  var confirmField = document.getElementById(confirmField).type = 'password';

 }


</script>
<script>

 function check_email_validity(mail) {

  var check_regex = <?php print $JS_EMAIL_REGEX; ?>

  if (! check_regex.test(mail) ) {
   document.getElementById("mail_div").classList.add("has-error");
   <?php if ($EMAIL_SENDING_ENABLED == TRUE) { ?>document.getElementById("send_email_checkbox").disabled = true;<?php } ?>
  }
  else {
   document.getElementById("mail_div").classList.remove("has-error");
   <?php if ($EMAIL_SENDING_ENABLED == TRUE) { ?>document.getElementById("send_email_checkbox").disabled = false;<?php } ?>
  }

 }

</script>

<div class="container">
 <div class="col-sm-8">

  <div class="panel panel-default">
   <div class="panel-heading text-center"><?php print $page_title; ?></div>
   <div class="panel-body text-center">

    <form class="form-horizontal" action="" method="post">

     <?php if ($admin_setup == TRUE) { ?><input type="hidden" name="setup_admin_account" value="true"><?php } ?>
     <input type="hidden" name="create_account">
     <input type="hidden" id="pass_score" value="0" name="pass_score">


<?php

  foreach ($attribute_map as $attribute => $attr_r) {
    $label   = $attr_r['label'];
    if ($attribute == $LDAP['account_attribute']) { $label = "<strong>$label</strong><sup>&ast;</sup>"; }
  ?>
     <div class="form-group" id="<?php print $attribute; ?>_div">
      <label for="<?php print $attribute; ?>" class="col-sm-3 control-label"><?php print $label; ?></label>
      <div class="col-sm-6">
       <input tabindex="<?php print $tabindex; ?>" type="text" class="form-control" id="<?php print $attribute; ?>" name="<?php print $attribute; ?>" value="<?php if (isset($$attribute)) { print $$attribute; } ?>" <?php
         if (isset($attr_r['onkeyup'])) { print "onkeyup=\"${attr_r['onkeyup']};\""; } ?>>
      </div>
     </div>
  <?php
   $tabindex++;
  }
?>

     <div class="form-group" id="password_div">
      <label for="password" class="col-sm-3 control-label">Password</label>
      <div class="col-sm-6">
       <input tabindex="<?php print $tabindex+1; ?>" type="text" class="form-control" id="password" name="password" onkeyup="back_to_hidden('password','confirm');">
      </div>
      <div class="col-sm-1">
       <input tabindex="<?php print $tabindex+2; ?>" type="button" class="btn btn-sm" id="password_generator" onclick="random_password();" value="Generate password">
      </div>
     </div>

     <div class="form-group" id="confirm_div">
      <label for="confirm" class="col-sm-3 control-label">Confirm</label>
      <div class="col-sm-6">
       <input tabindex="<?php print $tabindex+3; ?>" type="password" class="form-control" id="confirm" name="password_match" onkeyup="check_passwords_match()">
      </div>
     </div>

<?php  if ($EMAIL_SENDING_ENABLED == TRUE and $admin_setup != TRUE) { ?>
      <div class="form-group" id="send_email_div">
       <label for="send_email" class="col-sm-3 control-label"> </label>
       <div class="col-sm-6">
        <input tabindex="<?php print $tabindex+4; ?>" type="checkbox" class="form-check-input" id="send_email_checkbox" name="send_email" <?php if ($disabled_email_tickbox == TRUE) { print "disabled"; } ?>>  Email these credentials to the user?
       </div>
      </div>
<?php } ?>

     <div class="form-group">
       <button tabindex="<?php print $tabindex+5; ?>" type="submit" class="btn btn-warning">Create account</button>
     </div>

    </form>

    <div class="progress">
     <div id="StrengthProgressBar" class="progress-bar"></div>
    </div>

    <div><sup>&ast;</sup>The account identifier</div>

   </div>
  </div>

 </div>
</div>
<?php



render_footer();

?>
