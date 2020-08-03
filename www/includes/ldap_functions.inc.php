<?php

$LDAP_CONNECTION_WARNING = FALSE;

###################################

function open_ldap_connection() {

 global $log_prefix, $LDAP, $SENT_HEADERS, $LDAP_DEBUG;

 $ldap_connection = @ ldap_connect($LDAP['uri']);

 if (!$ldap_connection) {
  print "Problem: Can't connect to the LDAP server at ${LDAP['uri']}";
  die("Can't connect to the LDAP server at ${LDAP['uri']}");
  exit(1);
 }

 ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);

 if (!preg_match("/^ldaps:/", $LDAP['uri'])) {

  $tls_result = @ ldap_start_tls($ldap_connection);

  if ($tls_result != TRUE) {

   error_log("$log_prefix Failed to start STARTTLS connection to ${LDAP['uri']}: " . ldap_error($ldap_connection),0);

   if ($LDAP["require_starttls"] == TRUE) {
    print "<div style='position: fixed;bottom: 0;width: 100%;' class='alert alert-danger'>Fatal:  Couldn't create a secure connection to ${LDAP['uri']} and LDAP_REQUIRE_STARTTLS is TRUE.</div>";
    exit(0);
   }
   else {
    if ($SENT_HEADERS == TRUE) {
      print "<div style='position: fixed;bottom: 0px;width: 100%;height: 20px;border-bottom:solid 20px yellow;'>WARNING: Insecure LDAP connection to ${LDAP['uri']}</div>";
    }
    ldap_close($ldap_connection);
    $ldap_connection = @ ldap_connect($LDAP['uri']);
    ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
   }
  }
  elseif ($LDAP_DEBUG == TRUE) {
    error_log("$log_prefix Start STARTTLS connection to ${LDAP['uri']}",0);
  }
 }

 $bind_result = @ ldap_bind( $ldap_connection, $LDAP['admin_bind_dn'], $LDAP['admin_bind_pwd']);

 if ($bind_result != TRUE) {

   $this_error = "Failed to bind to ${LDAP['uri']} as ${LDAP['admin_bind_dn']}";
   if ($LDAP_DEBUG == TRUE) { $this_error .= " with password ${LDAP['admin_bind_pwd']}"; }
   $this_error .= ": " . ldap_error($ldap_connection);
   print "Problem: Failed to bind as ${LDAP['admin_bind_dn']}";
   error_log("$log_prefix $this_error",0);

   exit(1);

 }
 elseif ($LDAP_DEBUG == TRUE) {
   error_log("$log_prefix Bound to ${LDAP['uri']} as ${LDAP['admin_bind_dn']}",0);
 }

 return $ldap_connection;

}


###################################

function ldap_auth_username($ldap_connection,$username, $password) {

 # Search for the DN for the given username.  If found, try binding with the DN and user's password.
 # If the binding succeeds, return the DN.

 global $log_prefix, $LDAP, $LDAP_DEBUG;

 $ldap_search_query="${LDAP['account_attribute']}=" . ldap_escape($username, "", LDAP_ESCAPE_FILTER);
 $ldap_search = @ ldap_search( $ldap_connection, $LDAP['base_dn'], $ldap_search_query );

 if ($LDAP_DEBUG == TRUE) { "$log_prefix Running LDAP search: $ldap_search_query"; }

 if (!$ldap_search) {
  error_log("$log_prefix Couldn't search for ${username}: " . ldap_error($ldap_connection),0);
  return FALSE;
 }

 $result = ldap_get_entries($ldap_connection, $ldap_search);
 if ($LDAP_DEBUG == TRUE) { error_log("$log_prefix LDAP search returned ${result["count"]} records for $username",0); }

 if ($result["count"] == 1) {

  $auth_ldap_connection = open_ldap_connection();
  $can_bind = @ldap_bind( $auth_ldap_connection, $result[0]['dn'], $password);
  ldap_close($auth_ldap_connection);

  if ($can_bind) {
   preg_match("/{$LDAP['account_attribute']}=(.*?),/",$result[0]['dn'],$dn_match);
   return $dn_match[1];
   ldap_unbind($auth_ldap_connection);
   if ($LDAP_DEBUG == TRUE) { error_log("$log_prefix Able to bind as $username",0); }
  }
  else {
   if ($LDAP_DEBUG == TRUE) { error_log("$log_prefix Unable to bind as ${username}: " . ldap_error($ldap_connection),0); }
   return FALSE;
  }

 }


}


###################################

function ldap_setup_auth($ldap_connection, $password) {

 #For the initial setup we need to make sure that whoever's running it has the default admin user
 #credentials as passed in ADMIN_BIND_*
 global $log_prefix, $LDAP, $LDAP_DEBUG;

  if ($LDAP_DEBUG == TRUE) { error_log("$log_prefix Initial setup: opening another LDAP connection to test authentication as ${LDAP['admin_bind_dn']}.",0); }
  $auth_ldap_connection = open_ldap_connection();
  $can_bind = @ldap_bind($auth_ldap_connection, $LDAP['admin_bind_dn'], $password);
  ldap_close($auth_ldap_connection);
  if ($can_bind) {
    if ($LDAP_DEBUG == TRUE) { error_log("$log_prefix Initial setup: able to authenticate as ${LDAP['admin_bind_dn']}.",0); }
    return TRUE;
  }
  else {
    $this_error="Initial setup: Unable to authenticate as ${LDAP['admin_bind_dn']}";
    if ($LDAP_DEBUG == TRUE) { $this_error .= " with password $password"; }
    $this_error .= ". The password used to authenticate for /setup should be the same as set by LDAP_ADMIN_BIND_PWD. ";
    $this_error .= ldap_error($ldap_connection);
    error_log("$log_prefix $this_error",0);
    return FALSE;
  }


}


#################################

function generate_salt($length) {

 $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ./';

 mt_srand((double)microtime() * 1000000);

 $salt = '';
 while (strlen($salt) < $length) {
    $salt .= substr($permitted_chars, (rand() % strlen($permitted_chars)), 1);
  }

 return $salt;

}


##################################

function ldap_hashed_password($password) {

 global $PASSWORD_HASH, $log_prefix;

 $check_algos = array (
                       "SHA512CRYPT" => "CRYPT_SHA512",
                       "SHA256CRYPT" => "CRYPT_SHA256",
#                       "BLOWFISH"    => "CRYPT_BLOWFISH",
#                       "EXT_DES"     => "CRYPT_EXT_DES",
                       "MD5CRYPT"    => "CRYPT_MD5"
                      );

 $remaining_algos = array (
                            "SSHA",
                            "SHA",
                            "SMD5",
                            "MD5",
                            "CRYPT",
                            "CLEAR"
                          );

 $available_algos = array();

 foreach ($check_algos as $algo_name => $algo_function) {
   if (defined($algo_function) and constant($algo_function) != 0) {
     array_push($available_algos, $algo_name);
   }
   else {
     error_log("$log_prefix password hashing - the system doesn't support ${algo_name}");
   }
 }
 $available_algos = array_merge($available_algos, $remaining_algos);

 if (isset($PASSWORD_HASH)) {
   if (!in_array($PASSWORD_HASH, $available_algos)) {
     $hash_algo = $available_algos[0];
     error_log("$log_prefix LDAP password: the chosen hash method ($PASSWORD_HASH) wasn't available");
   }
   else {
     $hash_algo = $PASSWORD_HASH;
   }
 }
 else {
   $hash_algo = $available_algos[0];
 }
 error_log("$log_prefix LDAP password: using '${hash_algo}' as the hashing method");

 $hash_algo = 'SSHA';

 switch ($hash_algo) {

  case 'SHA512CRYPT':
    $hashed_pwd = '{CRYPT}' . crypt($password, '$6$' . generate_salt(8));
    break;

  case 'SHA256CRYPT':
    $hashed_pwd = '{CRYPT}' . crypt($password, '$5$' . generate_salt(8));
    break;

# Blowfish & EXT_DES didn't work
#  case 'BLOWFISH':
#    $hashed_pwd = '{CRYPT}' . crypt($password, '$2a$12$' . generate_salt(13));
#    break;

#  case 'EXT_DES':
#    $hashed_pwd = '{CRYPT}' . crypt($password, '_' . generate_salt(8));
#    break;

  case 'MD5CRYPT':
    $hashed_pwd = '{CRYPT}' . crypt($password, '$1$' . generate_salt(9));
    break;

  case 'SMD5':
    $salt = generate_salt(8);
    $hashed_pwd = '{SMD5}' . base64_encode(md5($password . $salt, TRUE) . $salt);
    break;

  case 'MD5':
    $hashed_pwd = '{MD5}' . base64_encode(md5($password, TRUE));
    break;

  case 'SHA':
    $hashed_pwd = '{SHA}' . base64_encode(sha1($password, TRUE));
    break;

  case 'SSHA':
    $salt = generate_salt(8);
    $hashed_pwd = '{SSHA}' . base64_encode(sha1($password . $salt, TRUE) . $salt);
    break;

  case 'CRYPT':
    $salt = generate_salt(2);
    $hashed_pwd = '{CRYPT}' . crypt($password, $salt);
    break;

  case 'CLEAR':
    error_log("$log_prefix password hashing - WARNING - Saving password in cleartext. This is extremely bad practice and should never ever be done in a production environment.");
    $hashed_pwd = $password;
    break;


 }

 error_log("$log_prefix password update - algo $hash_algo | pwd $hashed_pwd");

 return $hashed_pwd;

}


##################################


function ldap_get_user_list($ldap_connection,$start=0,$entries=NULL,$sort="asc",$sort_key=NULL,$filters=NULL,$fields=NULL) {

 global $log_prefix, $LDAP, $LDAP_DEBUG;

 if (!isset($fields)) { $fields = array_unique( array("${LDAP['account_attribute']}", "givenname", "sn", "mail")); }

 if (!isset($sort_key)) { $sort_key = $LDAP['account_attribute']; }

 $this_filter = "(&(${LDAP['account_attribute']}=*)$filters)";

 $ldap_search = @ ldap_search($ldap_connection, "${LDAP['user_dn']}", $this_filter, $fields);
 $result = @ ldap_get_entries($ldap_connection, $ldap_search);
 if ($LDAP_DEBUG == TRUE) { error_log("$log_prefix: LDAP returned ${result['count']} users for ${LDAP['user_dn']} when using this filter: $this_filter",0); }

 $records = array();
 foreach ($result as $record) {

  if (isset($record[$sort_key][0])) {

   $add_these = array();
   foreach($fields as $this_attr) {
    if ($this_attr !== $sort_key) { $add_these[$this_attr] = $record[$this_attr][0]; }
   }

   $records[$record[$sort_key][0]] = $add_these;

  }
 }

 if ($sort == "asc") { ksort($records); } else { krsort($records); }

 return(array_slice($records,$start,$entries));


}

##################################


function ldap_get_highest_id($ldap_connection,$type="uid") {

 global $log_prefix, $LDAP, $LDAP_DEBUG, $min_uid, $min_gid;

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

 global $log_prefix, $LDAP, $LDAP_DEBUG;

 $this_filter = "(&(objectclass=*)$filters)";
 $ldap_search = ldap_search($ldap_connection, "${LDAP['group_dn']}", $this_filter);

 $result = @ ldap_get_entries($ldap_connection, $ldap_search);
 if ($LDAP_DEBUG == TRUE) { error_log("$log_prefix: LDAP returned ${result['count']} groups for ${LDAP['group_dn']} when using this filter: $this_filter",0); }

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

 global $log_prefix, $LDAP, $LDAP_DEBUG;

 $ldap_search_query = "(cn=". ldap_escape($group_name, "", LDAP_ESCAPE_FILTER) . ")";
 $ldap_search = @ ldap_search($ldap_connection, "${LDAP['group_dn']}", $ldap_search_query, array($LDAP['group_membership_attribute']));

 $result = @ ldap_get_entries($ldap_connection, $ldap_search);
 $result_count = $result[0]['count'];

 $records = array();

 if ($result_count > 0) {

  foreach ($result[0][$LDAP['group_membership_attribute']] as $key => $value) {

   if ($key !== 'count' and !empty($value)) {
    $this_member = preg_replace("/^.*?=(.*?),.*/", "$1", $value);
    array_push($records, $this_member);
    if ($LDAP_DEBUG == TRUE) { error_log("$log_prefix: ${value} is a member",0); }
   }

  }

  $actual_result_count = count($records);
  if ($LDAP_DEBUG == TRUE) { error_log("$log_prefix: LDAP returned $actual_result_count members of ${group_name} when using this search: $ldap_search_query and this filter: ${LDAP['group_membership_attribute']}",0); }

  if ($actual_result_count > 0) {
   if ($sort == "asc") { sort($records); } else { rsort($records); }
   return(array_slice($records,$start,$entries));
  }
  else {
   return array();
  }

 }
 else {
  return array();
 }

}


##################################

function ldap_is_group_member($ldap_connection,$group_name,$username) {

 global $log_prefix, $LDAP, $LDAP_DEBUG;

 $ldap_search_query = "(cn=" . ldap_escape($group_name, "", LDAP_ESCAPE_FILTER) . ")";
 $ldap_search = ldap_search($ldap_connection, "${LDAP['group_dn']}", $ldap_search_query);
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

 global $log_prefix, $LDAP, $LDAP_DEBUG;

 if (isset($group_name)) {

  $ldap_search_query = "(cn=" . ldap_escape($group_name, "", LDAP_ESCAPE_FILTER) . ",${LDAP['group_dn']})";
  $ldap_search = @ ldap_search($ldap_connection, "${LDAP['group_dn']}", $ldap_search_query);
  $result = @ ldap_get_entries($ldap_connection, $ldap_search);

  if ($result['count'] == 0) {

   $highest_gid = ldap_get_highest_id($ldap_connection,'gid');
   $new_gid = $highest_gid + 1;

   if ($LDAP['nis_schema'] == TRUE) {
    $new_group_array=array( 'objectClass' => array('top','posixGroup'),
                            'cn' => $group_name,
                            'gidNumber' => $new_gid
                          );
   }
   else {
    $new_group_array=array( 'objectClass' => array('top','groupOfUniqueNames','posixGroup'),
                            'cn' => $group_name,
                            'gidNumber' => $new_gid,
                            $LDAP['group_membership_attribute'] => ''
                          );
   }

   $group_dn="cn=$group_name,${LDAP['group_dn']}";

   $add_group = @ ldap_add($ldap_connection, $group_dn, $new_group_array);

   if (! $add_group ) {
    $this_error="$log_prefix LDAP: unable to add new group (${group_dn}): " . ldap_error($ldap_connection);
    if ($LDAP_DEBUG == TRUE) { error_log("$log_prefix: DEBUG add_group array: ". print_r($new_group_array,true)); }
    error_log($this_error,0);
   }
   else {
    error_log("$log_prefix Added new group $group_name",0);
    $update_gid = @ ldap_mod_replace($ldap_connection, "cn=lastGID,${LDAP['base_dn']}", array( 'serialNumber' => $new_gid ));
    if ($update_gid) {
     error_log("$log_prefix Updated cn=lastGID with $new_gid",0);
     return TRUE;
    }
    else {
     error_log("$log_prefix Failed to update cn=lastGID: " . ldap_error($ldap_connection) ,0);
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

 global $log_prefix, $LDAP, $LDAP_DEBUG;

 if (isset($group_name)) {

  $delete_query = "cn=" . ldap_escape($group_name, "", LDAP_ESCAPE_FILTER) . ",${LDAP['group_dn']}";
  $delete = @ ldap_delete($ldap_connection, $delete_query);

  if ($delete) {
   error_log("$log_prefix Deleted group $group_name",0);
   return TRUE;
  }
  else {
   error_log("$log_prefix Couldn't delete group $group_name" . ldap_error($ldap_connection) ,0);
   return FALSE;
  }

 }

}


##################################

function ldap_get_gid_of_group($ldap_connection,$group_name) {

 global $log_prefix, $LDAP, $LDAP_DEBUG;

 if (isset($group_name)) {

  $ldap_search_query = "(cn=" . ldap_escape($group_name, "", LDAP_ESCAPE_FILTER) . ")";
  $ldap_search = @ ldap_search($ldap_connection, "${LDAP['group_dn']}", $ldap_search_query , array("gidNumber"));
  $result = @ ldap_get_entries($ldap_connection, $ldap_search);

  if (isset($result[0]['gidnumber'][0]) and is_numeric($result[0]['gidnumber'][0])) {
    return $result[0]['gidnumber'][0];
  }

 }

 return FALSE;

}


##################################

function ldap_new_account($ldap_connection,$first_name,$last_name,$username,$password,$email) {

 global $log_prefix, $LDAP, $LDAP_DEBUG, $DEFAULT_USER_SHELL, $DEFAULT_USER_GROUP;

 if (isset($first_name) and isset($last_name) and isset($username) and isset($password)) {

  $ldap_search_query = "(${LDAP['account_attribute']}=" . ldap_escape($username, "", LDAP_ESCAPE_FILTER) . ",${LDAP['user_dn']})";
  $ldap_search = @ ldap_search($ldap_connection, "${LDAP['user_dn']}", $ldap_search_query);
  $result = @ ldap_get_entries($ldap_connection, $ldap_search);

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
                         'userPassword' => $hashed_pass,
                         'mail' => $email
                      );

      $add_account = @ ldap_add($ldap_connection,
                          "${LDAP['account_attribute']}=$username,${LDAP['user_dn']}",
                          $user_info
                         );

   if ($add_account) {
    error_log("$log_prefix Created new account: $username",0);
    ldap_add_member_to_group($ldap_connection,$add_to_group,$username);
    $update_uid = @ ldap_mod_replace($ldap_connection, "cn=lastUID,${LDAP['base_dn']}", array( 'serialNumber' => $new_uid ));
    if ($update_uid) {
     error_log("$log_prefix Create account; Updated cn=lastUID with $new_uid",0);
     return TRUE;
    }
    else {
     error_log("$log_prefix Create account; Failed to update cn=lastUID: " . ldap_error($ldap_connection),0);
    }

   }
   else {
    error_log("$log_prefix Create account; couldn't create the account for ${username}: " . ldap_error($ldap_connection),0);
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

 global $log_prefix, $LDAP, $LDAP_DEBUG;

 if (isset($username)) {

  $delete_query = "${LDAP['account_attribute']}=" . ldap_escape($username, "", LDAP_ESCAPE_FILTER) . ",${LDAP['user_dn']}";
  $delete = @ ldap_delete($ldap_connection, $delete_query);

  if ($delete) {
   error_log("$log_prefix Deleted account for $username",0);
   return TRUE;
  }
  else {
   error_log("$log_prefix Couldn't delete account for ${username}: " . ldap_error($ldap_connection),0);
   return FALSE;
  }

 }

}


##################################

function ldap_add_member_to_group($ldap_connection,$group_name,$username) {

  global $log_prefix, $LDAP, $LDAP_DEBUG;

  $group_dn = "cn=" . ldap_escape($group_name, "", LDAP_ESCAPE_FILTER) . ",${LDAP['group_dn']}";

  if ($LDAP['group_membership_uses_uid'] == FALSE) {
   $username = "${LDAP['account_attribute']}=$username,${LDAP['user_dn']}";
  }

  $group_update = array($LDAP['group_membership_attribute'] => $username);
  $update = @ ldap_mod_add($ldap_connection,$group_dn,$group_update);

  if ($update) {
   error_log("$log_prefix Added $username to $group_name",0);
   return TRUE;
  }
  else {
   error_log("$log_prefix Couldn't add $username to ${group_name}: " . ldap_error($ldap_connection),0);
   return FALSE;
  }

}


##################################

function ldap_delete_member_from_group($ldap_connection,$group_name,$username) {

  global $log_prefix, $LDAP, $LDAP_DEBUG;

  $group_dn = "cn=" . ldap_escape($group_name, "", LDAP_ESCAPE_FILTER) . ",${LDAP['group_dn']}";

  if ($LDAP['group_membership_uses_uid'] == FALSE) {
   $username = "${LDAP['account_attribute']}=$username,${LDAP['user_dn']}";
  }

  $group_update = array($LDAP['group_membership_attribute'] => $username);
  $update = @ ldap_mod_del($ldap_connection,$group_dn,$group_update);

  if ($update) {
   error_log("$log_prefix Removed $username from $group_name",0);
   return TRUE;
  }
  else {
   error_log("$log_prefix Couldn't remove $username from ${group_name}: " . ldap_error($ldap_connection),0);
   return FALSE;
  }

}


##################################

function ldap_change_password($ldap_connection,$username,$new_password) {

 global $log_prefix, $LDAP, $LDAP_DEBUG;

 #Find DN of user

 $ldap_search_query = "${LDAP['account_attribute']}=" . ldap_escape($username, "", LDAP_ESCAPE_FILTER);
 $ldap_search = @ ldap_search( $ldap_connection, $LDAP['base_dn'], $ldap_search_query);
 if ($ldap_search) {
  $result = @ ldap_get_entries($ldap_connection, $ldap_search);
  if ($result["count"] == 1) {
  $this_dn=$result[0]['dn'];
  }
  else {
   error_log("$log_prefix Couldn't find the DN for user $username");
   return FALSE;
  }
 }
 else {
  error_log("$log_prefix Couldn't perform an LDAP search for ${LDAP['account_attribute']}=${username}: " . ldap_error($ldap_connection),0);
  return FALSE;
 }

 $entries["userPassword"] = ldap_hashed_password($new_password);
 $update = @ ldap_mod_replace($ldap_connection, $this_dn, $entries);

 if ($update) {
  error_log("$log_prefix Updated the password for $username",0);
  return TRUE;
 }
 else {
  error_log("$log_prefix Couldn't update the password for ${username}: " . ldap_error($ldap_connection),0);
  return TRUE;
 }

}


?>
