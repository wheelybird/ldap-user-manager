<?php

/**
 * Group MFA Settings Tab
 * Displays MFA configuration form for the group
 */

if (!defined('LDAP_USER_MANAGER')) {
  die('Direct access not permitted');
}

// Get attribute names from config
$mfa_objectclass = $GROUP_MFA_ATTRS['objectclass'];
$mfa_required_attr = strtolower($GROUP_MFA_ATTRS['required']);
$mfa_grace_period_attr = strtolower($GROUP_MFA_ATTRS['grace_period']);

// Get current MFA settings for this group
$group_mfa_required = false;
$group_mfa_grace_period = 7; // default

if (isset($this_group[0][$mfa_required_attr][0])) {
  $group_mfa_required = (strcasecmp($this_group[0][$mfa_required_attr][0], 'TRUE') == 0);
}
if (isset($this_group[0][$mfa_grace_period_attr][0])) {
  $group_mfa_grace_period = intval($this_group[0][$mfa_grace_period_attr][0]);
}

// Check if group has MFA object class
$has_mfa_objectclass = false;
if (isset($this_group[0]['objectclass'])) {
  foreach ($this_group[0]['objectclass'] as $oc) {
    if (strcasecmp($oc, $mfa_objectclass) == 0) {
      $has_mfa_objectclass = true;
      break;
    }
  }
}
?>
<form action="<?php print $CURRENT_PAGE; ?>" method="post">
  <input type="hidden" name="group_name" value="<?php print urlencode($group_cn); ?>">

  <div class="col-md-8">
    <p class="text-muted">
      <?php print t('group_mfa.description'); ?>
    </p>

  <?php
  // Display current MFA status
  $mfa_required_attr = $GROUP_MFA_ATTRS['required'];
  $mfa_grace_period_attr = $GROUP_MFA_ATTRS['grace_period'];
  $has_mfa_required = isset($this_group[0][strtolower($mfa_required_attr)][0]);
  $mfa_is_required = $has_mfa_required && (strcasecmp($this_group[0][strtolower($mfa_required_attr)][0], 'TRUE') == 0);

  if ($has_mfa_required) {
    $mfa_grace = isset($this_group[0][strtolower($mfa_grace_period_attr)][0]) ?
                 $this_group[0][strtolower($mfa_grace_period_attr)][0] : t('group_mfa.not_set');
    $badge_class = $mfa_is_required ? 'bg-success' : 'bg-secondary';
    $badge_text = $mfa_is_required ? t('group_mfa.required') : t('group_mfa.not_required');
  ?>
  <div class="alert alert-info mb-3">
    <strong><i class="bi bi-shield-lock"></i> <?php print t('group_mfa.current_status'); ?></strong>
    <span class="badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
    <?php if ($mfa_is_required) { ?>
      <span class="text-muted ms-2"><?php print t('group_mfa.grace_period_days', array('days' => $mfa_grace)); ?></span>
    <?php } ?>
  </div>
  <?php } else { ?>
  <div class="alert alert-secondary mb-3">
    <strong><i class="bi bi-shield-lock"></i> <?php print t('group_mfa.current_status'); ?></strong>
    <span class="badge bg-secondary"><?php print t('group_mfa.not_configured'); ?></span>
  </div>
  <?php } ?>

  <div class="row mb-3">
    <label class="col-md-3 col-form-label">
      <?php print t('group_mfa.require_mfa'); ?>
    </label>
    <div class="col-md-4">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox"
               id="mfa_required" name="mfa_required"
               <?php if ($group_mfa_required) echo 'checked'; ?>
               <?php if (count($group_members)==0) print 'disabled'; ?>>
        <label class="form-check-label" for="mfa_required">
          <?php print t('group_mfa.members_must_enroll'); ?>
        </label>
      </div>
    </div>
  </div>

  <div class="row mb-3">
    <label for="mfa_grace_period" class="col-md-3 col-form-label">
      <?php print t('group_mfa.grace_period_label'); ?>
    </label>
    <div class="col-md-4">
      <input type="number" class="form-control"
             id="mfa_grace_period" name="mfa_grace_period"
             value="<?php echo $group_mfa_grace_period; ?>"
             min="1" max="365"
             <?php if (count($group_members)==0) print 'disabled'; ?>>
      <small class="form-text text-muted">
        <?php print t('group_mfa.grace_period_help'); ?>
      </small>
    </div>
  </div>

  <?php if (!$has_mfa_objectclass) { ?>
  <div class="alert alert-info">
    <i class="bi bi-info-circle"></i>
    <?php print t('group_mfa.enable_adds_objectclass', array('objectclass' => $mfa_objectclass)); ?>
  </div>
  <?php } ?>

  <div class="row">
    <div class="col-md-4 offset-md-3">
      <button type="submit" class="btn btn-info"
              id="submit_mfa"
              name="submit_mfa"
              <?php if (count($group_members)==0) print 'disabled'; ?>>
        <?php print t('group_mfa.save_settings'); ?>
      </button>
    </div>
  </div>
</div>
</form>
