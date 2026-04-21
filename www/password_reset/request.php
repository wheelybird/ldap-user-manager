<?php

set_include_path( ".:" . __DIR__ . "/../includes/");

include_once "web_functions.inc.php";
include_once "ldap_functions.inc.php";
include_once "password_reset_functions.inc.php";
include_once "audit_functions.inc.php";
include_once "mail_functions.inc.php";

// No authentication required for this page (public access)

// Check if password reset feature is enabled
if ($PASSWORD_RESET_ENABLED != TRUE) {
  header("Location: " . url('/log_in'));
  exit(0);
}

// Check if email sending is enabled
if ($EMAIL_SENDING_ENABLED != TRUE) {
  die(t('password_reset.email_not_configured'));
}

$request_sent = FALSE;
$rate_limited = FALSE;
$username_input = '';

if (isset($_POST['request_reset'])) {

  $username_input = trim($_POST['username']);

  // Validate username format (alphanumeric, dashes, underscores, dots)
  if (empty($username_input) || !preg_match('/^[a-zA-Z0-9._-]+$/', $username_input)) {
    $invalid_username = TRUE;
  }
  else {
    $ldap_connection = open_ldap_connection();

    // Check rate limiting FIRST (before revealing whether username exists)
    if (!password_reset_check_rate_limit($ldap_connection, $username_input)) {
      $rate_limited = TRUE;

      // Audit log rate limit event
      audit_log('password_reset_rate_limited', $username_input, 'IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 'failure');
    }
    else {
      // Increment rate limit counter
      password_reset_increment_rate_limit($ldap_connection, $username_input);

      // Look up user by username
      $user_search = ldap_search($ldap_connection, $LDAP['user_dn'],
        "({$LDAP['account_attribute']}=" . ldap_escape($username_input, "", LDAP_ESCAPE_FILTER) . ")",
        array('mail', 'givenname', 'sn'));

      if ($user_search) {
        $user_entry = ldap_get_entries($ldap_connection, $user_search);

        if ($user_entry['count'] > 0 && isset($user_entry[0]['mail'][0])) {
          $username = $username_input;
          $user_mail = $user_entry[0]['mail'][0];
          $user_givenname = isset($user_entry[0]['givenname'][0]) ? $user_entry[0]['givenname'][0] : '';
          $user_sn = isset($user_entry[0]['sn'][0]) ? $user_entry[0]['sn'][0] : '';
        } else {
          $username = FALSE;
        }
      } else {
        $username = FALSE;
      }

      if ($username !== FALSE) {

        // Generate token
        $token = password_reset_generate_token($ldap_connection, $username, $user_mail);

        if ($token !== FALSE) {
          // Build reset URL
          $reset_url = "{$SITE_PROTOCOL}{$SERVER_HOSTNAME}{$SERVER_PATH}password_reset/reset.php?token={$token}&user={$username}";

          // Get token expiry in minutes
          $expiry_minutes = isset($PASSWORD_RESET_TOKEN_EXPIRY_MINUTES) ? $PASSWORD_RESET_TOKEN_EXPIRY_MINUTES : 60;

          // Parse email template
          $mail_body = parse_mail_text(
            $password_reset_request_mail_body,
            '', // No password
            $username,
            $user_givenname,
            $user_sn,
            null, // No timestamp
            null, // No IP
            $ADMIN_EMAIL,
            $reset_url,
            $expiry_minutes
          );

          $mail_subject = parse_mail_text(
            $password_reset_request_mail_subject,
            '',
            $username,
            $user_givenname,
            $user_sn,
            null,
            null,
            $ADMIN_EMAIL,
            $reset_url,
            $expiry_minutes
          );

          $full_name = trim($user_givenname . " " . $user_sn);

          // Send email
          $sent = send_email($user_mail, $full_name, $mail_subject, $mail_body);

          if ($sent) {
            // Audit log successful request
            audit_log('password_reset_requested', $username, 'Email: ' . $user_mail . ', IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 'success');
          }
          else {
            error_log("$log_prefix Password reset: Failed to send email to $user_mail");
            // Audit log email failure
            audit_log('password_reset_email_failed', $username, 'Email: ' . $user_mail, 'failure');
          }
        }
        else {
          error_log("$log_prefix Password reset: Failed to generate token for $username");
          // Audit log token generation failure
          audit_log('password_reset_token_failed', $username, 'Failed to generate token', 'failure');
        }
      }
      else {
        // User not found - don't reveal this to prevent enumeration
        // Audit log with hashed username to avoid storing real usernames
        $username_hash = hash('sha256', strtolower($username_input));
        audit_log('password_reset_unknown_username', $username_hash, 'IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 'failure');
      }

      // Always show success message (don't reveal if username exists)
      $request_sent = TRUE;
    }

    ldap_close($ldap_connection);
  }
}

render_header(t('password_reset.page_title', array('org' => $ORGANISATION_NAME)));

?>
<div class="container">

<?php

if ($rate_limited) {  ?>
 <div class="alert alert-warning">
  <p class="text-center"><strong><?php echo t('password_reset.request.rate_limited_title'); ?></strong></p>
  <p class="text-center"><?php echo t('password_reset.request.rate_limited_body'); ?></p>
 </div>
<?php }

if (isset($invalid_username)) {  ?>
 <div class="alert alert-warning">
  <p class="text-center"><?php echo t('password_reset.request.invalid_username'); ?></p>
 </div>
<?php }

if ($request_sent) {  ?>
 <div class="alert alert-success">
  <p class="text-center"><strong><?php echo t('password_reset.request.sent_title'); ?></strong></p>
  <p class="text-center"><?php echo t('password_reset.request.sent_body'); ?></p>
  <p class="text-center"><?php echo t('password_reset.request.sent_instructions'); ?></p>
  <p class="text-center"><small><?php echo t('password_reset.request.sent_expiry', array('minutes' => isset($PASSWORD_RESET_TOKEN_EXPIRY_MINUTES) ? $PASSWORD_RESET_TOKEN_EXPIRY_MINUTES : 60)); ?></small></p>
 </div>
<?php } else { ?>

 <div class="row justify-content-center">
  <div class="col-md-8">

   <div class="card">
    <div class="card-header text-center"><?php echo t('password_reset.request.card_header'); ?></div>

    <ul class="list-group">
     <li class="list-group-item"><?php echo t('password_reset.request.instruction', array('minutes' => isset($PASSWORD_RESET_TOKEN_EXPIRY_MINUTES) ? $PASSWORD_RESET_TOKEN_EXPIRY_MINUTES : 60)); ?></li>
    </ul>

    <div class="card-body text-center">

     <form class="form-horizontal" action='' method='post'>

      <input type='hidden' name="request_reset" value="1">

      <div class="row mb-3">
       <label for="username" class="col-sm-3 col-form-label text-end"><?php echo t('password_reset.request.username_label'); ?></label>
       <div class="col-sm-6">
        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username_input); ?>" required autocomplete="username">
       </div>
      </div>

      <div class="text-center mb-3">
        <button type="submit" class="btn btn-secondary"><?php echo t('password_reset.request.submit'); ?></button>
      </div>

     </form>

     <div class="text-center mt-3">
       <a href="<?php echo url('/log_in'); ?>"><?php echo t('password_reset.request.back_to_login'); ?></a>
     </div>

    </div>
   </div>

  </div>
 </div>

<?php } ?>

</div>
<?php

render_footer();

?>
