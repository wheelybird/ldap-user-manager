<?php

/**
 * User MFA Tab
 * Displays MFA status and backup code management
 */

if (!defined('LDAP_USER_MANAGER')) {
  die('Direct access not permitted');
}

?>
<table class="table table-condensed">
  <tr>
    <th width="30%"><?php print t('user_mfa.status'); ?></th>
    <td>
      <?php
        switch ($user_totp_status) {
          case 'active':
            echo '<span class="badge bg-success">' . t('user_mfa.active') . '</span>';
            break;
          case 'pending':
            echo '<span class="badge bg-warning text-dark">' . t('user_mfa.pending_setup') . '</span>';
            break;
          case 'disabled':
            echo '<span class="badge bg-secondary">' . t('user_mfa.disabled') . '</span>';
            break;
          default:
            echo '<span class="badge bg-secondary">' . t('user_mfa.not_configured') . '</span>';
        }
      ?>
    </td>
  </tr>
  <?php if ($user_requires_mfa) { ?>
  <tr>
    <th><?php print t('user_mfa.required'); ?></th>
    <td><span class="badge bg-info text-dark"><?php print t('label.yes'); ?></span> (<?php print t('user_mfa.required_by_group'); ?>)</td>
  </tr>
  <?php } ?>
  <?php if ($user_totp_status == 'active' && $user_backup_code_count > 0) { ?>
  <tr>
    <th><?php print t('user_mfa.backup_codes'); ?></th>
    <td>
      <span class="badge <?php echo $user_backup_code_count < 3 ? 'bg-warning text-dark' : 'bg-info text-dark'; ?>">
        <?php print t('user_mfa.remaining', array('count' => $user_backup_code_count)); ?>
      </span>
      <?php if ($user_backup_code_count < 3) { ?>
        <span class="text-warning"><small> - <?php print t('user_mfa.running_low'); ?></small></span>
      <?php } ?>
    </td>
  </tr>
  <?php } ?>
</table>

<?php if ($user_totp_status == 'active') { ?>
<?php if (!$MFA_SCHEMA_OK) { ?>
  <div class="alert alert-warning" style="margin-top: 15px;">
    <strong><?php print t('user_mfa.schema_missing_title'); ?></strong> <?php print t('user_mfa.schema_missing_body'); ?>
  </div>
<?php } else { ?>
<form method="post" style="margin-top: 15px;">
  <input type="hidden" name="account_identifier" value="<?php echo htmlspecialchars($account_identifier); ?>">
  <input type="hidden" name="regenerate_backup_codes" value="1">
  <button type="submit" class="btn btn-warning" onclick="return confirm(<?php echo json_encode(t('user_mfa.regenerate_confirm')); ?>);">
    <?php print t('user_mfa.regenerate_backup_codes'); ?>
  </button>
</form>
<?php } ?>
<?php } ?>
