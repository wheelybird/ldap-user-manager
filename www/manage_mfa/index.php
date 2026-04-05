<?php

set_include_path( ".:" . __DIR__ . "/../includes/");

include_once "web_functions.inc.php";
include_once "ldap_functions.inc.php";
include_once "totp_functions.inc.php";
include_once "audit_functions.inc.php";

set_page_access("user");

$ldap_connection = open_ldap_connection();

// Get user's DN
$status_attr = $TOTP_ATTRS['status'];
$enrolled_attr = $TOTP_ATTRS['enrolled_date'];
$scratch_attr = $TOTP_ATTRS['scratch_codes'];

$user_search = ldap_search($ldap_connection, $LDAP['user_dn'],
  "({$LDAP['account_attribute']}=" . ldap_escape($USER_ID, "", LDAP_ESCAPE_FILTER) . ")",
  array('dn', $status_attr, $enrolled_attr, $scratch_attr, 'memberOf'));

if (!$user_search) {
  die("Failed to find user");
}

$user_entry = ldap_get_entries($ldap_connection, $user_search);
if ($user_entry['count'] == 0) {
  die("User not found");
}

$status_attr_lower = strtolower($status_attr);
$enrolled_attr_lower = strtolower($enrolled_attr);

$user_dn = $user_entry[0]['dn'];
$totp_status = isset($user_entry[0][$status_attr_lower][0]) ? $user_entry[0][$status_attr_lower][0] : 'none';
$totp_enrolled_date = isset($user_entry[0][$enrolled_attr_lower][0]) ? $user_entry[0][$enrolled_attr_lower][0] : null;

// Get backup code count
$backup_code_count = totp_get_backup_code_count($ldap_connection, $user_dn);

// Check MFA schema status dynamically if MFA is enabled
if ($MFA_FEATURE_ENABLED == TRUE) {
  $MFA_SCHEMA_OK = totp_check_schema($ldap_connection);
  $MFA_FULLY_OPERATIONAL = $MFA_SCHEMA_OK;
}

// Check if user is in MFA-required group
$mfa_result = totp_user_requires_mfa($ldap_connection, $USER_ID, $MFA_REQUIRED_GROUPS);
$user_requires_mfa = $mfa_result['required'];
$grace_period_remaining = null;

if ($user_requires_mfa && $totp_status == 'pending' && $totp_enrolled_date) {
  // Use group-specific grace period if available, otherwise use global setting
  $grace_period = $mfa_result['grace_period'] !== null ? $mfa_result['grace_period'] : $MFA_GRACE_PERIOD_DAYS;
  $grace_period_remaining = totp_grace_period_remaining($totp_enrolled_date, $grace_period);
}

// Check if MFA schema is available
$schema_error = false;
if ($MFA_FEATURE_ENABLED && !$MFA_SCHEMA_OK) {
  $schema_error = true;
}

// Handle first code validation (AJAX)
if (isset($_POST['validate_first_code'])) {
  header('Content-Type: application/json');
  $secret = $_POST['secret'];
  $code = $_POST['code'];

  if (totp_validate_code($secret, $code, 1)) {
    echo json_encode(array('valid' => true, 'time_window' => floor(time() / 30)));
  } else {
    echo json_encode(array('valid' => false, 'message' => t('mfa.invalid_code')));
  }
  exit;
}

// Handle final enrolment form submission
if (isset($_POST['enrol_mfa'])) {
  if ($schema_error) {
    $error_message = t('mfa.unavailable_body');
  }
  elseif (!isset($_POST['code1']) || !isset($_POST['code2']) || !isset($_POST['time_window1'])) {
    $error_message = t('mfa.complete_both_steps');
  }
  else {
    $secret = $_POST['secret'];
    $code1 = $_POST['code1'];
    $code2 = $_POST['code2'];
    $time_window1 = $_POST['time_window1'];
    $time_window2 = floor(time() / 30);

    // Verify codes are from different time windows
    if ($time_window1 == $time_window2) {
      $error_message = t('mfa.second_code_different_window');
    }
    // Validate second code
    elseif (!totp_validate_code($secret, $code2, 1)) {
      $error_message = t('mfa.invalid_code');
    }
    else {
      // Generate backup codes
      $backup_codes = totp_generate_backup_codes(10, 8);

      // Save to LDAP
      if (totp_set_secret($ldap_connection, $user_dn, $secret, $backup_codes)) {
        // Audit log successful MFA enrolment
        audit_log('mfa_enrolled', $USER_ID, 'User enrolled in MFA', 'success', $USER_ID);
        $success = true;
        $totp_status = 'active';
      }
      else {
        $error_message = t('mfa.save_to_ldap_failed');
      }
    }
  }
}

// Handle disable MFA
if (isset($_POST['disable_mfa'])) {
  if ($schema_error) {
    $error_message = t('mfa.unavailable_body');
  }
  elseif (totp_disable($ldap_connection, $user_dn)) {
    // Audit log MFA disabled
    audit_log('mfa_disabled', $USER_ID, 'User disabled MFA', 'success', $USER_ID);
    $success_disable = true;
    $totp_status = 'disabled';
  }
  else {
    $error_message = t('mfa.disable_failed');
  }
}

// Handle new enrolment request
if (isset($_POST['start_enrolment'])) {
  $enrolling = true;
  $new_secret = totp_generate_secret();
  $qr_url = totp_get_qr_code_url($new_secret, $USER_ID, $MFA_TOTP_ISSUER);
  $qr_image_url = totp_get_qr_code_image_url($qr_url);
}

render_header(t('mfa.title'));

?>

<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-8">

      <?php if (isset($_GET['mfa_required'])) { ?>
      <div class="alert alert-danger">
        <p><strong><?php echo t('mfa.required_title'); ?></strong></p>
        <p><?php echo t('mfa.required_body'); ?></p>
      </div>
    <?php } ?>

    <?php if ($schema_error) { ?>
      <div class="alert alert-warning">
        <p><strong><?php echo t('mfa.unavailable_title'); ?></strong></p>
        <p><?php echo t('mfa.unavailable_body'); ?></p>
        <p><small><?php echo t('mfa.unavailable_hint'); ?></small></p>
      </div>
    <?php } ?>

    <?php if (isset($error_message)) { ?>
      <div class="alert alert-danger">
        <p class="text-center"><?php echo htmlspecialchars($error_message); ?></p>
      </div>
    <?php } ?>

    <?php if (isset($success)) { ?>
      <div class="card border-success">
        <div class="card-header"><?php echo t('mfa.enabled_title'); ?></div>
        <div class="card-body">
          <p><strong><?php echo t('mfa.enabled_body'); ?></strong></p>
          <p><?php echo t('mfa.enabled_backup_hint'); ?></p>

          <div class="well">
            <?php foreach (totp_format_backup_codes($backup_codes) as $code) { ?>
              <code><?php echo htmlspecialchars(trim($code)); ?></code><br>
            <?php } ?>
          </div>

          <p class="text-center">
            <a href="<?php echo url('/home'); ?>" class="btn btn-primary"><?php echo t('mfa.return_home'); ?></a>
          </p>
        </div>
      </div>
      <?php render_footer(); exit(0); ?>
    <?php } ?>

    <?php if (isset($success_disable)) { ?>
      <div class="card border-success">
        <div class="card-header"><?php echo t('mfa.disabled_title'); ?></div>
        <div class="card-body">
          <p><?php echo t('mfa.disabled_body'); ?></p>
          <p class="text-center">
            <a href="<?php echo url('/home'); ?>" class="btn btn-primary"><?php echo t('mfa.return_home'); ?></a>
          </p>
        </div>
      </div>
      <?php render_footer(); exit(0); ?>
    <?php } ?>

    <div class="card">
      <div class="card-header">
        <h4 class="card-title"><?php echo t('mfa.setting_title'); ?></h4>
      </div>
      <div class="card-body">

        <table class="table">
          <tr>
            <th style="width: 200px;"><?php echo t('mfa.status'); ?></th>
            <td>
              <?php
                switch ($totp_status) {
                  case 'active':
                    echo '<span class="badge bg-success">' . t('mfa.active') . '</span>';
                    break;
                  case 'pending':
                    echo '<span class="badge bg-warning text-dark">' . t('mfa.pending') . '</span>';
                    break;
                  case 'disabled':
                    echo '<span class="badge bg-secondary">' . t('mfa.disabled') . '</span>';
                    break;
                  default:
                    echo '<span class="badge bg-secondary">' . t('mfa.not_configured') . '</span>';
                }
              ?>
            </td>
          </tr>

          <?php if ($user_requires_mfa) { ?>
            <tr>
              <th><?php echo t('mfa.required'); ?></th>
              <td><span class="badge bg-info text-dark"><?php echo t('label.yes'); ?></span> (<?php echo t('mfa.yes_group'); ?>)</td>
            </tr>
          <?php } ?>

          <?php if ($totp_status == 'active' && $backup_code_count > 0) { ?>
            <tr>
              <th><?php echo t('mfa.backup_codes'); ?></th>
              <td>
                <span class="label <?php echo $backup_code_count < 3 ? 'label-warning' : 'label-info'; ?>">
                  <?php echo t('mfa.remaining', array('count' => $backup_code_count)); ?>
                </span>
                <?php if ($backup_code_count < 3) { ?>
                  <br><small class="text-warning"><?php echo t('mfa.low_codes'); ?></small>
                <?php } ?>
              </td>
            </tr>
          <?php } ?>

          <?php if ($grace_period_remaining !== null) { ?>
            <tr>
              <th><?php echo t('mfa.grace_period'); ?></th>
              <td>
                <?php if ($grace_period_remaining > 0) { ?>
                  <span class="badge bg-warning text-dark"><?php echo t('mfa.days_remaining', array('days' => $grace_period_remaining)); ?></span>
                  <br><small><?php echo t('mfa.must_setup_days', array('days' => $grace_period_remaining)); ?></small>
                <?php } else { ?>
                  <span class="badge bg-danger"><?php echo t('mfa.grace_expired'); ?></span>
                  <br><small><?php echo t('mfa.grace_expired_hint'); ?></small>
                <?php } ?>
              </td>
            </tr>
          <?php } ?>
        </table>

        <?php if ($totp_status == 'active') { ?>
          <div class="alert alert-info">
            <strong><?php echo t('mfa.enabled_info_title'); ?></strong>
            <p><?php echo t('mfa.enabled_info_body1'); ?></p>
            <p><?php echo t('mfa.enabled_info_body2'); ?></p>
          </div>

          <form method="POST">
            <div class="text-center">
              <button type="submit" name="disable_mfa" class="btn btn-danger" <?php if ($schema_error) echo 'disabled title="' . t('mfa.schema_not_available') . '"'; ?> onclick="return confirm(<?php echo json_encode(t('mfa.disable_confirm')); ?>);">
                <?php echo t('mfa.disable'); ?>
              </button>
            </div>
          </form>

        <?php } elseif (isset($enrolling)) { ?>

          <div class="card border-info">
            <div class="card-header"><?php echo t('mfa.enrol_title'); ?></div>
            <div class="card-body">

              <h4><?php echo t('mfa.step1'); ?></h4>
              <p><?php echo t('mfa.step1_body'); ?></p>

              <div class="text-center">
                <div id="qrcode" style="display: inline-block;"></div>
              </div>

              <p class="text-center"><small><?php echo t('mfa.manual_secret'); ?> <code><?php echo htmlspecialchars($new_secret); ?></code></small></p>

              <script src="<?php echo url('/js/qrcode.min.js'); ?>"></script>
              <script>
                new QRCode(document.getElementById("qrcode"), {
                  text: "<?php echo htmlspecialchars($qr_image_url, ENT_QUOTES); ?>",
                  width: 200,
                  height: 200
                });
              </script>

              <hr>

              <h4><?php echo t('mfa.step2'); ?></h4>
              <p id="step-instruction"><?php echo t('mfa.step2_body'); ?></p>

              <form method="POST" id="mfa-verification-form">
                <input type="hidden" name="secret" id="secret" value="<?php echo htmlspecialchars($new_secret); ?>">
                <input type="hidden" name="code1" id="code1-hidden">
                <input type="hidden" name="code2" id="code2-hidden">
                <input type="hidden" name="time_window1" id="time-window1">
                
                <div class="row align-items-center mb-3" id="code-input-group">
                  <div class="col-auto">
                    <label for="code-input" class="col-form-label mb-0"><?php echo t('mfa.first_code'); ?></label>
                  </div>
                  <div class="col-auto">
                    <input
                      type="text"
                      class="form-control text-center"
                      id="code-input"
                      pattern="[0-9]{6}"
                      maxlength="6"
                      inputmode="numeric"
                      required
                      autofocus
                      autocomplete="off"
                      style="width: 8ch"
                      aria-describedby="code-help"
                    >
                  </div>
                  <div class="w-100"></div>
                  <div class="col">
                    <small class="form-text text-muted" id="code-help">
                      <?php echo t('mfa.first_code_help'); ?>
                    </small>
                  </div>
                </div>

                <div class="row align-items-center mb-3" id="next-code-input-group" style="display: none;">
                  <div class="col-auto">
                    <label for="next-code-input" class="col-form-label mb-0"><?php echo t('mfa.second_code'); ?></label>
                  </div>
                  <div class="col-auto">
                    <input
                      type="text"
                      class="form-control text-center"
                      id="next-code-input"
                      pattern="[0-9]{6}"
                      maxlength="6"
                      inputmode="numeric"
                      autocomplete="off"
                      required
                      style="width: 8ch;"
                      aria-describedby="next-code-help"
                    >
                  </div>
                  <div class="w-100"></div>
                  <div class="col">
                    <small class="form-text text-muted" id="next-code-help">
                      <?php echo t('mfa.second_code_help'); ?>
                    </small>
                  </div>
                </div>

                <div id="error-message" style="display: none;" class="alert alert-danger"></div>

                <div class="text-center">
                  <button type="button" id="verify-button" class="btn btn-primary btn-lg">
                    <?php echo t('mfa.verify_first'); ?>
                  </button>
                </div>
                <div class="text-center">
                  <button type="submit" name="enrol_mfa" id="complete-button" class="btn btn-success btn-lg" style="display: none;">
                    <?php echo t('mfa.complete_setup'); ?>
                  </button>
                </div>
              </form>

              <script>
                (function() {
                  let step = 1;
                  let timeWindow1 = null;

                  function handleVerifyClick(e) {
                    e.preventDefault();

                    if (step === 1) {
                      const codeInput = document.getElementById('code-input');
                      const code = codeInput.value.trim();

                      if (code.length !== 6 || !/^[0-9]{6}$/.test(code)) {
                        showError(<?php echo json_encode(t('mfa.invalid_code')); ?>);
                        return;
                      }

                      verifyFirstCode(code);
                    } else if (step === 2) {
                      const codeInput = document.getElementById('next-code-input');
                      const code = codeInput.value.trim();

                      if (code.length !== 6 || !/^[0-9]{6}$/.test(code)) {
                        showError(<?php echo json_encode(t('mfa.invalid_code')); ?>);
                        return;
                      }

                      verifySecondCode(code);
                    }
                  }

                  function verifyFirstCode(code) {
                    const secret = document.getElementById('secret').value;
                    const button = document.getElementById('verify-button');

                    button.disabled = true;
                    button.textContent = <?php echo json_encode(t('mfa.verifying')); ?>;

                    fetch(window.location.href, {
                      method: 'POST',
                      headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                      },
                      body: 'validate_first_code=1&secret=' + encodeURIComponent(secret) + '&code=' + encodeURIComponent(code)
                    })
                    .then(response => response.json())
                    .then(data => {
                      if (data.valid) {
                        document.getElementById('code1-hidden').value = code;
                        document.getElementById('time-window1').value = data.time_window;
                        timeWindow1 = data.time_window;

                        // Hide first input, show second input
                        document.getElementById('code-input-group').style.display = 'none';
                        document.getElementById('next-code-input-group').style.display = 'flex';
                        document.getElementById('step-instruction').textContent = <?php echo json_encode(t('mfa.wait_next_code')); ?>;

                        // Focus on second input
                        document.getElementById('next-code-input').focus();

                        button.textContent = <?php echo json_encode(t('mfa.verify_second')); ?>;
                        button.disabled = false;

                        step = 2;
                        hideError();
                      } else {
                        showError(data.message);
                        button.disabled = false;
                        button.textContent = <?php echo json_encode(t('mfa.verify_first')); ?>;
                      }
                    })
                    .catch(error => {
                      showError(<?php echo json_encode(t('mfa.generic_error')); ?>);
                      button.disabled = false;
                      button.textContent = <?php echo json_encode(t('mfa.verify_first')); ?>;
                    });
                  }

                  function verifySecondCode(code) {
                    document.getElementById('code2-hidden').value = code;
                    document.getElementById('verify-button').style.display = 'none';
                    document.getElementById('complete-button').style.display = 'block';
                    document.getElementById('next-code-input-group').style.display = 'none';

                    const finalMessage = document.createElement('div');
                    finalMessage.className = 'alert alert-success';
                    finalMessage.innerHTML = '<strong>' + <?php echo json_encode(t('mfa.both_verified')); ?> + '</strong><br>' + <?php echo json_encode(t('mfa.click_complete')); ?>;
                    document.getElementById('mfa-verification-form').insertBefore(finalMessage, document.getElementById('complete-button'));
                  }

                  document.getElementById('verify-button').addEventListener('click', handleVerifyClick);

                  document.getElementById('code-input').addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                      e.preventDefault();
                      handleVerifyClick(e);
                    }
                  });

                  document.getElementById('next-code-input').addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                      e.preventDefault();
                      handleVerifyClick(e);
                    }
                  });

                  function showError(message) {
                    const errorDiv = document.getElementById('error-message');
                    errorDiv.textContent = message;
                    errorDiv.style.display = 'block';
                  }

                  function hideError() {
                    document.getElementById('error-message').style.display = 'none';
                  }
                })();
              </script>

            </div>
          </div>

        <?php } else { ?>

          <?php if ($user_requires_mfa && $grace_period_remaining !== null && $grace_period_remaining <= 0) { ?>
            <div class="alert alert-danger">
              <strong><?php echo t('mfa.action_required_title'); ?></strong>
              <p><?php echo t('mfa.action_required_expired'); ?></p>
            </div>
          <?php } elseif ($user_requires_mfa && $grace_period_remaining !== null) { ?>
            <div class="alert alert-warning">
              <strong><?php echo t('mfa.action_required_title'); ?></strong>
              <p><?php echo t('mfa.action_required_days', array('days' => $grace_period_remaining)); ?></p>
            </div>
          <?php } ?>

          <p><?php echo t('mfa.intro'); ?></p>

          <h4><?php echo t('mfa.how_works'); ?></h4>
          <ol>
            <li><?php echo t('mfa.how_1'); ?></li>
            <li><?php echo t('mfa.how_2'); ?></li>
            <li><?php echo t('mfa.how_3'); ?></li>
          </ol>

          <form method="POST">
            <div class="text-center">
              <button type="submit" name="start_enrolment" class="btn btn-primary btn-lg"
                <?php if ($schema_error) echo 'disabled title="MFA schema not available"'; ?>>
                <?php echo t('mfa.setup_button'); ?>
              </button>
            </div>

        <?php } ?>

      </div>
    </div>

    </div>
  </div>
</div>

<?php
render_footer();
?>
