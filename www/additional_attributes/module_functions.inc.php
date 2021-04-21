<?php



##################################

function ldap_additional_account_attribute_array() {

 global $LDAP;

  $additional_attributes_r = array();

 if (isset($LDAP['account_additional_attributes'])) {

  $user_attribute_r = explode(",", $LDAP['account_additional_attributes']);

  foreach ($user_attribute_r as $this_attr) {

    $this_r = array();
    $kv = explode(":", $this_attr);
    $attr_name = strtolower(filter_var($kv[0], FILTER_SANITIZE_STRING));

    if (preg_match('/^[a-zA-Z0-9\-]+$/', $attr_name) == 1) {

     if (isset($kv[1]) and $kv[1] != "") {
      $this_r['label'] = filter_var($kv[1], FILTER_SANITIZE_STRING);
     }
     else {
      $this_r['label'] = $attr_name;
     }

     if (isset($kv[2]) and $kv[2] != "") {
      $this_r['default'] = filter_var($kv[2], FILTER_SANITIZE_STRING);
     }

     $additional_attributes_r[$attr_name] = $this_r;

   }
  }
 }

 return($additional_attributes_r);

}


?>
