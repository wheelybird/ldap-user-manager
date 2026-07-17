<?php

set_include_path( ".:" . __DIR__ . "/../includes/");

include_once "web_functions.inc.php";

// Sessions are now initialized automatically in web_functions.inc.php

render_header("$ORGANISATION_NAME - request an account");

if ($ACCOUNT_REQUESTS_ENABLED == FALSE) {

?>
<div class="container">
 <div class='alert alert-warning'><p class='text-center'>Account requesting is disabled.</p></div>
</div>
<?php

render_footer();
exit(0);

}

if($_POST) {

  $error_messages = array();

  # Fail closed if the CAPTCHA was never generated for this session (an attacker POSTing
  # directly leaves proof_of_humanity unset; strcasecmp("", NULL) returns 0 and would pass).
  # Compare in a timing-safe way and consume the token so it can't be replayed.
  $expected_proof = $_SESSION['proof_of_humanity'] ?? '';
  $supplied_proof = isset($_POST['validate']) ? (string)$_POST['validate'] : '';
  if(empty($expected_proof) or ! hash_equals(strtolower($expected_proof), strtolower($supplied_proof))) {
    array_push($error_messages, "The validation text didn't match the image.");
  }
  unset($_SESSION['proof_of_humanity']);

  if (! isset($_POST['firstname']) or $_POST['firstname'] == "") {
    array_push($error_messages, "You didn't enter your first name.");
  }
  else {
    $firstname=trim($_POST['firstname']);
  }

  if (! isset($_POST['lastname']) or $_POST['lastname'] == "") {
    array_push($error_messages, "You didn't enter your first name.");
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
      The request couldn't be sent because:
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

    # Strip control characters (including CR/LF) from user values placed in the email subject
    # header, as defence-in-depth against header injection. PHPMailer also sanitises headers,
    # but we don't rely on that alone.
    $firstname_s = preg_replace('/[\x00-\x1F\x7F]/', '', $firstname);
    $lastname_s  = preg_replace('/[\x00-\x1F\x7F]/', '', $lastname);
    $mail_subject = "$firstname_s $lastname_s has requested an account for $ORGANISATION_NAME.";

    # URL-encode each value placed into the link so a "&" in user input can't smuggle extra
    # query parameters into the admin's pre-filled new_user.php form.
    $link_url = "{$SITE_PROTOCOL}{$SERVER_HOSTNAME}{$SERVER_PATH}account_manager/new_user.php?account_request" .
                "&first_name=" . rawurlencode($firstname) .
                "&last_name=" . rawurlencode($lastname) .
                "&email=" . rawurlencode($email ?? '');

if (!isset($email)) { $email = "n/a"; }
if (!isset($notes)) { $notes = "n/a"; }

    # HTML-encode every user-supplied value placed into the HTML email body (and the href).
    $firstname_h = htmlspecialchars($firstname, ENT_QUOTES);
    $lastname_h  = htmlspecialchars($lastname, ENT_QUOTES);
    $email_h     = htmlspecialchars($email, ENT_QUOTES);
    $notes_h     = htmlspecialchars($notes, ENT_QUOTES);
    $link_url_h  = htmlspecialchars($link_url, ENT_QUOTES);

    $mail_body = <<<EoT
A request for an $ORGANISATION_NAME account has been sent:
<p>
First name: <b>$firstname_h</b><br>
Last name: <b>$lastname_h</b><br>
Email: <b>$email_h</b><br>
Notes: <pre>$notes_h</pre><br>
<p>
<a href="$link_url_h">Create this account.</a>
EoT;

     include_once "mail_functions.inc.php";
     $sent_email = send_email($ACCOUNT_REQUESTS_EMAIL,"$ORGANISATION_NAME account requests",$mail_subject,$mail_body);
     if ($sent_email) { ?>
       <div class="container">
         <div class="row justify-content-center">
           <div class="col-sm-6">
             <div class="card border-success">
             <div class="card-header">Thank you</div>
             <div class="card-body">
               The request was sent and the administrator will process it as soon as possible.
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
             <div class="card-header">Error</div>
             <div class="card-body">
               Unfortunately the account request wasn't sent because of a technical issue.
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
    Use this form to send a request for an account to an administrator at <?php print $ORGANISATION_NAME; ?>.
    If the administrator approves your request they'll get in touch with you to give you your new credentials.
    </div>
  </div>

  <div class="card"> 
   <div class="card-header text-center">Request an account for <?php print $ORGANISATION_NAME; ?></div>
   <div class="card-body text-center">

   <form class="form-horizontal" action='' method='post'>

    <div class="row mb-3">
     <label for="firstname" class="col-sm-4 col-form-label text-end">First name</label>
     <div class="col-sm-6">
      <input type="text" class="form-control" id="firstname" name="firstname" placeholder="Required" <?php if (isset($firstname)) { print "value='".htmlspecialchars($firstname, ENT_QUOTES)."'"; } ?>>
     </div>
    </div>

    <div class="row mb-3">
     <label for="lastname" class="col-sm-4 col-form-label text-end">Last name</label>
     <div class="col-sm-6">
      <input type="text" class="form-control" id="lastname" name="lastname" placeholder="Required" <?php if (isset($lastname)) { print "value='".htmlspecialchars($lastname, ENT_QUOTES)."'"; } ?>>
     </div>
    </div>

    <div class="row mb-3">
     <label for="email" class="col-sm-4 col-form-label text-end">Email</label>
     <div class="col-sm-6">
      <input type="text" class="form-control" id="email" name="email" <?php if (isset($email)) { print "value='".htmlspecialchars($email, ENT_QUOTES)."'"; } ?>>
     </div>
    </div>

    <div class="row mb-3">
     <label for="Notes" class="col-sm-4 col-form-label text-end">Notes</label>
     <div class="col-sm-6">
      <textarea class="form-control" id="notes" name="notes" placeholder="Enter any extra information you think the administrator might need to know."><?php if (isset($notes)) { print htmlspecialchars($notes, ENT_QUOTES); } ?></textarea>
     </div>
    </div>

    <div class="row mb-3">
     <label for="validate" class="col-sm-4 col-form-label text-end">Validation</label>
     <div class="col-sm-6">
      <span class="center-block">
        <img src="human.php" class="human-check" alt="Non-human detection">
        <button type="button" class="btn btn-secondary btn-sm" onclick="document.querySelector('.human-check').src = 'human.php?' + Date.now()">
         <span class="bi bi-arrow-clockwise"></span> Refresh
        </button>
      </span>
      <input type="text" class="form-control center-block" id="validate" name="validate" placeholder="Enter the characters from the image">
     </div>
    </div>

    <div class="text-center mb-3">
     <button type="submit" class="btn btn-secondary">Send request</button>
    </div>
   
   </form>
  </div>
  </div>
 </div>
</div>
<?php
render_footer();
?>
