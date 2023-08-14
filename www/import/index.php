<?php

set_include_path( ".:" . __DIR__ . "/../includes/");

include_once "web_functions.inc.php";
include_once "ldap_functions.inc.php";
set_page_access("admin");

render_header("$ORGANISATION_NAME import users");

$ldap_connection = open_ldap_connection();

if (isset($_POST['import_users'])) {
  _import_csv($ENFORCE_SAFE_SYSTEM_NAMES);
}

?>
<div class="container">
  <p>You can import users from a CSV file.<br/>CSV format/file must be : <em>utf8</em>, using <em>;</em> as fields separator, and <em>|</em> character for multi-valued fields.</p>
  <p>First line is ignored : it contains headers, each column is a LDAP account attribue. These are the available options :</p>
  <ul>
  <?php foreach($LDAP['default_attribute_map'] as $attrName => $tab) {
    echo '<li>' . $attrName . '   -  <em>required</em></li>';  
  }
  ?>
  <li>password</li>
  <li>cn  -  <em>(auto-generated with sn and givenname if empty)</em></li>
  </ul>
  <p>Except 'cn' and 'password', there are all mandatory fields. If <em>password</em> is blank or missing, a random one will be created.</p>
  <p>You can add a "<em>groups</em>" column. Each Group Name is separated by a "|" character. Group names are lowercased and trimmed. If the group does not exist, it is created.</p>
  <hr>
  
 <form enctype="multipart/form-data" action="<?php print $THIS_MODULE_PATH; ?>/index.php" method="post">
  <input type="file" name="csv" /><br/>
  <input type="hidden" name="import_users" value="1" />
  <button class="btn btn-default" type="submit">Import users</button>
 </form>
</div>
<?php

ldap_close($ldap_connection);
render_footer();

/**
 * ----------------------------------------------------------------------------------------------------
 * Get imported file and process it to create users.
 */
function _import_csv($ENFORCE_SAFE_SYSTEM_NAMES = 1) {
  if(isset($_FILES['csv']) && UPLOAD_ERR_OK == $_FILES['csv']['error']) {
    // Good we get a file
    $file = $_FILES['csv'];
    if(strpos($file['name'],'.csv') === FALSE) {
      render_alert_banner("Please submit a valid CSV file.", "danger"); 
      return;  
    } 
  } else {
    render_alert_banner("No file was provided.", "danger"); 
    return;
  }

  // We get the content
  $file_handle = fopen($file['tmp_name'], "r");

  $headings = fgetcsv($file_handle,null,";");
  if($headings == FALSE) {
    render_alert_banner("Error when parsing the CSV file.", "danger");   
  }

  // Connecting
  $ldap_connection = open_ldap_connection();

  // Reading through the file
  $i = 1;
  $ok = 0;
  while($contents = fgetcsv($file_handle,null,";")) {
    $i++;
    $generated_pwd = false;
    $new_account_r = [];
    // Processing line by line
    foreach($contents as $k => $value) {
      $attribute = $headings[$k];

      // Special treatements
      if("password" == $attribute) {
        if(!$value) {
          continue; // if empty we'll deal with password later on
        }
      }

      // Todo : chek emails, etc ..
      $new_account_r[$attribute] = [$value];
    }
    
    // Check uid / email
    if(!isset($new_account_r['uid'])) {
      render_alert_banner("Line $i : uid is a mandatory field.", "danger");
      continue;
    } else {
      $uid = reset($new_account_r['uid']);
    }
    if(!isset($new_account_r['mail'])) {
      render_alert_banner("Line $i : email is a mandatory field.", "danger");
      continue;
    }
    if(!isset($new_account_r['givenname'])) {
      render_alert_banner("Line $i : givenname is a mandatory field.", "danger");
      continue;
    }
    if(!isset($new_account_r['sn'])) {
      render_alert_banner("Line $i : sn is a mandatory field.", "danger");
      continue;
    }

    // CN ?
    if(!isset($new_account_r['cn'])) { 
      $separator = " ";
      if ($ENFORCE_SAFE_SYSTEM_NAMES == TRUE) {
        $separator = "";
      }
      $new_account_r['cn'] = [$new_account_r['givenname'][0] . $separator . $new_account_r['sn'][0]];
    }

    // Is password there ?
    if(!isset($new_account_r['password'])) {
      $generated_pwd = true;
      $new_account_r['password'] = [generatePassword(16)];
    }

    // Groups ?
    if(isset($new_account_r['groups'])) {
      $groups_str = $new_account_r['groups'][0];
      unset($new_account_r['groups']);
      $groups = explode('|',$groups_str);
      foreach($groups as $key => $group) {
        $groups[$key] = mb_strtolower(trim($group));
      }
    } else {
      $groups = [];
    }

    // Creation
    $new_account = ldap_new_account($ldap_connection, $new_account_r);
    if($new_account) {
      $suffix = "";
      if($generated_pwd) {
        $suffix = " Password is : " . $new_account_r['password'][0];
      }
      render_alert_banner("User uid : " . $uid . " has been created." . $suffix);

      // Group creation
      _create_group($ldap_connection, $uid, $groups);
      $ok++;
    } else {
      render_alert_banner("ERROR when processing uid :" . $uid . ".", "danger");
    }
  }

  // Récap
  $done = $i - 1;
  if($ok == $done) {
    render_alert_banner("Every $ok users have been succesfully created.");
  } else {
    render_alert_banner("Only $ok users out of $done have been successfully created.", "danger");
  }
}

/**
 * Create Groups for uid
 */
function _create_group($ldap_connection, $uid, $groups) {
  if(count($groups) == 0) {
    return NULL;
  }
  foreach($groups as $group) {
    if(!ldap_get_group_entry($ldap_connection, $group)) {
      ldap_new_group($ldap_connection, $group, $uid);
    } else {
      ldap_add_member_to_group($ldap_connection, $group, $uid);
    }
  }
}
?>
