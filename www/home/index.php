<?php

set_include_path( ".:" . __DIR__ . "/../includes/");

include_once "web_functions.inc.php";
include_once "ldap_functions.inc.php";

// Include TOTP functions if MFA is enabled (needed for schema check)
if ($MFA_FEATURE_ENABLED == TRUE) {
  include_once "totp_functions.inc.php";
}

set_page_access("user");

// Open LDAP connection for all checks
$ldap_connection = open_ldap_connection();

// Check MFA schema status dynamically if MFA is enabled
if ($MFA_FEATURE_ENABLED == TRUE) {
  $MFA_SCHEMA_OK = totp_check_schema($ldap_connection);
  $MFA_FULLY_OPERATIONAL = $MFA_SCHEMA_OK;
}

// Check user's MFA status
$mfa_status = array('needs_setup' => false);

if ($MFA_FULLY_OPERATIONAL) {
  $mfa_status = totp_get_user_mfa_status($ldap_connection, $USER_ID, $MFA_REQUIRED_GROUPS, $MFA_GRACE_PERIOD_DAYS);
}

render_header($ORGANISATION_NAME . ' ' . t('account_manager.title'));

?>

<div class="container">

  <?php if ($mfa_status['needs_setup']): ?>
  <div class="alert alert-warning">
    <h4><strong><?php echo t('home.mfa_required_title'); ?></strong></h4>
    <p>
      <?php echo t('home.mfa_required_body'); ?>
      <?php if ($mfa_status['days_remaining'] !== null): ?>
        <strong><?php echo t('home.mfa_days_remaining', array('days' => $mfa_status['days_remaining'], 'suffix' => $mfa_status['days_remaining'] != 1 ? t('home.day_suffix_plural') : '')); ?></strong>
      <?php endif; ?>
    </p>
    <p><?php echo t('home.mfa_cta'); ?></p>
  </div>
  <?php endif; ?>

  <?php
  // Check password expiry status
  if (isset($_SESSION['password_should_warn']) && $_SESSION['password_should_warn'] === true && isset($_SESSION['password_days_remaining'])):
    $days_remaining = $_SESSION['password_days_remaining'];
  ?>
  <div class="alert alert-warning">
    <h4><strong><?php echo t('home.password_expiry_title'); ?></strong></h4>
    <p>
      <?php echo t('home.password_expiry_body', array('days' => $days_remaining, 'suffix' => $days_remaining != 1 ? t('home.day_suffix_plural') : '')); ?>
    </p>
    <a href="<?php echo url('/change_password'); ?>" class="btn btn-warning"><?php echo t('home.password_expiry_button'); ?></a>
  </div>
  <?php endif; ?>

  <?php
  // Check account expiration status
  if (isset($_SESSION['account_should_warn']) && $_SESSION['account_should_warn'] === true && isset($_SESSION['account_days_remaining'])):
    $account_days_remaining = $_SESSION['account_days_remaining'];
  ?>
  <div class="alert alert-danger">
    <h4><strong><?php echo t('home.account_expiry_title'); ?></strong></h4>
    <p>
      <?php echo t('home.account_expiry_body', array('days' => $account_days_remaining, 'suffix' => $account_days_remaining != 1 ? t('home.day_suffix_plural') : '')); ?>
    </p>
    <?php if (!empty($SUPPORT_EMAIL)): ?>
    <p><strong><?php echo t('home.support_contact'); ?></strong> <a href="mailto:<?php echo htmlspecialchars($SUPPORT_EMAIL); ?>" class="text-white"><u><?php echo htmlspecialchars($SUPPORT_EMAIL); ?></u></a></p>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <div class="row">
    <div class="col-md-12">
      <h2><?php echo t('home.welcome'); ?><?php if(isset($USER_ID)) { echo ', ' . htmlspecialchars($USER_ID); } ?></h2>
      <p class="lead"><?php echo t('home.lead'); ?></p>
    </div>
  </div>

  <div class="row">

    <?php if (in_array('change_password', array_keys($MODULES)) && $MODULES['change_password'] == 'auth') { ?>
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          <h4><?php echo t('home.change_password_title'); ?></h4>
        </div>
        <div class="card-body">
          <p><?php echo t('home.change_password_body'); ?></p>
          <a href="<?php echo url('/change_password'); ?>" class="btn btn-primary"><?php echo t('module.change_password'); ?></a>
        </div>
      </div>
    </div>
    <?php } ?>

    <?php if (isset($MODULES['manage_mfa']) && $MODULES['manage_mfa'] == 'auth') { ?>
    <div class="col-md-6">
      <div class="card <?php echo $mfa_status['needs_setup'] ? 'border-warning' : ''; ?>">
        <div class="card-header <?php echo $mfa_status['needs_setup'] ? 'bg-warning text-dark' : ''; ?>">
          <h4>
            <?php echo t('home.manage_mfa_title'); ?>
            <?php if ($mfa_status['needs_setup']): ?>
              <span class="badge bg-warning text-dark float-end"><?php echo t('home.manage_mfa_required'); ?></span>
            <?php endif; ?>
          </h4>
        </div>
        <div class="card-body">
          <?php if ($mfa_status['needs_setup']): ?>
            <p><?php echo t('home.manage_mfa_action'); ?></p>
          <?php else: ?>
            <p><?php echo t('home.manage_mfa_body'); ?></p>
          <?php endif; ?>
          <a href="<?php echo url('/manage_mfa'); ?>" class="btn btn-<?php echo $mfa_status['needs_setup'] ? 'warning' : 'primary'; ?>"><?php echo t('module.manage_mfa'); ?></a>
        </div>
      </div>
    </div>
    <?php } ?>

    <?php if (isset($MODULES['account_manager']) && ($MODULES['account_manager'] == 'admin' && $IS_ADMIN)) { ?>
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          <h4><?php echo t('home.account_manager_title'); ?></h4>
        </div>
        <div class="card-body">
          <p><?php echo t('home.account_manager_body'); ?></p>
          <a href="<?php echo url('/account_manager'); ?>" class="btn btn-success"><?php echo t('module.account_manager'); ?></a>
        </div>
      </div>
    </div>
    <?php } ?>

    <?php if (isset($MODULES['system_config']) && ($MODULES['system_config'] == 'admin' && $IS_ADMIN)) { ?>
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          <h4><?php echo t('home.system_config_title'); ?></h4>
        </div>
        <div class="card-body">
          <p><?php echo t('home.system_config_body'); ?></p>
          <a href="<?php echo url('/system_config'); ?>" class="btn btn-info"><?php echo t('module.system_config'); ?></a>
        </div>
      </div>
    </div>
    <?php } ?>

  </div>

</div>

<?php

render_footer();

?>
