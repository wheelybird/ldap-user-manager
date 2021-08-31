<?php

##################################

function ldap_personal_account_attribute_array() {
  global $LDAP;

  $personal_attributes_r = array();

  if (isset($LDAP['account_attributes_personal'])) {
    $account_attribute_r = ldap_complete_account_attribute_array();

    $user_attribute_r = explode(",", $LDAP['account_attributes_personal']);

    foreach ($user_attribute_r as $this_attr) {
      $attr_name = strtolower(filter_var($this_attr, FILTER_SANITIZE_STRING));

      // Regular attribute name.
      if (preg_match('/^[a-zA-Z0-9\-]+$/', $attr_name) == 1) {
        // Attribute exists in Account.
        if (isset($account_attribute_r[$attr_name]))
          $personal_attributes_r[$attr_name] = $account_attribute_r[$attr_name];
      }
    }
  }

  return($personal_attributes_r);
}

?>
