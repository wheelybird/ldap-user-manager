<?php

set_include_path( ".:" . __DIR__ . "/../includes/");

include_once "web_functions.inc.php";
include_once "ldap_functions.inc.php";
include_once "module_functions.inc.php";

set_page_access("user");

render_header('Update additional attributes');

$to_update = array();

$attribute_map = ldap_additional_account_attribute_array();

$account_identifier = $USER_ID;

$ldap_connection = open_ldap_connection();
$ldap_search_query="(${LDAP['account_attribute']}=". ldap_escape($account_identifier, "", LDAP_ESCAPE_FILTER) . ")";
$ldap_search = ldap_search( $ldap_connection, $LDAP['user_dn'], $ldap_search_query);


if ($ldap_search) {
 $user = ldap_get_entries($ldap_connection, $ldap_search);

 foreach ($attribute_map as $attribute => $attr_r) {

   $$attribute = $user[0][$attribute][0];

   if (isset($_POST['update_account']) and isset($_POST[$attribute]) and $_POST[$attribute] != $$attribute) {
     $$attribute = filter_var($_POST[$attribute], FILTER_SANITIZE_STRING);
     $to_update[$attribute] = $$attribute;
   }
   elseif (isset($attr_r['default'])) {
     $$attribute = $attr_r['default'];
   }

 }
 $dn = $user[0]['dn'];


  ### Update values

 if (isset($_POST['update_account'])) {

  if (array_key_exists($LDAP['account_attribute'], $to_update)) {
    $new_rdn = "${LDAP['account_attribute']}=${to_update[$LDAP['account_attribute']]}";
    $renamed_entry = ldap_rename($ldap_connection, $dn, $new_rdn, $LDAP['user_dn'], true);
    if ($renamed_entry) {
      $dn = "${new_rdn},${LDAP['user_dn']}";
      $account_identifier = $to_update[$LDAP['account_attribute']];
    }
    else {
      ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $detailed_err);
      error_log("$log_prefix Failed to rename the DN for ${account_identifier}: " . ldap_error($ldap_connection) . " -- " . $detailed_err,0);
    }
  }

  $updated_account = @ ldap_mod_replace($ldap_connection, $dn, $to_update);
  if (!$updated_account) {
    ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $detailed_err);
    error_log("$log_prefix Failed to modify account details for ${account_identifier}: " . ldap_error($ldap_connection) . " -- " . $detailed_err,0);
  }

  if ($updated_account) {
   ?>
   <script>
     window.setTimeout(function() {
                                   $(".alert").fadeTo(500, 0).slideUp(500, function(){ $(this).remove(); });
                                  }, 4000);
   </script>
   <div class="alert alert-success" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="TRUE">&times;</span></button>
    <p class="text-center">The account has been updated.<?php print $sent_email_message; ?></p>
   </div>
  <?php
  }
  else {
   ?>
   <script>
     window.setTimeout(function() {
                                   $(".alert").fadeTo(500, 0).slideUp(500, function(){ $(this).remove(); });
                                  }, 4000);
   </script>
   <div class="alert alert-danger" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="TRUE">&times;</span></button>
    <p class="text-center">There was a problem updating the account.  Check the logs for more information.</p>
   </div>
  <?php
  }
 }
}

?>

<div class="container">
 <div class="col-sm-8">

  <div class="panel panel-default"> 
   <div class="panel-heading text-center">Update your additional attributes</div>
   <div class="panel-body text-center">
   
    <form class="form-horizontal" action='' method='post'>

     <input type="hidden" name="update_account">
     <input type="hidden" id="pass_score" value="0" name="pass_score">
     <input type="hidden" name="account_identifier" value="<?php print $account_identifier; ?>">
     
<?php

  foreach ($attribute_map as $attribute => $attr_r) {
    $label   = $attr_r['label'];

    if ($attribute == $LDAP['account_attribute']) { $label = "<strong>$label</strong><sup>&ast;</sup>"; }
  ?>
     <div class="form-group" id="<?php print $attribute; ?>_div">
      <label for="<?php print $attribute; ?>" class="col-sm-3 control-label"><?php print $label; ?></label>
      <div class="col-sm-8">
       <input type="text" class="form-control" id="<?php print $attribute; ?>" name="<?php print $attribute; ?>" value="<?php if (isset($$attribute)) { print $$attribute; } ?>" >
      </div>
     </div>
  <?php
  }
?>

     <div class="form-group">
       <button type="submit" class="btn btn-default">Update account</button>
     </div>
     
    </form>

   </div>
  </div>

 </div>
</div>
<?php

render_footer();

?>

