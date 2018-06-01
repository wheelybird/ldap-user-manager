<?php

include_once("web_functions.inc.php");
render_header($WEBSITE_NAME);

 if (isset($_GET['logged_out'])) {
 ?>
 <div class="alert alert-warning">
 <p class="text-center">You've been automatically logged out because you've been inactive for over
 <?php print $LOGIN_TIMEOUT_MINS; ?> minutes. Click on the 'Log in' link to get back into the system.</p>
 </div>
 <?php
 }

 if (isset($_GET['logged_in'])) {
 ?>
 <div class="alert alert-success">
 <p class="text-center">You're logged in. Select from the menu above.</p>
 </div>
 <?php
 }

 if (isset($_GET['unauthorised'])) {
 ?>
 <div class="alert alert-danger">
 <p class="text-center">You don't have the necessary permissions needed to use this module.</p>
 </div>
 <?php
 }

render_footer();
?>
