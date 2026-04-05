<?php

/**
 * User Password Tab
 * Displays password expiry information
 */

if (!defined('LDAP_USER_MANAGER')) {
  die('Direct access not permitted');
}

// Get password change time and calculate expiry info
include_once "password_policy_functions.inc.php";

$password_changed_time = password_policy_get_changed_time($ldap_connection, $dn);

// Calculate password info if available
$password_changed_formatted = null;
$password_age_days = null;
$password_expires_in_days = null;
$expiry_date = null;
$status_badge = 'bg-secondary';
$status_text = t('user_password.unknown');

if ($password_changed_time) {
  // Calculate password age and expiry
  $timestamp = strtotime($password_changed_time);
  $password_changed_formatted = date('F j, Y \a\t g:i A', $timestamp);
  $age_seconds = time() - $timestamp;
  $password_age_days = floor($age_seconds / 86400);
  $password_expires_in_days = $PASSWORD_EXPIRY_DAYS - $password_age_days;
  $expiry_date = date('F j, Y', $timestamp + ($PASSWORD_EXPIRY_DAYS * 86400));

  // Determine status
  $status_badge = 'bg-success';
  $status_text = t('user_password.ok');
  if ($password_expires_in_days <= 0) {
    $status_badge = 'bg-danger';
    $status_text = t('user_password.expired');
  } elseif ($password_expires_in_days <= $PASSWORD_EXPIRY_WARNING_DAYS) {
    $status_badge = 'bg-warning text-dark';
    $status_text = t('user_password.expiring_soon');
  }
}
?>
<?php if ($password_changed_time): ?>
<table class="table table-condensed">
  <tr>
    <th width="30%"><?php print t('user_password.status'); ?></th>
    <td>
      <span class="badge <?php echo $status_badge; ?>"><?php echo $status_text; ?></span>
    </td>
  </tr>
  <tr>
    <th><?php print t('user_password.last_changed'); ?></th>
    <td><?php echo $password_changed_formatted; ?></td>
  </tr>
  <tr>
    <th><?php print t('user_password.password_age'); ?></th>
    <td><?php print t('user_password.days_value', array('days' => $password_age_days)); ?></td>
  </tr>
  <tr>
    <th><?php print t('user_password.expiry_date'); ?></th>
    <td><?php echo $expiry_date; ?></td>
  </tr>
  <tr>
    <th><?php print t('user_password.days_until_expiry'); ?></th>
    <td>
      <?php if ($password_expires_in_days > 0): ?>
        <span class="text-<?php echo $status_badge == 'bg-warning text-dark' ? 'warning' : 'success'; ?>">
          <?php print t('user_password.days_value', array('days' => $password_expires_in_days)); ?>
        </span>
      <?php else: ?>
        <span class="text-danger"><?php print t('user_password.password_has_expired'); ?></span>
      <?php endif; ?>
    </td>
  </tr>
</table>
<?php else: ?>
<div class="alert alert-info">
  <p><strong><i class="bi bi-info-circle"></i> <?php print t('user_password.info_not_available_title'); ?></strong></p>
  <p><?php print t('user_password.info_not_available_body'); ?></p>
</div>
<?php endif; ?>
