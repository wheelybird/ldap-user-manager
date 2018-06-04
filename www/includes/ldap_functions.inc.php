<?php

$log_prefix = date('Y-m-d H:i:s') . " - LDAP manager - $USER_ID - ";

###################################

function open_ldap_connection() {

 global $log_prefix, $LDAP, $LDAP_CONNECTION_WARNING;

 $ldap_connection = ldap_connect($LDAP['uri']);

 if (!$ldap_connection) {
  print "Problem: Can't connect to the LDAP server at ${LDAP['uri']}";
  die("Can't connect to the LDAP server at ${LDAP['uri']}");
  exit(1);
 }

 ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);

 
 if (!preg_match("/^ldaps:/", $LDAP['uri'])) {

  $tls_result = ldap_start_tls($ldap_connection);

  if ($tls_result != TRUE) {

   error_log("$log_prefix Failed to start STARTTLS connection to ${LDAP['uri']}",0);

   if ($LDAP["require_starttls"] == TRUE) {
    print "<div style='position: fixed;bottom: 0;width: 100%;' class='alert alert-danger'>Fatal:  Couldn't create a secure connection to ${LDAP['uri']} and LDAP_REQUIRE_STARTTLS is TRUE.</div>";
    exit(0);
   }
   else {
    print "<div style='position: fixed;bottom: 0;width: 100%;' class='alert alert-warning'>WARNING: Insecure LDAP connection to ${LDAP['uri']}</div>";

    ldap_close($ldap_connection);
    $ldap_connection = ldap_connect($LDAP['uri']);
    ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
   }
  }
 }

 $bind_result = ldap_bind( $ldap_connection, $LDAP['admin_bind_dn'], $LDAP['admin_bind_pwd']);

 if ($bind_result != TRUE) {
  print "Problem: Failed to bind as ${LDAP['admin_bind_dn']}";
  error_log("$log_prefix Failed to bind as ${LDAP['admin_bind_dn']}",0);
  exit(1);
 }

 return $ldap_connection;

}


###################################

function ldap_auth_username($ldap_connection,$username, $password) {

 # Search for the DN for the given username.  If found, try binding with the DN and user's password.
 # If the binding succeeds, return the DN.

 global $log_prefix, $LDAP;

 $ldap_search = ldap_search( $ldap_connection, $LDAP['base_dn'], "${LDAP['account_attribute']}=${username}");

 if (!$ldap_search) {
  error_log("$log_prefix Couldn't search for $username",0);
  return FALSE;
 }

 $result = ldap_get_entries($ldap_connection, $ldap_search);
 if ($result["count"] == 1) {

  $auth_ldap_connection = open_ldap_connection();
  $can_bind = @ldap_bind( $auth_ldap_connection, $result[0]['dn'], $password);
  ldap_close($auth_ldap_connection);

  if ($can_bind) {
   preg_match("/{$LDAP['account_attribute']}=(.*?),/",$result[0]['dn'],$dn_match);
   return $dn_match[1];
   ldap_unbind($auth_ldap_connection);
  }
  else {
   return FALSE;
  }

 }


}


###################################

function ldap_setup_auth($ldap_connection, $password) {

 #For the initial setup we need to make sure that whoever's running it has the default admin user
 #credentials as passed in ADMIN_BIND_*
 global $log_prefix, $LDAP;

  $auth_ldap_connection = open_ldap_connection();
  $can_bind = @ldap_bind($auth_ldap_connection, $LDAP['admin_bind_dn'], $password);
  ldap_close($auth_ldap_connection);
  if ($can_bind) { return TRUE; } else { return FALSE; }


}



##################################

function ldap_hashed_password($password) {

 $hashed_pwd = '{MD5}' . base64_encode(md5($password,TRUE));
 return $hashed_pwd;

}


##################################


function ldap_get_user_list($ldap_connection,$start=0,$entries=NULL,$sort="asc",$sort_key=NULL,$filters=NULL,$fields=NULL) {

 global $log_prefix, $LDAP;

 if (!isset($fields)) { $fields = array("uid", "givenname", "sn"); }
 if (!isset($sort_key)) { $sort_key = $LDAP['account_attribute']; }

 $ldap_search = ldap_search($ldap_connection, "${LDAP['user_dn']}", "(&(${LDAP['account_attribute']}=*)$filters)", $fields);

 $result = ldap_get_entries($ldap_connection, $ldap_search);

 $records = array();
 foreach ($result as $record) {

  if (isset($record[$sort_key][0])) {

   $add_these = array();
   foreach($fields as $this_attr) {
    if ($this_attr != $sort_key) { $add_these[$this_attr] = $record[$this_attr][0]; }
   }

   $records[$record[$sort_key][0]] = $add_these;

  }
 }

 if ($sort == "asc") { ksort($records); } else { krsort($records); }

 return(array_slice($records,$start,$entries));


}

##################################


function ldap_get_highest_id($ldap_connection,$type="uid") {

 global $log_prefix, $LDAP, $min_uid, $min_gid;

 if ($type == "uid") {
  $this_id = $min_uid;
  $record_base_dn = $LDAP['user_dn'];
  $record_filter = "(${LDAP['account_attribute']}=*)";
  $record_attribute = array("uidNumber");
 }
 else {
  $type = "gid";
  $this_id = $min_gid;
  $record_base_dn = $LDAP['group_dn'];
  $record_filter = "(objectClass=posixGroup)";
  $record_attribute = array("gidNumber");
 }

 $filter = "(&(objectclass=device)(cn=last${type}))";
 $ldap_search = ldap_search($ldap_connection, "${LDAP['base_dn']}", $filter, array('serialNumber'));
 $result = ldap_get_entries($ldap_connection, $ldap_search);

 $fetched_id = $result[0]['serialnumber'][0];

 if (isset($fetched_id) and is_numeric($fetched_id)){

  $this_id = $fetched_id;

 }
 else {

  $ldap_search = ldap_search($ldap_connection, $record_base_dn, $record_filter, $record_attribute);
  $result = ldap_get_entries($ldap_connection, $ldap_search);

  foreach ($result as $record) {
   if (isset($record[$record_attribute][0])) {
    if ($record[$record_attribute][0] > $this_id) { $this_id = $record[$record_attribute][0]; }
   }
  }

 }

 return($this_id);

}


##################################


function ldap_get_group_list($ldap_connection,$start=0,$entries=NULL,$sort="asc",$filters=NULL) {

 global $log_prefix, $LDAP;

 $ldap_search = ldap_search($ldap_connection, "${LDAP['group_dn']}", "(&(objectclass=*)$filters)");

 $result = ldap_get_entries($ldap_connection, $ldap_search);

 $records = array();
 foreach ($result as $record) {

  if (isset($record['cn'][0])) {

   array_push($records, $record['cn'][0]);

  }
 }

 if ($sort == "asc") { sort($records); } else { rsort($records); }

 return(array_slice($records,$start,$entries));


}


##################################

function ldap_get_group_members($ldap_connection,$group_name,$start=0,$entries=NULL,$sort="asc") {

 global $log_prefix, $LDAP;

 $ldap_search = ldap_search($ldap_connection, "${LDAP['group_dn']}", "(cn=$group_name)", array($LDAP['group_membership_attribute']));

 $result = ldap_get_entries($ldap_connection, $ldap_search);

 $records = array();
 foreach ($result[0][$LDAP['group_membership_attribute']] as $record => $value) {

  if ($record != 'count' and isset($value)) {
   array_push($records, $value);
  }
 }

 if ($sort == "asc") { sort($records); } else { rsort($records); }

 return(array_slice($records,$start,$entries));


}


##################################

function ldap_is_group_member($ldap_connection,$group_name,$username) {

 global $log_prefix, $LDAP;

 $ldap_search = ldap_search($ldap_connection, "${LDAP['group_dn']}", "(cn=$group_name)");
 $result = ldap_get_entries($ldap_connection, $ldap_search);

 if ($LDAP['group_membership_uses_uid'] == FALSE) {
  $username = "${LDAP['account_attribute']}=$username,${LDAP['user_dn']}";
 }

 if (preg_grep ("/^${username}$/i", $result[0][$LDAP['group_membership_attribute']])) {
  return TRUE;
 }
 else {
  return FALSE;
 }

}


##################################

function ldap_new_group($ldap_connection,$group_name) {

 global $log_prefix, $LDAP;

 if (isset($group_name)) {

  $ldap_search = ldap_search($ldap_connection, "${LDAP['group_dn']}", "(cn=$group_name,${LDAP['group_dn']})");
  $result = ldap_get_entries($ldap_connection, $ldap_search);

  if ($result['count'] == 0) {

    $highest_gid = ldap_get_highest_id($ldap_connection,'gid');
    $new_gid = $highest_gid + 1;

    $add_group = ldap_add($ldap_connection,
                          "cn=$group_name,${LDAP['group_dn']}",
                          array(  'objectClass' => array( 'top', 'groupOfUniqueNames', 'posixGroup' ),
                                  'cn' => $group_name,
                                  'gidNumber' => $new_gid,
                                  $LDAP['group_membership_attribute'] => ''
                               )
                         );

   if ($add_group) {
    error_log("$log_prefix Added new group $group_name",0);
    $update_gid = ldap_mod_replace($ldap_connection, "cn=lastGID,${LDAP['base_dn']}", array( 'serialNumber' => $new_gid ));
    if ($update_gid) {
     error_log("$log_prefix Updated cn=lastGID with $new_gid",0);
     return TRUE;
    }
    else {
     error_log("$log_prefix Failed to update cn=lastGID",0);
    }
   }

  }
  else {
   error_log("$log_prefix Create group; group $group_name already exists.",0);
  }
 }
 else {
  error_log("$log_prefix Create group; group name wasn't set.",0);
 }

 return FALSE;

}


##################################

function ldap_delete_group($ldap_connection,$group_name) {

 global $log_prefix, $LDAP;

 if (isset($group_name)) {

  $delete = ldap_delete($ldap_connection, "cn=$group_name,${LDAP['group_dn']}");

  if ($delete) {
   error_log("$log_prefix Deleted group $group_name",0);
   return TRUE;
  }
  else {
   error_log("$log_prefix Couldn't delete group $group_name",0);
   return FALSE;
  }

 }

}


##################################

function ldap_get_gid_of_group($ldap_connection,$group_name) {

 global $log_prefix, $LDAP;

 if (isset($group_name)) {

  $ldap_search = ldap_search($ldap_connection, "${LDAP['group_dn']}", "(cn=$group_name)", array("gidNumber"));
  $result = ldap_get_entries($ldap_connection, $ldap_search);

  if (isset($result[0]['gidnumber'][0]) and is_numeric($result[0]['gidnumber'][0])) {
    return $result[0]['gidnumber'][0];
  }

 }

 return FALSE;

}


##################################

function ldap_new_account($ldap_connection,$first_name,$last_name,$username,$password) {

 global $log_prefix, $LDAP, $DEFAULT_USER_SHELL, $DEFAULT_USER_GROUP, $EMAIL_DOMAIN;

 if (isset($first_name) and isset($last_name) and isset($username) and isset($password)) {

  $ldap_search = ldap_search($ldap_connection, "${LDAP['user_dn']}", "(${LDAP['account_attribute']}=$username,${LDAP['user_dn']})");
  $result = ldap_get_entries($ldap_connection, $ldap_search);

  if ($result['count'] == 0) {

    $highest_uid = ldap_get_highest_id($ldap_connection,'uid');
    $new_uid = $highest_uid + 1;

    $default_gid = ldap_get_gid_of_group($ldap_connection,$DEFAULT_USER_GROUP);

    if (!is_numeric($default_gid)) {
     $group_add = ldap_new_group($ldap_connection,$username);
     $gid = ldap_get_gid_of_group($ldap_connection,$username);
     $add_to_group = $username;
    }
    else {
     $gid = $default_gid;
     $add_to_group = $DEFAULT_USER_GROUP;
    }

    $hashed_pass = ldap_hashed_password($password);

    $user_info = array(  'objectClass' => array( 'person', 'inetOrgPerson', 'posixAccount' ),
                         'uid' => $username,
                         'givenName' => $first_name,
                         'sn' => $last_name,
                         'cn' => "$first_name $last_name",
                         'displayName' => "$first_name $last_name",
                         'uidNumber' => $new_uid,
                         'gidNumber' => $gid,
                         'loginShell' => $DEFAULT_USER_SHELL,
                         'homeDirectory' => "/home/$username",
                         'userPassword' => $hashed_pass
                      );

    if (isset($EMAIL_DOMAIN)) {
     array_push($user_info, ['mail' => "$username@$EMAIL_DOMAIN"]);
    }

    $add_account = ldap_add($ldap_connection,
                          "${LDAP['account_attribute']}=$username,${LDAP['user_dn']}",
                          $user_info
                         );

   if ($add_account) {
    error_log("$log_prefix Created new account: $username",0);
    ldap_add_member_to_group($ldap_connection,$add_to_group,$username);
    $update_uid = ldap_mod_replace($ldap_connection, "cn=lastUID,${LDAP['base_dn']}", array( 'serialNumber' => $new_uid ));
    if ($update_uid) {
     error_log("$log_prefix Create account; Updated cn=lastUID with $new_uid",0);
     return TRUE;
    }
    else {
     error_log("$log_prefix Create account; Failed to update cn=lastUID",0);
    }

   }
   else {
    error_log("$log_prefix Create account; couldn't create the account for $username",0);
   }

  }
  else {
   error_log("$log_prefix Create account; Account for $username already exists",0);
  }

 }
 else {
  error_log("$log_prefix Create account; missing parameters",0);
 }


 return FALSE;

}


##################################

function ldap_delete_account($ldap_connection,$username) {

 global $log_prefix, $LDAP;

 if (isset($username)) {

  $delete = ldap_delete($ldap_connection, "${LDAP['account_attribute']}=$username,${LDAP['user_dn']}");

  if ($delete) {
   error_log("$log_prefix Deleted account for $username",0);
   return TRUE;
  }
  else {
   error_log("$log_prefix Couldn't delete account for $username",0);
   return FALSE;
  }

 }

}


##################################

function ldap_add_member_to_group($ldap_connection,$group_name,$username) {

  global $log_prefix, $LDAP;

  $group_dn = "cn=${group_name},${LDAP['group_dn']}";

  if ($LDAP['group_membership_uses_uid'] == FALSE) {
   $username = "${LDAP['account_attribute']}=$username,${LDAP['user_dn']}";
  }

  $group_update = array($LDAP['group_membership_attribute'] => $username);
  $update = ldap_mod_add($ldap_connection,$group_dn,$group_update);

  if ($update) {
   error_log("$log_prefix Added $username to $group_name",0);
   return TRUE;
  }
  else {
   error_log("$log_prefix Couldn't add $username to $group_name",0);
   return FALSE;
  }

}


##################################

function ldap_delete_member_from_group($ldap_connection,$group_name,$username) {

  global $log_prefix, $LDAP;

  $group_dn = "cn=${group_name},${LDAP['group_dn']}";

  if ($LDAP['group_membership_uses_uid'] == FALSE) {
   $username = "${LDAP['account_attribute']}=$username,${LDAP['user_dn']}";
  }

  $group_update = array($LDAP['group_membership_attribute'] => $username);
  $update = ldap_mod_del($ldap_connection,$group_dn,$group_update);

  if ($update) {
   error_log("$log_prefix Removed $username from $group_name",0);
   return TRUE;
  }
  else {
   error_log("$log_prefix Couldn't remove $username from $group_name",0);
   return FALSE;
  }

}


##################################

function ldap_change_password($ldap_connection,$username,$new_password) {

 global $log_prefix, $LDAP;

 #Find DN of user

 $ldap_search = ldap_search( $ldap_connection, $LDAP['base_dn'], "${LDAP['account_attribute']}=${username}");
 if ($ldap_search) {
  $result = ldap_get_entries($ldap_connection, $ldap_search);
  if ($result["count"] == 1) {
  $this_dn=$result[0]['dn'];
  }
  else {
   error_log("$log_prefix Couldn't find the DN for user $username");
   return FALSE;
  }
 }
 else {
  error_log("$log_prefix Couldn't perform an LDAP search for ${LDAP['account_attribute']}=${username}",0);
  return FALSE;
 }

 #Hash password

 $hashed_pass = ldap_hashed_password($new_password);

 $entries["userPassword"] = $new_password;
 $update = ldap_mod_replace($ldap_connection, $this_dn, $entries);

 if ($update) {
  error_log("$log_prefix Updated the password for $username");
  return TRUE;
 }
 else {
  error_log("$log_prefix Couldn't update the password for $username");
  return TRUE;
 }

}


?>
