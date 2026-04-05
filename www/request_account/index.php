<?php

set_include_path( ".:" . __DIR__ . "/../includes/");

include_once "web_functions.inc.php";

// Sessions are now initialized automatically in web_functions.inc.php

render_header($ORGANISATION_NAME . ' ' . t('request_account.title'));

if ($ACCOUNT_REQUESTS_ENABLED == FALSE) {

?>
<div class="container">
 <div class='alert alert-warning'><p class='text-center'><?php echo t('request_account.disabled'); ?></p></div>
</div>
<?php

render_footer();
exit(0);

}

if($_POST) {

  $error_messages = array();

  if(! isset($_POST['validate']) or strcasecmp($_POST['validate'], $_SESSION['proof_of_humanity']) != 0) {
    array_push($error_messages, t('request_account.validation_mismatch'));
  }

  if (! isset($_POST['firstname']) or $_POST['firstname'] == "") {
    array_push($error_messages, t('request_account.missing_firstname'));
  }
  else {
    $firstname=trim($_POST['firstname']);
  }

  if (! isset($_POST['lastname']) or $_POST['lastname'] == "") {
    array_push($error_messages, t('request_account.missing_lastname'));
  }
  else {
    $lastname=trim($_POST['lastname']);
  }

  if (isset($_POST['email']) and $_POST['email'] != "") {
    $email=filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
  }

  if (isset($_POST['notes']) and $_POST['notes'] != "") {
    $notes=trim($_POST['notes']);
  }


  if (count($error_messages) > 0) { ?>
    <div class="container">
     <div class="alert alert-danger" role="alert">
      <?php echo t('request_account.failed_prefix'); ?>
      <p>
      <ul>
        <?php
         foreach($error_messages as $message) {
           print "<li>$message</li>\n";
         }
        ?>
      </ul>
     </div>
    </div>
  <?php
  }
  else {

    $mail_subject = "$firstname $lastname has requested an account for $ORGANISATION_NAME.";

$link_url="{$SITE_PROTOCOL}{$SERVER_HOSTNAME}{$SERVER_PATH}account_manager/new_user.php?account_request&first_name=$firstname&last_name=$lastname&email=$email";

if (!isset($email)) { $email = "n/a"; }
if (!isset($notes)) { $notes = "n/a"; }

    $mail_body = <<<EoT
A request for an $ORGANISATION_NAME account has been sent:
<p>
First name: <b>$firstname</b><br>
Last name: <b>$lastname</b><br>
Email: <b>$email</b><br>
Notes: <pre>$notes</pre><br>
<p>
<a href="$link_url">Create this account.</a>
EoT;

     include_once "mail_functions.inc.php";
     $sent_email = send_email($ACCOUNT_REQUESTS_EMAIL,"$ORGANISATION_NAME account requests",$mail_subject,$mail_body);
     if ($sent_email) { ?>
       <div class="container">
         <div class="row justify-content-center">
           <div class="col-sm-6">
             <div class="card border-success">
             <div class="card-header"><?php echo t('request_account.thank_you'); ?></div>
             <div class="card-body">
               <?php echo t('request_account.sent'); ?>
             </div>
           </div>
           </div>
         </div>
       </div>
     <?php }
     else { ?>
       <div class="container">
         <div class="row justify-content-center">
           <div class="col-sm-6">
             <div class="card border-danger">
             <div class="card-header"><?php echo t('request_account.error'); ?></div>
             <div class="card-body">
               <?php echo t('request_account.technical_issue'); ?>
             </div>
           </div>
           </div>
         </div>
       </div>
    <?php
    }
   render_footer();
   exit(0);

  }
}
?>
<div class="container">
 <div class="row justify-content-center">
  <div class="col-sm-8">

  <div class="card">
    <div class="card-body">
    <?php echo t('request_account.intro', array('org' => $ORGANISATION_NAME)); ?>
    </div>
  </div>

  <div class="card"> 
   <div class="card-header text-center"><?php echo t('request_account.header', array('org' => $ORGANISATION_NAME)); ?></div>
   <div class="card-body text-center">

   <form class="form-horizontal" action='' method='post'>

    <div class="row mb-3">
     <label for="firstname" class="col-sm-4 col-form-label text-end"><?php echo t('request_account.first_name'); ?></label>
     <div class="col-sm-6">
      <input type="text" class="form-control" id="firstname" name="firstname" placeholder="<?php echo t('request_account.required'); ?>" <?php if (isset($firstname)) { print "value='$firstname'"; } ?>>
     </div>
    </div>

    <div class="row mb-3">
     <label for="lastname" class="col-sm-4 col-form-label text-end"><?php echo t('request_account.last_name'); ?></label>
     <div class="col-sm-6">
      <input type="text" class="form-control" id="lastname" name="lastname" placeholder="<?php echo t('request_account.required'); ?>" <?php if (isset($lastname)) { print "value='$lastname'"; } ?>>
     </div>
    </div>

    <div class="row mb-3">
     <label for="email" class="col-sm-4 col-form-label text-end"><?php echo t('request_account.email'); ?></label>
     <div class="col-sm-6">
      <input type="text" class="form-control" id="email" name="email" <?php if (isset($email)) { print "value='$email'"; } ?>>
     </div>
    </div>

    <div class="row mb-3">
     <label for="Notes" class="col-sm-4 col-form-label text-end"><?php echo t('request_account.notes'); ?></label>
     <div class="col-sm-6">
      <textarea class="form-control" id="notes" name="notes" placeholder="<?php echo t('request_account.notes_placeholder'); ?>"><?php if (isset($notes)) { print $notes; } ?></textarea>
     </div>
    </div>

    <div class="row mb-3">
     <label for="validate" class="col-sm-4 col-form-label text-end"><?php echo t('request_account.validation'); ?></label>
     <div class="col-sm-6">
      <span class="center-block">
        <img src="human.php" class="human-check" alt="Non-human detection">
        <button type="button" class="btn btn-secondary btn-sm" onclick="document.querySelector('.human-check').src = 'human.php?' + Date.now()">
         <span class="bi bi-arrow-clockwise"></span> <?php echo t('request_account.refresh'); ?>
        </button>
      </span>
      <input type="text" class="form-control center-block" id="validate" name="validate" placeholder="<?php echo t('request_account.validation_placeholder'); ?>">
     </div>
    </div>

    <div class="text-center mb-3">
     <button type="submit" class="btn btn-secondary"><?php echo t('request_account.submit'); ?></button>
    </div>
   
   </form>
  </div>
  </div>
 </div>
</div>
<?php
render_footer();
?>
