<?php

set_include_path( ".:" . __DIR__ . "/../includes/");

include_once "web_functions.inc.php";
include_once "ldap_functions.inc.php";
include_once "totp_functions.inc.php";
include_once "module_functions.inc.php";
set_page_access("admin");

render_header($ORGANISATION_NAME . ' ' . t('mfa_status.title'));
render_submenu();

$ldap_connection = open_ldap_connection();

// Handle admin actions
if (isset($_POST['disable_user_mfa'])) {
  $username = $_POST['username'];

  // Get user DN
  $user_search = ldap_search($ldap_connection, $LDAP['user_dn'],
    "({$LDAP['account_attribute']}=" . ldap_escape($username, "", LDAP_ESCAPE_FILTER) . ")",
    array('dn'));

  if ($user_search) {
    $user_entry = ldap_get_entries($ldap_connection, $user_search);
    if ($user_entry['count'] > 0) {
      $user_dn = $user_entry[0]['dn'];

      if (totp_disable($ldap_connection, $user_dn)) {
        render_alert_banner(t('mfa_status.alert_disabled', array('username' => htmlspecialchars($username))));
      } else {
        render_alert_banner(t('mfa_status.alert_disable_failed', array('username' => htmlspecialchars($username))), "danger", 15000);
      }
    }
  }
}

if (isset($_POST['reset_grace_period'])) {
  $username = $_POST['username'];

  // Get user DN
  $user_search = ldap_search($ldap_connection, $LDAP['user_dn'],
    "({$LDAP['account_attribute']}=" . ldap_escape($username, "", LDAP_ESCAPE_FILTER) . ")",
    array('dn'));

  if ($user_search) {
    $user_entry = ldap_get_entries($ldap_connection, $user_search);
    if ($user_entry['count'] > 0) {
      $user_dn = $user_entry[0]['dn'];

      // Set status to pending and reset enrolled date
      $modifications = array(
        'totpStatus' => 'pending',
        'totpEnrolledDate' => gmdate('YmdHis') . 'Z',
      );

      if (ldap_mod_replace($ldap_connection, $user_dn, $modifications)) {
        render_alert_banner(t('mfa_status.alert_grace_reset', array('username' => htmlspecialchars($username))));
      } else {
        render_alert_banner(t('mfa_status.alert_grace_reset_failed', array('username' => htmlspecialchars($username))), "danger", 15000);
      }
    }
  }
}

// Get all users with MFA status
$people = ldap_get_user_list($ldap_connection);

// Enhance with MFA status information
$mfa_stats = array(
  'total' => 0,
  'active' => 0,
  'pending' => 0,
  'disabled' => 0,
  'not_configured' => 0,
  'required' => 0,
  'grace_expired' => 0,
);

foreach ($people as $username => $attribs) {
  $mfa_stats['total']++;

  // Get MFA status
  $user_search = ldap_search($ldap_connection, $LDAP['user_dn'],
    "({$LDAP['account_attribute']}=" . ldap_escape($username, "", LDAP_ESCAPE_FILTER) . ")",
    array('totpStatus', 'totpEnrolledDate', 'memberOf'));

  if ($user_search) {
    $user_entry = ldap_get_entries($ldap_connection, $user_search);
    if ($user_entry['count'] > 0) {
      $status = isset($user_entry[0]['totpstatus'][0]) ? $user_entry[0]['totpstatus'][0] : 'none';
      $enrolled_date = isset($user_entry[0]['totpenrolleddate'][0]) ? $user_entry[0]['totpenrolleddate'][0] : null;

      $people[$username]['mfa_status'] = $status;
      $people[$username]['mfa_enrolled_date'] = $enrolled_date;

      // Check if user requires MFA
      $mfa_result = totp_user_requires_mfa($ldap_connection, $username, $MFA_REQUIRED_GROUPS);
      $requires_mfa = $mfa_result['required'];
      $people[$username]['mfa_required'] = $requires_mfa;

      // Calculate grace period
      if ($status == 'pending' && $enrolled_date && $requires_mfa) {
        // Use group-specific grace period if available, otherwise use global setting
        $grace_period = $mfa_result['grace_period'] !== null ? $mfa_result['grace_period'] : $MFA_GRACE_PERIOD_DAYS;
        $days_remaining = totp_grace_period_remaining($enrolled_date, $grace_period);
        $people[$username]['grace_days_remaining'] = $days_remaining;

        if ($days_remaining <= 0) {
          $mfa_stats['grace_expired']++;
        }
      }

      // Update statistics
      switch ($status) {
        case 'active':
          $mfa_stats['active']++;
          break;
        case 'pending':
          $mfa_stats['pending']++;
          break;
        case 'disabled':
          $mfa_stats['disabled']++;
          break;
        default:
          $mfa_stats['not_configured']++;
      }

      if ($requires_mfa) {
        $mfa_stats['required']++;
      }
    }
  }
}

?>

<div class="container">

  <h2><?php echo t('mfa_status.heading'); ?></h2>

  <!-- Statistics Cards -->
  <div class="row" style="margin-bottom: 20px;">
    <div class="col-md-2">
      <div class="card">
        <div class="card-body text-center">
          <h3><?php echo $mfa_stats['total']; ?></h3>
          <p><?php echo t('mfa_status.stat_total'); ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card border-success">
        <div class="card-body text-center">
          <h3><?php echo $mfa_stats['active']; ?></h3>
          <p><?php echo t('mfa.active'); ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card border-warning">
        <div class="card-body text-center">
          <h3><?php echo $mfa_stats['pending']; ?></h3>
          <p><?php echo t('mfa_status.pending'); ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card">
        <div class="card-body text-center">
          <h3><?php echo $mfa_stats['not_configured']; ?></h3>
          <p><?php echo t('mfa.not_configured'); ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card border-info">
        <div class="card-body text-center">
          <h3><?php echo $mfa_stats['required']; ?></h3>
          <p><?php echo t('mfa_status.stat_required'); ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card border-danger">
        <div class="card-body text-center">
          <h3><?php echo $mfa_stats['grace_expired']; ?></h3>
          <p><?php echo t('mfa_status.stat_grace_expired'); ?></p>
        </div>
      </div>
    </div>
  </div>

  <!-- Configuration Summary -->
  <div class="card">
    <div class="card-header">
      <h4 class="card-title"><?php echo t('mfa_status.config_heading'); ?></h4>
    </div>
    <div class="card-body">
      <table class="table table-condensed">
        <tr>
          <th style="width: 200px;"><?php echo t('mfa_status.enabled_label'); ?></th>
          <td><?php echo $MFA_FEATURE_ENABLED ? '<span class="badge bg-success">' . t('label.yes') . '</span>' : '<span class="badge bg-secondary">' . t('label.no') . '</span>'; ?></td>
        </tr>
        <?php if ($MFA_FEATURE_ENABLED && !empty($MFA_REQUIRED_GROUPS)) { ?>
          <tr>
            <th><?php echo t('mfa_status.required_groups_label'); ?></th>
            <td><?php echo implode(', ', $MFA_REQUIRED_GROUPS); ?></td>
          </tr>
          <tr>
            <th><?php echo t('mfa.grace_period'); ?></th>
            <td><?php echo $MFA_GRACE_PERIOD_DAYS . ' ' . t('unit.days'); ?></td>
          </tr>
          <tr>
            <th><?php echo t('mfa_status.totp_issuer_label'); ?></th>
            <td><?php echo htmlspecialchars($MFA_TOTP_ISSUER); ?></td>
          </tr>
        <?php } ?>
      </table>
    </div>
  </div>

  <!-- User List -->
  <div class="card">
    <div class="card-header">
      <h4 class="card-title"><?php echo t('mfa_status.user_list_heading'); ?></h4>
    </div>
    <div class="card-body">
      <input class="form-control" id="search_input" type="text" placeholder="<?php echo htmlspecialchars(t('mfa_status.search_placeholder')); ?>">
    </div>
    <table class="table table-striped">
      <thead>
        <tr>
          <th><?php echo t('mfa_status.col_username'); ?></th>
          <th><?php echo t('mfa_status.col_mfa_status'); ?></th>
          <th><?php echo t('mfa_status.col_required'); ?></th>
          <th><?php echo t('mfa_status.col_grace_period'); ?></th>
          <th><?php echo t('mfa_status.col_actions'); ?></th>
        </tr>
      </thead>
      <tbody id="userlist">
        <script>
          document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search_input');
            searchInput.addEventListener('keyup', function() {
              const value = this.value.toLowerCase();
              const rows = document.querySelectorAll('#userlist tr');
              rows.forEach(function(row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.indexOf(value) > -1 ? '' : 'none';
              });
            });
          });
        </script>
        <?php foreach ($people as $username => $attribs) { ?>
          <tr>
            <td>
              <a href="<?php echo $THIS_MODULE_PATH; ?>/show_user.php?account_identifier=<?php echo urlencode($username); ?>">
                <?php echo htmlspecialchars($username); ?>
              </a>
            </td>
            <td>
              <?php
                $status = isset($attribs['mfa_status']) ? $attribs['mfa_status'] : 'none';
                switch ($status) {
                  case 'active':
                    echo '<span class="badge bg-success">' . t('mfa.active') . '</span>';
                    break;
                  case 'pending':
                    echo '<span class="badge bg-warning text-dark">' . t('mfa_status.pending') . '</span>';
                    break;
                  case 'disabled':
                    echo '<span class="badge bg-secondary">' . t('mfa.disabled') . '</span>';
                    break;
                  default:
                    echo '<span class="badge bg-secondary">' . t('mfa.not_configured') . '</span>';
                }
              ?>
            </td>
            <td>
              <?php
                if (isset($attribs['mfa_required']) && $attribs['mfa_required']) {
                  echo '<span class="badge bg-info text-dark">' . t('label.yes') . '</span>';
                } else {
                  echo '<span class="badge bg-secondary">' . t('label.no') . '</span>';
                }
              ?>
            </td>
            <td>
              <?php
                if (isset($attribs['grace_days_remaining'])) {
                  $days = $attribs['grace_days_remaining'];
                  if ($days > 3) {
                    echo '<span class="badge bg-success">' . $days . ' ' . t('unit.days') . '</span>';
                  } elseif ($days > 0) {
                    echo '<span class="badge bg-warning text-dark">' . $days . ' ' . t('unit.days') . '</span>';
                  } else {
                    echo '<span class="badge bg-danger">' . t('mfa.grace_expired') . '</span>';
                  }
                } else {
                  echo '<span class="text-muted">' . t('label.na') . '</span>';
                }
              ?>
            </td>
            <td>
              <?php if ($status == 'active') { ?>
                <form method="POST" style="display: inline;">
                  <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">
                  <button type="submit" name="disable_user_mfa" class="btn btn-sm btn-danger"
                          onclick="return confirm(<?php echo json_encode(t('mfa_status.disable_confirm', array('username' => $username))); ?>);">
                    <?php echo t('mfa.disable'); ?>
                  </button>
                </form>
              <?php } ?>

              <?php if ($status == 'pending' && isset($attribs['grace_days_remaining']) && $attribs['grace_days_remaining'] <= 0) { ?>
                <form method="POST" style="display: inline;">
                  <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">
                  <button type="submit" name="reset_grace_period" class="btn btn-danger btn-sm"
                          onclick="return confirm(<?php echo json_encode(t('mfa_status.reset_grace_confirm', array('username' => $username))); ?>);">
                    <?php echo t('mfa_status.reset_grace_btn'); ?>
                  </button>
                </form>
              <?php } ?>
            </td>
          </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>

</div>

<?php
ldap_close($ldap_connection);
render_footer();
?>
