<?php

#Security level vars

$VALIDATED = FALSE;
$IS_ADMIN = FALSE;
$IS_SETUP_ADMIN = FALSE;
$ACCESS_LEVEL_NAME = array('account','admin');
unset($USER_ID);
$CURRENT_PAGE=htmlentities($_SERVER['PHP_SELF']);
$SENT_HEADERS = FALSE;
$SESSION_TIMED_OUT = FALSE;

$paths=explode('/',getcwd());
$THIS_MODULE_PATH=end($paths);

$GOOD_ICON = "&#9745;";
$WARN_ICON = "&#9888;";
$FAIL_ICON = "&#9940;";

$JS_EMAIL_REGEX='/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;';

if (isset($_SERVER['HTTPS']) and
   ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) or
   isset($_SERVER['HTTP_X_FORWARDED_PROTO']) and
   $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
  $SITE_PROTOCOL = 'https://';
}
else {
  $SITE_PROTOCOL = 'http://';
}

include ("config.inc.php");    # get local settings
include ("modules.inc.php");   # module definitions

validate_passkey_cookie();

######################################################

function generate_passkey() {

 $rnd1 = rand(10000000,100000000000);
 $rnd2 = rand(10000000,100000000000);
 $rnd3 = rand(10000000,100000000000);
 return sprintf("%0x",$rnd1) . sprintf("%0x",$rnd2) . sprintf("%0x",$rnd3);

}


######################################################

function set_passkey_cookie($user_id,$is_admin) {

 # Create a random value, store it locally and set it in a cookie.

 global $SESSION_TIMEOUT, $VALIDATED, $USER_ID, $IS_ADMIN, $log_prefix, $SESSION_DEBUG;


 $passkey = generate_passkey();
 $this_time=time();
 $admin_val = 0;

 if ($is_admin == TRUE ) {
  $admin_val = 1;
  $IS_ADMIN = TRUE;
 }
 $filename = preg_replace('/[^a-zA-Z0-9]/','_', $user_id);
 @ file_put_contents("/tmp/$filename","$passkey:$admin_val:$this_time");
 setcookie('orf_cookie', "$user_id:$passkey", $this_time+(60 * $SESSION_TIMEOUT), '/', '', '', TRUE);
 setcookie('sessto_cookie', $this_time+(60 * $SESSION_TIMEOUT), $this_time+7200, '/', '', '', TRUE);
 if ( $SESSION_DEBUG == TRUE) {  error_log("$log_prefix Session: user $user_id validated (IS_ADMIN=${IS_ADMIN}), sent orf_cookie to the browser.",0); }
 $VALIDATED = TRUE;

}


######################################################

function validate_passkey_cookie() {

 global $SESSION_TIMEOUT, $IS_ADMIN, $USER_ID, $VALIDATED, $log_prefix, $SESSION_TIMED_OUT, $SESSION_DEBUG;

 $this_time=time();

 if (isset($_COOKIE['orf_cookie'])) {

  list($user_id,$c_passkey) = explode(":",$_COOKIE['orf_cookie']);
  $filename = preg_replace('/[^a-zA-Z0-9]/','_', $user_id);
  $session_file = @ file_get_contents("/tmp/$filename");
  if (!$session_file) {
   $VALIDATED = FALSE;
   unset($USER_ID);
   $IS_ADMIN = FALSE;
   if ( $SESSION_DEBUG == TRUE) {  error_log("$log_prefix Session: orf_cookie was sent by the client but the session file wasn't found at /tmp/$filename",0); }
  }
  else {
   list($f_passkey,$f_is_admin,$f_time) = explode(":",$session_file);
   if (!empty($c_passkey) and $f_passkey == $c_passkey and $this_time < $f_time+(60 * $SESSION_TIMEOUT)) {
    if ($f_is_admin == 1) { $IS_ADMIN = TRUE; }
    $VALIDATED = TRUE;
    $USER_ID=$user_id;
    if ( $SESSION_DEBUG == TRUE) {  error_log("$log_prefix Setup session: Cookie and session file values match for user ${user_id} - VALIDATED (ADMIN = ${IS_ADMIN})",0); }
    set_passkey_cookie($USER_ID,$IS_ADMIN);
   }
   else {
    if ( $SESSION_DEBUG == TRUE ) {
     $this_error="$log_prefix Session: orf_cookie was sent by the client and the session file was found at /tmp/$filename, but";
      if (empty($c_passkey)) { $this_error .= " the cookie passkey wasn't set;"; }
      if ($c_passkey != $f_passkey) { $this_error .= " the session file passkey didn't match the cookie passkey;"; }
      $this_error += " Cookie: ${_COOKIE['orf_cookie']} - Session file contents: $session_file";
      error_log($this_error,0);
    }
   }
  }
  
 }
 else {
  if ( $SESSION_DEBUG == TRUE) { error_log("$log_prefix Session: orf_cookie wasn't sent by the client.",0); }
  if (isset($_COOKIE['sessto_cookie'])) {
   $this_session_timeout = $_COOKIE['sessto_cookie'];
   if ($this_time >= $this_session_timeout) {
    $SESSION_TIMED_OUT = TRUE;
    if ( $SESSION_DEBUG == TRUE) { error_log("$log_prefix Session: The session had timed-out (over $SESSION_TIMEOUT mins idle).",0); }
   }
  }
 }

}


######################################################

function set_setup_cookie() {

 # Create a random value, store it locally and set it in a cookie.

 global $SESSION_TIMEOUT, $IS_SETUP_ADMIN, $log_prefix, $SESSION_DEBUG;

 $passkey = generate_passkey();
 $this_time=time();

 $IS_SETUP_ADMIN = TRUE;

 file_put_contents("/tmp/ldap_setup","$passkey:$this_time");
# setcookie('setup_cookie', "$passkey", $this_time+(60 * $SESSION_TIMEOUT), '/', $_SERVER["HTTP_HOST"]);
 setcookie('setup_cookie', "$passkey", $this_time+(60 * $SESSION_TIMEOUT), '/', '', '', TRUE);
 if ( $SESSION_DEBUG == TRUE) {  error_log("$log_prefix Setup session: sent setup_cookie to the client.",0); }

}


######################################################

function validate_setup_cookie() {

 global $SESSION_TIMEOUT, $IS_SETUP_ADMIN, $log_prefix, $SESSION_DEBUG;

 if (isset($_COOKIE['setup_cookie'])) {

  $c_passkey = $_COOKIE['setup_cookie'];
  $session_file = file_get_contents("/tmp/ldap_setup");
  if (!$session_file) {
   $IS_SETUP_ADMIN = FALSE;
   if ( $SESSION_DEBUG == TRUE) {  error_log("$log_prefix Setup session: setup_cookie was sent by the client but the session file wasn't found at /tmp/ldap_setup",0); }
  }
  list($f_passkey,$f_time) = explode(":",$session_file);
  $this_time=time();
  if (!empty($c_passkey) and $f_passkey == $c_passkey and $this_time < $f_time+(60 * $SESSION_TIMEOUT)) {
   $IS_SETUP_ADMIN = TRUE;
   if ( $SESSION_DEBUG == TRUE) {  error_log("$log_prefix Setup session: Cookie and session file values match - VALIDATED ",0); }
   set_setup_cookie();
  }
  elseif ( $SESSION_DEBUG == TRUE) {
   $this_error="$log_prefix Setup session: setup_cookie was sent by the client and the session file was found at /tmp/ldap_setup, but";
   if (empty($c_passkey)) { $this_error .= " the cookie passkey wasn't set;"; }
   if ($c_passkey != $f_passkey) { $this_error .= " the session file passkey didn't match the cookie passkey;"; }
   $this_error += " Cookie: ${_COOKIE['setup_cookie']} - Session file contents: $session_file";
   error_log($this_error,0);
  }
 }
 elseif ( $SESSION_DEBUG == TRUE) {
   error_log("$log_prefix Session: setup_cookie wasn't sent by the client.",0);
 }

}


######################################################

function log_out($method='normal') {

 # Delete the passkey from the database and the passkey cookie

 global $USER_ID;

 setcookie('orf_cookie', "", time()-20000, '/', '', '', TRUE);
 setcookie('sessto_cookie', "", time()-20000, '/', '', '', TRUE);

 $filename = preg_replace('/[^a-zA-Z0-9]/','_', $USER_ID);
 @ unlink("/tmp/$filename");

 if ($method == 'auto') { $options = "?logged_out"; } else { $options = ""; }
 header("Location:  //${_SERVER["HTTP_HOST"]}/index.php$options\n\n");

}


######################################################

function render_header($title="",$menu=TRUE) {

 global $SITE_NAME, $IS_ADMIN, $SENT_HEADERS;

 if (empty($title)) { $title = $SITE_NAME; }

 #Initialise the HTML output for the page.

 ?>
<HTML>
<HEAD>
 <TITLE><?php print "$title"; ?></TITLE>
 <meta charset="utf-8">
 <meta name="viewport" content="width=device-width, initial-scale=1">
 <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
 <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
 <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
</HEAD>
<BODY>
<?php

 if ($menu == TRUE) {
  render_menu();
 }

 $SENT_HEADERS = TRUE;

}


######################################################

function render_menu() {

 #Render the navigation menu.
 #The menu is dynamically rendered the $MODULES hash

 global $SITE_NAME, $MODULES, $THIS_MODULE_PATH, $VALIDATED, $IS_ADMIN;

 ?>
  <nav class="navbar navbar-default">
   <div class="container-fluid">
   <div class="navbar-header">
     <a class="navbar-brand" href="#"><img src="../gfx/SlovenskoIT_logo.svg" width="30" height="30" class="d-inline-block align-top" alt=""><?php print $SITE_NAME ?></a>
   </div>
     <ul class="nav navbar-nav">
     <?php
     foreach ($MODULES as $module => $access) {

      $this_module_name=stripslashes(ucwords(preg_replace('/_/',' ',$module)));

      $show_this_module = TRUE;
      if ($VALIDATED == TRUE) {
       if ($access == 'hidden_on_login') { $show_this_module = FALSE; }
       if ($IS_ADMIN == FALSE and $access == 'admin' ){ $show_this_module = FALSE; }
      }
      else {
       if ($access != 'hidden_on_login') { $show_this_module = FALSE; }
      }
      #print "<p>$module - access is $access & show is $show_this_module</p>";
      if ($show_this_module == TRUE ) {
       if ($module == $THIS_MODULE_PATH) {
        print "<li class='active'>";
       }
       else {
        print '<li>';
       }
       print "<a href='/{$module}/'>$this_module_name</a></li>\n";
      }
     }
    ?>
    </ul>
   </div>
  </nav>
 <?php
}


######################################################

function render_footer() {

#Finish rendering an HTML page.

?>
 </BODY>
</HTML>
<?php

}


######################################################

function set_page_access($level) {

 global $IS_ADMIN, $IS_SETUP_ADMIN, $VALIDATED, $log_prefix, $SESSION_DEBUG, $SESSION_TIMED_OUT;

 #Set the security level needed to view a page.
 #This should be one of the first pieces of code
 #you call on a page.
 #Either 'setup', 'admin' or 'user'.

 if ($level == "setup") {
  if ($IS_SETUP_ADMIN == TRUE) {
   return;
  }
  else {
   header("Location: //" . $_SERVER["HTTP_HOST"] . "/setup/index.php?unauthorised\n\n");
   if ( $SESSION_DEBUG == TRUE) {  error_log("$log_prefix Session: UNAUTHORISED: page security level is 'setup' but IS_SETUP_ADMIN isn't TRUE",0); }
   exit(0);
  }
 }

 if ($SESSION_TIMED_OUT == TRUE) { $reason = "session_timeout"; } else { $reason = "unauthorised"; }

 if ($level == "admin") {
  if ($IS_ADMIN == TRUE and $VALIDATED == TRUE) {
   return;
  }
  else {
   header("Location: //" . $_SERVER["HTTP_HOST"] . "/log_in/index.php?$reason&redirect_to=" . base64_encode($_SERVER['REQUEST_URI']) . "\n\n");
   if ( $SESSION_DEBUG == TRUE) {  error_log("$log_prefix Session: no access to page ($reason): page security level is 'admin' but IS_ADMIN = '${IS_ADMIN}' and VALIDATED = '${VALIDATED}' (user) ",0); }
   exit(0);
  }
 }

 if ($level == "user") {
  if ($VALIDATED == TRUE){
   return;
  }
  else {
   header("Location: //" . $_SERVER["HTTP_HOST"] . "/log_in/index.php?$reason&redirect_to=" . base64_encode($_SERVER['REQUEST_URI']) . "\n\n");
   if ( $SESSION_DEBUG == TRUE) {  error_log("$log_prefix Session: no access to page ($reason): page security level is 'user' but VALIDATED = '${VALIDATED}'",0); }
   exit(0);
  }
 }

}


######################################################

function is_valid_email($email) {

 return (!filter_var($email, FILTER_VALIDATE_EMAIL)) ? FALSE : TRUE;

}


######################################################

function render_js_username_check(){

  global $USERNAME_REGEX;

 print <<<EoCheckJS

<script>

 function check_entity_name_validity(name,div_id) {

  var check_regex = /$USERNAME_REGEX/;

  if (! check_regex.test(name) ) {
   document.getElementById(div_id).classList.add("has-error");
  }
  else {
   document.getElementById(div_id).classList.remove("has-error");
  }

 }

</script>
EoCheckJS;

}

######################################################

function generate_username($fn,$ln) {

  global $USERNAME_FORMAT;

  $username = $USERNAME_FORMAT;
  $username = str_replace('{first_name}',strtolower($fn), $username);
  $username = str_replace('{first_name_initial}',strtolower($fn[0]), $username);
  $username = str_replace('{last_name}',strtolower($ln), $username);
  $username = str_replace('{first_name_initial}',strtolower($ln[0]), $username);

  return $username;

}

######################################################

function render_js_username_generator($firstname_field_id,$lastname_field_id,$username_field_id,$username_div_id) {

 #Parameters are the IDs of the input fields and username name div in the account creation form.
 #The div will be set to warning if the username is invalid.

 global $USERNAME_FORMAT;

  render_js_username_check();

  print <<<EoRenderJS
<script>

 function update_username() {

  var first_name = document.getElementById('$firstname_field_id').value;
  var last_name  = document.getElementById('$lastname_field_id').value;
  var template = '$USERNAME_FORMAT';

  var actual_username = template;

  actual_username = actual_username.replace('{first_name}', first_name.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '') );
  actual_username = actual_username.replace('{first_name_initial}', first_name.charAt(0).toLowerCase() );
  actual_username = actual_username.replace('{last_name}', last_name.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '') );
  actual_username = actual_username.replace('{last_name_initial}', last_name.charAt(0).toLowerCase() );

  check_entity_name_validity(actual_username,'$username_div_id');

  document.getElementById('$username_field_id').value = actual_username;

 }
</script>
EoRenderJS;

}


######################################################

function render_js_email_generator($username_field_id,$email_field_id) {

 global $EMAIL_DOMAIN;

  print <<<EoRenderEmailJS
<script>

 var auto_email_update = true;

 function update_email() {

  if ( auto_email_update == true && "$EMAIL_DOMAIN" != ""  ) {
    var username = document.getElementById('$username_field_id').value;
    document.getElementById('$email_field_id').value = username + '@' + "$EMAIL_DOMAIN";
  }
 }
</script>
EoRenderEmailJS;

}

?>
