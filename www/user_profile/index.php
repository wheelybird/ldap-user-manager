<?php

set_include_path( ".:" . __DIR__ . "/../includes/");

include_once "web_functions.inc.php";
include_once "ldap_functions.inc.php";
include_once "totp_functions.inc.php";

set_page_access("user");

render_header($ORGANISATION_NAME . ' ' . t('user_profile.title'));

$ldap_connection = open_ldap_connection();

// Build attribute map for user-editable attributes with friendly labels
$attribute_map = array();

// Map attribute names to input types and friendly labels
$attribute_config = array(
  'telephonenumber' => array('label' => t('attr.telephonenumber'), 'inputtype' => 'tel'),
  'mobile' => array('label' => t('attr.mobile'), 'inputtype' => 'tel'),
  'displayname' => array('label' => t('attr.displayname'), 'inputtype' => 'text'),
  'description' => array('label' => t('attr.description'), 'inputtype' => 'textarea'),
  'title' => array('label' => t('attr.title'), 'inputtype' => 'text'),
  'jpegphoto' => array('label' => t('attr.jpegphoto'), 'inputtype' => 'binary'),
  'sshpublickey' => array('label' => t('attr.sshpublickey'), 'inputtype' => 'multipleinput'),

  // Common additional attributes with good defaults
  'homephone' => array('label' => t('attr.homephone'), 'inputtype' => 'tel'),
  'facsimiletelephonenumber' => array('label' => t('attr.facsimiletelephonenumber'), 'inputtype' => 'tel'),
  'pager' => array('label' => t('attr.pager'), 'inputtype' => 'tel'),
  'employeetype' => array('label' => t('attr.employeetype'), 'inputtype' => 'text'),
  'employeenumber' => array('label' => t('attr.employeenumber'), 'inputtype' => 'text'),
  'preferredlanguage' => array('label' => t('attr.preferredlanguage'), 'inputtype' => 'text'),
  'street' => array('label' => t('attr.street'), 'inputtype' => 'text'),
  'postaladdress' => array('label' => t('attr.postaladdress'), 'inputtype' => 'textarea'),
  'postalcode' => array('label' => t('attr.postalcode'), 'inputtype' => 'text'),
  'l' => array('label' => t('attr.l'), 'inputtype' => 'text'),
  'st' => array('label' => t('attr.st'), 'inputtype' => 'text'),
  'postofficebox' => array('label' => t('attr.postofficebox'), 'inputtype' => 'text'),
  'usercertificate' => array('label' => t('attr.usercertificate'), 'inputtype' => 'binary'),
  'labeleduri' => array('label' => t('attr.labeleduri'), 'inputtype' => 'url'),
  'carlicense' => array('label' => t('attr.carlicense'), 'inputtype' => 'text'),
  'roomnumber' => array('label' => t('attr.roomnumber'), 'inputtype' => 'text'),
  'departmentnumber' => array('label' => t('attr.departmentnumber'), 'inputtype' => 'text'),
  'initials' => array('label' => t('attr.initials'), 'inputtype' => 'text'),
);

// Build attribute map from editable attributes list
foreach ($USER_EDITABLE_ATTRIBUTES as $attr) {
  $attr_lower = strtolower($attr);

  // Use predefined config if available, otherwise create default
  if (isset($attribute_config[$attr_lower])) {
    $attribute_map[$attr_lower] = $attribute_config[$attr_lower];
  } else {
    // Fallback to translated attribute labels when available.
    $attribute_map[$attr_lower] = array(
      'label' => t('attr.' . $attr_lower) ?? ucwords(str_replace('_', ' ', $attr_lower)),
      'inputtype' => 'text'
    );
  }
}

// Load current user's LDAP entry
$user_search = ldap_search($ldap_connection, $LDAP['user_dn'],
  "({$LDAP['account_attribute']}=" . ldap_escape($USER_ID, "", LDAP_ESCAPE_FILTER) . ")",
  array_merge(array('dn', 'cn', 'givenname', 'sn'), array_keys($attribute_map)));

if (!$user_search) {
  render_alert_banner(t('user_profile.load_failed'), "danger", 15000);
  render_footer();
  exit(1);
}

$user = ldap_get_entries($ldap_connection, $user_search);

if ($user['count'] == 0) {
  render_alert_banner(t('user_profile.not_found'), "danger", 15000);
  render_footer();
  exit(1);
}

$dn = $user[0]['dn'];
$to_update = array();

// Load current attribute values
foreach ($attribute_map as $attribute => $attr_config) {
  if (isset($user[0][$attribute]) && $user[0][$attribute]['count'] > 0) {
    $$attribute = $user[0][$attribute];
  } else {
    $$attribute = array();
  }

  // Handle file uploads
  if (isset($_FILES[$attribute]['size']) && $_FILES[$attribute]['size'] > 0) {

    // Special validation for jpegPhoto
    if ($attribute == 'jpegphoto') {
      $upload_error = null;

      // Check file size (500KB limit for LDAP performance)
      $max_size = 500 * 1024; // 500KB in bytes
      if ($_FILES[$attribute]['size'] > $max_size) {
        $upload_error = t('user_profile.upload_too_large');
      }

      // Check MIME type
      $finfo = new finfo(FILEINFO_MIME_TYPE);
      $mime_type = $finfo->file($_FILES[$attribute]['tmp_name']);
      if ($mime_type !== 'image/jpeg') {
        $upload_error = t('user_profile.upload_must_be_jpeg', array('mime' => $mime_type));
      }

      // Verify it's actually a valid JPEG by attempting to load it
      $image_check = @imagecreatefromjpeg($_FILES[$attribute]['tmp_name']);
      if ($image_check === false) {
        $upload_error = t('user_profile.upload_invalid_jpeg');
      } else {
        imagedestroy($image_check);
      }

      if ($upload_error) {
        render_alert_banner($upload_error, "danger", 15000);
        // Skip this file upload
        continue;
      }
    }

    $this_attribute = array();
    $this_attribute['count'] = 1;
    $this_attribute[0] = file_get_contents($_FILES[$attribute]['tmp_name']);
    $$attribute = $this_attribute;
    $to_update[$attribute] = $this_attribute;
    unset($to_update[$attribute]['count']);
  }

  // Handle form submission
  if (isset($_POST['update_profile']) && isset($_POST[$attribute])) {
    $this_attribute = array();

    if (is_array($_POST[$attribute])) {
      // Multi-valued attribute
      foreach ($_POST[$attribute] as $key => $value) {
        if ($value != "") {
          $this_attribute[$key] = trim($value);
        }
      }
      $this_attribute['count'] = count($this_attribute);
    } elseif ($_POST[$attribute] != "") {
      // Single-valued attribute
      $this_attribute['count'] = 1;
      $this_attribute[0] = trim($_POST[$attribute]);
    }

    // Check if value changed
    if ($this_attribute != $$attribute) {
      $$attribute = $this_attribute;
      $to_update[$attribute] = $this_attribute;
      unset($to_update[$attribute]['count']);
    }
  }

  // Handle checkbox (boolean) attributes
  if (isset($_POST['update_profile']) && isset($attr_config['inputtype']) && $attr_config['inputtype'] == 'checkbox') {
    if (!isset($_POST[$attribute])) {
      // Checkbox not checked - set to empty
      $this_attribute = array();
      if ($this_attribute != $$attribute) {
        $$attribute = $this_attribute;
        $to_update[$attribute] = array(); // Will delete the attribute
      }
    }
  }
}

// Process form submission
if (isset($_POST['update_profile'])) {

  // Security validation: ensure all attributes being updated are actually editable
  $security_violation = false;
  foreach (array_keys($to_update) as $attr_to_update) {
    if (!is_user_editable($attr_to_update)) {
      error_log("$log_prefix Security violation: User $USER_ID attempted to edit blacklisted attribute: $attr_to_update");
      $security_violation = true;
      break;
    }
  }

  if ($security_violation) {
    render_alert_banner(t('user_profile.security_violation'), "danger", 15000);
  } elseif (!empty($to_update)) {
    // Perform LDAP update
    $updated_profile = @ldap_mod_replace($ldap_connection, $dn, $to_update);

    if ($updated_profile) {
      render_alert_banner(t('user_profile.updated'));

      // Reload user data to show updated values
      $user_search = ldap_search($ldap_connection, $LDAP['user_dn'],
        "({$LDAP['account_attribute']}=" . ldap_escape($USER_ID, "", LDAP_ESCAPE_FILTER) . ")",
        array_merge(array('dn'), array_keys($attribute_map)));

      if ($user_search) {
        $user = ldap_get_entries($ldap_connection, $user_search);
        if ($user['count'] > 0) {
          foreach ($attribute_map as $attribute => $attr_config) {
            if (isset($user[0][$attribute]) && $user[0][$attribute]['count'] > 0) {
              $$attribute = $user[0][$attribute];
            } else {
              $$attribute = array();
            }
          }
        }
      }
    } else {
      ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $detailed_err);
      error_log("$log_prefix Failed to update profile for $USER_ID: " . ldap_error($ldap_connection) . " -- " . $detailed_err);
      render_alert_banner(t('user_profile.update_failed'), "danger", 15000);
    }
  } else {
    render_alert_banner(t('user_profile.no_changes'), "info", 4000);
  }
}

// Get user's display name
$display_name = $USER_ID;
if (isset($user[0]['cn'][0])) {
  $display_name = $user[0]['cn'][0];
} elseif (isset($user[0]['givenname'][0]) || isset($user[0]['sn'][0])) {
  $givenname = isset($user[0]['givenname'][0]) ? $user[0]['givenname'][0] : '';
  $sn = isset($user[0]['sn'][0]) ? $user[0]['sn'][0] : '';
  $display_name = trim($givenname . ' ' . $sn);
}

?>

<div class="container">

  <h2><?php echo t('user_profile.heading'); ?></h2>
  <p class="text-muted"><?php echo t('user_profile.subtitle'); ?></p>

  <div class="card">
    <div class="card-header">
      <h4 class="card-title"><?php echo htmlspecialchars($display_name); ?></h4>
      <p class="text-muted mb-0"><?php echo t('user_profile.username'); ?> <?php echo htmlspecialchars($USER_ID); ?></p>
    </div>
    <div class="card-body">

      <?php if (empty($attribute_map)) { ?>
        <div class="alert alert-info">
          <p class="text-center"><?php echo t('user_profile.no_editable'); ?></p>
        </div>
      <?php } else { ?>

        <form method="post" enctype="multipart/form-data">

          <?php
          foreach ($attribute_map as $attribute => $attr_config) {
            $label = $attr_config['label'];
            $inputtype = isset($attr_config['inputtype']) ? $attr_config['inputtype'] : 'text';

            // Render the attribute field
            render_attribute_fields($attribute, $label, $$attribute, $USER_ID, "", $inputtype);
          }
          ?>

          <div class="row mb-3">
            <div class="col-sm-9 offset-sm-3">
              <button type="submit" name="update_profile" class="btn btn-primary">
                <i class="bi bi-save"></i> <?php echo t('user_profile.update'); ?>
              </button>
              <a href="<?php echo url('/'); ?>" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> <?php echo t('user_profile.cancel'); ?>
              </a>
            </div>
          </div>

        </form>

      <?php } ?>

    </div>
  </div>

  <?php
  // Display password information if password policy is enabled
  if ($PASSWORD_POLICY_ENABLED && $PPOLICY_ENABLED && $PASSWORD_EXPIRY_DAYS > 0) {
    include_once "password_policy_functions.inc.php";

    // Get password information
    $user_search_pwd = ldap_search($ldap_connection, $LDAP['user_dn'],
      "({$LDAP['account_attribute']}=" . ldap_escape($USER_ID, "", LDAP_ESCAPE_FILTER) . ")",
      array('dn'));

    if ($user_search_pwd) {
      $user_pwd = ldap_get_entries($ldap_connection, $user_search_pwd);
      if ($user_pwd['count'] > 0) {
        $user_dn_pwd = $user_pwd[0]['dn'];
        $password_changed_time = password_policy_get_changed_time($ldap_connection, $user_dn_pwd);

        if ($password_changed_time) {
          // Calculate password age and expiry
          $timestamp = strtotime($password_changed_time);
          $password_changed_formatted = date('F j, Y \a\t g:i A', $timestamp);
          $age_seconds = time() - $timestamp;
          $password_age_days = floor($age_seconds / 86400);
          $password_expires_in_days = $PASSWORD_EXPIRY_DAYS - $password_age_days;
          $expiry_date = date('F j, Y', $timestamp + ($PASSWORD_EXPIRY_DAYS * 86400));

          // Determine status
          $status_class = 'success';
          $status_text = t('status.ok');
          if ($password_expires_in_days <= 0) {
            $status_class = 'danger';
            $status_text = t('status.expired');
          } elseif ($password_expires_in_days <= $PASSWORD_EXPIRY_WARNING_DAYS) {
            $status_class = 'warning';
            $status_text = t('status.expiring_soon');
          }
  ?>

  <div class="card mt-3">
    <div class="card-header">
      <h4 class="card-title">
        <?php echo t('user_profile.password_info'); ?>
        <span class="badge bg-<?php echo $status_class; ?> float-end"><?php echo $status_text; ?></span>
      </h4>
    </div>
    <div class="card-body">
      <div class="row mb-3">
        <div class="col-sm-4"><strong><?php echo t('user_profile.last_changed'); ?></strong></div>
        <div class="col-sm-8"><?php echo $password_changed_formatted; ?></div>
      </div>
      <div class="row mb-3">
        <div class="col-sm-4"><strong><?php echo t('user_profile.password_age'); ?></strong></div>
        <div class="col-sm-8"><?php echo $password_age_days . ' ' . ($password_age_days != 1 ? t('unit.days') : t('unit.day')); ?></div>
      </div>
      <div class="row mb-3">
        <div class="col-sm-4"><strong><?php echo t('user_profile.expiry_date'); ?></strong></div>
        <div class="col-sm-8"><?php echo $expiry_date; ?></div>
      </div>
      <div class="row mb-3">
        <div class="col-sm-4"><strong><?php echo t('user_profile.days_until_expiry'); ?></strong></div>
        <div class="col-sm-8">
          <?php if ($password_expires_in_days > 0): ?>
            <span class="text-<?php echo $status_class; ?>"><?php echo $password_expires_in_days . ' ' . ($password_expires_in_days != 1 ? t('unit.days') : t('unit.day')); ?></span>
          <?php else: ?>
            <span class="text-danger"><?php echo t('user_profile.password_expired'); ?></span>
          <?php endif; ?>
        </div>
      </div>
      <div class="row">
        <div class="col-sm-12">
          <a href="<?php echo url('/change_password'); ?>" class="btn btn-primary">
            <i class="bi bi-key"></i> <?php echo t('user_profile.change_password'); ?>
          </a>
        </div>
      </div>
    </div>
  </div>

  <?php
        }
      }
    }
  }
  ?>

  <?php
  // Display account lifecycle information if enabled
  if ($LIFECYCLE_ENABLED == TRUE && $ACCOUNT_EXPIRY_ENABLED == TRUE) {
    include_once "account_lifecycle_functions.inc.php";

    // Get account expiration information
    $account_days_remaining = null;
    $account_is_expired = account_lifecycle_is_expired($ldap_connection, $user_dn_profile, $account_days_remaining);
    $account_should_warn = account_lifecycle_should_warn($ldap_connection, $user_dn_profile, $account_days_remaining);
    $account_expiry_date_formatted = account_lifecycle_get_expiry_date_formatted($ldap_connection, $user_dn_profile, 'F j, Y');
    $account_expiry_timestamp = account_lifecycle_get_expiry_timestamp($ldap_connection, $user_dn_profile);
    $account_create_time = account_lifecycle_get_create_time($ldap_connection, $user_dn_profile);

    // Convert LDAP timestamps to formatted strings
    $account_created_formatted = null;
    if ($account_create_time) {
      $create_timestamp = account_lifecycle_ldap_to_timestamp($account_create_time);
      if ($create_timestamp) {
        $account_created_formatted = date('F j, Y', $create_timestamp);
      }
    }

    // Only display if account has an expiration date or is approaching expiry
    if ($account_expiry_date_formatted !== null || $account_is_expired || $account_should_warn) {

      // Determine account status
      $account_status_class = 'success';
      $account_status_text = t('status.ok');
      if ($account_is_expired) {
        $account_status_class = 'danger';
        $account_status_text = t('status.expired');
      } elseif ($account_should_warn) {
        $account_status_class = 'warning';
        $account_status_text = t('status.expiring_soon');
      }
  ?>

  <div class="card mt-3">
    <div class="card-header">
      <h4 class="card-title">
        <?php echo t('user_profile.account_info'); ?>
        <span class="badge bg-<?php echo $account_status_class; ?> float-end"><?php echo $account_status_text; ?></span>
      </h4>
    </div>
    <div class="card-body">
      <?php if ($account_created_formatted): ?>
      <div class="row mb-3">
        <div class="col-sm-4"><strong><?php echo t('user_profile.account_created'); ?></strong></div>
        <div class="col-sm-8"><?php echo $account_created_formatted; ?></div>
      </div>
      <?php endif; ?>
      <?php if ($account_expiry_date_formatted !== null): ?>
      <div class="row mb-3">
        <div class="col-sm-4"><strong><?php echo t('user_profile.expiration_date'); ?></strong></div>
        <div class="col-sm-8"><?php echo $account_expiry_date_formatted; ?></div>
      </div>
      <div class="row mb-3">
        <div class="col-sm-4"><strong><?php echo t('user_profile.days_until_expiry'); ?></strong></div>
        <div class="col-sm-8">
          <?php if ($account_is_expired): ?>
            <?php $abs_days = abs($account_days_remaining); ?>
            <span class="text-danger"><?php echo t('user_profile.account_expired_ago', array('days' => $abs_days, 'suffix' => ($abs_days != 1 ? 's' : ''))); ?></span>
          <?php elseif ($account_days_remaining !== null && $account_days_remaining > 0): ?>
            <span class="text-<?php echo $account_status_class; ?>"><?php echo $account_days_remaining . ' ' . ($account_days_remaining != 1 ? t('unit.days') : t('unit.day')); ?></span>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
      <?php if ($account_should_warn || $account_is_expired): ?>
      <div class="alert alert-<?php echo $account_status_class; ?>">
        <?php if ($account_is_expired): ?>
          <?php echo t('user_profile.account_expired_contact'); ?>
        <?php else: ?>
          <?php echo t('user_profile.account_expiry_soon'); ?>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <?php
    }
  }
  ?>

</div>

<?php
ldap_close($ldap_connection);
render_footer();
?>
