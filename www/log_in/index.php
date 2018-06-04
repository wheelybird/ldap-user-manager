<?php

include("web_functions.inc.php");
include("ldap_functions.inc.php");

if (isset($_POST["user_id"]) and isset($_POST["password"])) {

 $ldap_connection = open_ldap_connection();
 $user_auth = ldap_auth_username($ldap_connection,$_POST["user_id"],$_POST["password"]);
 $is_admin = ldap_is_group_member($ldap_connection,$LDAP['admins_group'],$_POST["user_id"]);
 
 ldap_close($ldap_connection);
 
 if ($user_auth != FALSE) {

  set_passkey_cookie($user_auth,$is_admin);
  if (isset($_POST["sendto"])) {
   header("Location: //${_SERVER["HTTP_HOST"]}${_POST["sendto"]}\n\n");
  }
  else {
   header("Location: //${_SERVER["HTTP_HOST"]}/index.php?logged_in\n\n");
  }
 }
 else {
  header("Location: //${_SERVER["HTTP_HOST"]}/${THIS_MODULE_PATH}/index.php?invalid\n\n");
 }

}
else {

 render_header("Log in");

 ?>
<div class="container">
 <div class="col-sm-8">

  <div class="panel panel-default"> 
   <div class="panel-heading text-center">Log in</div>
   <div class="panel-body text-center">
   
   <?php if (isset($_GET["invalid"])) { ?>
   <div class="alert alert-warning">
    The username and/or password are unrecognised.
   </div>
   <?php } ?>
   
   
   <form class="form-horizontal" action='' method='post'>
    <?php if (isset($sendto) and ($sendto != "")) { ?><input type="hidden" name="sendto" value="<?php print $sendto; ?>"><?php } ?>
    
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
