<?php

include_once("web_functions.inc.php");
include_once("ldap_functions.inc.php");
include_once("module_functions.inc.php");
set_page_access("admin");

render_header();
render_submenu();

$invalid_password = FALSE;
$mismatched_passwords = FALSE;
$invalid_username = FALSE;
$weak_password = FALSE;

$attribute_map = array( "givenname"      => "First name",
                        "sn"             => "Last name",
                        "uidnumber"      => "UID",
                        "gidnumber"      => "GID",
                        "loginshell"     => "Login shell",
                        "homedirectory"  => "Home directory"
                       );


$ldap_connection = open_ldap_connection();


if (!isset($_POST['username']) and !isset($_GET['username'])) {
?>
 <div class="alert alert-danger">
  <p class="text-center">The username is missing.</p>
 </div>
<?php
render_footer();
exit(0);
}
else {
 $username =  (isset($_POST['username']) ? $_POST['username'] : $_GET['username']);
}

if (!preg_match("/$USERNAME_REGEX/",$username)) {
?>
 <div class="alert alert-danger">
  <p class="text-center">The username is invalid.</p>
 </div>
<?php
render_footer();
exit(0);
}


$ldap_search = ldap_search( $ldap_connection, $LDAP['base_dn'], "(${LDAP['account_attribute']}=$username)" );

 
if ($ldap_search) {
 
 $user = ldap_get_entries($ldap_connection, $ldap_search);


 ################################################
 
 ### Check for updates

 if (isset($_POST['update_account'])) {

  $to_update = array();
  
  foreach ($attribute_map as $key => $value) {

   if ($user[0][$key][0] != $_POST[$key]) {
    $to_update[$key] = $_POST[$key];
    $user[0][$key][0] = $_POST[$key];
   }
  
  }

  if (isset($_POST['password']) and $_POST['password'] != "") {
    
    $password = $_POST['password'];
    
    if (!is_numeric($_POST['pass_score']) or $_POST['pass_score'] < 3) { $weak_password = TRUE; }
    if (preg_match("/\"|'/",$password)) { $invalid_password = TRUE; }
    if ($_POST['password'] != $_POST['password_match']) { $mismatched_passwords = TRUE; }
    if (!preg_match("/$USERNAME_REGEX/",$username)) { $invalid_username = TRUE; }

   if ( !$mismatched_passwords
       and !$weak_password
       and !$invalid_password
                             ) {
    $to_update['userpassword'] = ldap_hashed_password($password);
   }
  }


  $updated_account = ldap_mod_replace($ldap_connection, $user[0]['dn'] , $to_update);

  if ($updated_account) {
   ?>
   <script>
     window.setTimeout(function() {
                                   $(".alert").fadeTo(500, 0).slideUp(500, function(){ $(this).remove(); });
                                  }, 4000);
   </script>
   <div class="alert alert-success" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="TRUE">&times;</span></button>
    <strong>Success!</strong> The group has been updated.
   </div>
  <?php
  }
  else {
   ?>
   <script>
     window.setTimeout(function() {
                                   $(".alert").fadeTo(500, 0).slideUp(500, function(){ $(this).remove(); });
                                  }, 4000);
   </script>
   <div class="alert alert-success" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="TRUE">&times;</span></button>
    <strong>Success!</strong> The group has been updated.
   </div>
  <?php
  }
 }


 if ($weak_password) { ?>
 <div class="alert alert-warning">
  <p class="text-center">The password wasn't strong enough.</p>
 </div>
 <?php }

 if ($invalid_password) {  ?>
 <div class="alert alert-warning">
  <p class="text-center">The password contained invalid characters.</p>
 </div>
 <?php }

 if ($mismatched_passwords) {  ?>
 <div class="alert alert-warning">
  <p class="text-center">The passwords didn't match.</p>
 </div>
 <?php }


 ################################################


 $all_groups = ldap_get_group_list($ldap_connection);
 
 $currently_member_of = array();
 
 foreach ($all_groups as $this_group) {
  if (ldap_is_group_member($ldap_connection,$this_group,$username)) {
   array_push($currently_member_of,$this_group);
  }
 }
 
 $not_member_of = array_diff($all_groups,$currently_member_of);


 #########  Add/remove from groups

 if (isset($_POST["update_member_of"])) {

  $updated_group_membership = array();

  foreach ($_POST as $index => $group) {
   if (is_numeric($index) and preg_match("/$USERNAME_REGEX/",$group)) {
    array_push($updated_group_membership,$group);
   }
  }

  $groups_to_add = array_diff($updated_group_membership,$currently_member_of);
  $groups_to_del = array_diff($currently_member_of,$updated_group_membership);


  foreach ($groups_to_del as $this_group) {
   ldap_delete_member_from_group($ldap_connection,$this_group,$username);
  }
  foreach ($groups_to_add as $this_group) {
   ldap_add_member_to_group($ldap_connection,$this_group,$username);
  }

  $not_member_of = array_diff($all_groups,$updated_group_membership);
  $member_of = $updated_group_membership;

  ?>
   <script>
     window.setTimeout(function() {
                                   $(".alert").fadeTo(500, 0).slideUp(500, function(){ $(this).remove(); });
                                  }, 4000);
   </script>
   <div class="alert alert-success" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="TRUE">&times;</span></button>
    <strong>Success!</strong> The group has been updated.
   </div>

  <?php
 
 }
 else {
  $member_of = $currently_member_of;
 }

 ################


?>
<script src="//cdnjs.cloudflare.com/ajax/libs/zxcvbn/1.0/zxcvbn.min.js"></script>
<script type="text/javascript" src="/js/zxcvbn-bootstrap-strength-meter.js"></script>
<script type="text/javascript">
 $(document).ready(function(){	
   $("#StrengthProgressBar").zxcvbnProgressBar({ passwordInput: "#password" });
 });
</script>
<script type="text/javascript" src="/js/generate_passphrase.js"></script>
<script type="text/javascript" src="/js/wordlist.js"></script>
<script>
 
 function show_delete_user_button() {

  group_del_submit = document.getElementById('delete_user');
  group_del_submit.classList.replace('invisible','visible');
  
  
 }
 
 function check_passwords_match() {

   if (document.getElementById('password').value != document.getElementById('confirm').value ) {
       document.getElementById('password_div').classList.add("has-error");
       document.getElementById('confirm_div').classList.add("has-error");
   }
   else {
    document.getElementById('password_div').classList.remove("has-error");
    document.getElementById('confirm_div').classList.remove("has-error");
   }
  }

 function random_password() {
  
  generatePassword(4,'-','password','confirm');
  $("#StrengthProgressBar").zxcvbnProgressBar({ passwordInput: "#password" });
 }
 
 function back_to_hidden(passwordField,confirmField) {

  var passwordField = document.getElementById(passwordField).type = 'password';
  var confirmField = document.getElementById(confirmField).type = 'password';

 }
 
 function update_form_with_groups() {
  
  var group_form = document.getElementById('update_with_groups');
  var group_list_ul = document.getElementById('member_of_list');
  
  var group_list = group_list_ul.getElementsByTagName("li");

  for (var i = 0; i < group_list.length; ++i) {
    var hidden = document.createElement("input");
        hidden.type = "hidden";
        hidden.name = i;
        hidden.value = group_list[i]['textContent'];
        group_form.appendChild(hidden);
  
  }
  
  group_form.submit();
 
 }

 $(function () {

    $('body').on('click', '.list-group .list-group-item', function () {
        $(this).toggleClass('active');
    });
    $('.list-arrows button').click(function () {
        var $button = $(this), actives = '';
        if ($button.hasClass('move-left')) {
            actives = $('.list-right ul li.active');
            actives.clone().appendTo('.list-left ul');
            $('.list-left ul li.active').removeClass('active');
            actives.remove();
        } else if ($button.hasClass('move-right')) {
            actives = $('.list-left ul li.active');
            actives.clone().appendTo('.list-right ul');
            $('.list-right ul li.active').removeClass('active');
            actives.remove();
        }
        $("#submit_members").prop("disabled", false);
    });
    $('.dual-list .selector').click(function () {
        var $checkBox = $(this);
        if (!$checkBox.hasClass('selected')) {
            $checkBox.addClass('selected').closest('.well').find('ul li:not(.active)').addClass('active');
            $checkBox.children('i').removeClass('glyphicon-unchecked').addClass('glyphicon-check');
        } else {
            $checkBox.removeClass('selected').closest('.well').find('ul li.active').removeClass('active');
            $checkBox.children('i').removeClass('glyphicon-check').addClass('glyphicon-unchecked');
        }
    });
    $('[name="SearchDualList"]').keyup(function (e) {
        var code = e.keyCode || e.which;
        if (code == '9') return;
        if (code == '27') $(this).val(null);
        var $rows = $(this).closest('.dual-list').find('.list-group li');
        var val = $.trim($(this).val()).replace(/ +/g, ' ').toLowerCase();
        $rows.show().filter(function () {
            var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();
            return !~text.indexOf(val);
        }).hide();
    });

 });


</script>
     
<div class="container">
 <div class="col-sm-7">

  <div class="panel panel-default">
    <div class="panel-heading clearfix">
     <h3 class="panel-title pull-left" style="padding-top: 7.5px;"><?php print $user[0]['uid'][0]; ?></h3>
     <button class="btn btn-warning pull-right" onclick="show_delete_user_button();">Delete account</button>
     <form action="/<?php print $THIS_MODULE_PATH; ?>/index.php" method="post"><input type="hidden" name="delete_user" value="<?php print $username; ?>"><button class="btn btn-danger pull-right invisible" id="delete_user">Confirm deletion</button></form>
    </div>
    <div class="panel-body">
     <form class="form-horizontal" action="" method="post">

      <input type="hidden" name="update_account">
      <input type="hidden" id="pass_score" value="0" name="pass_score">
      <input type="hidden" name="username" value="<?php print $username; ?>">
     

<?php

  foreach ($attribute_map as $key => $value) {
  ?>
      <div class="form-group">
       <label for="<?php print $key; ?>" class="col-sm-3 control-label"><?php print $value; ?></label>
       <div class="col-sm-6">
        <input type="text" class="form-control" id="<?php print $key; ?>" name="<?php print $key; ?>" value="<?php print $user[0][$key][0]; ?>">
       </div>
      </div>
  <?php
  }
?>

     <div class="form-group" id="password_div">
      <label for="password" class="col-sm-3 control-label">Password</label>
      <div class="col-sm-6">
       <input type="password" class="form-control" id="password" name="password" onkeyup="back_to_hidden('password','confirm');">
      </div>
      <div class="col-sm-1">
       <input type="button" class="btn btn-sm" id="password_generator" onclick="random_password();" value="Generate password">
      </div>
     </div>

     <div class="form-group" id="confirm_div">
      <label for="confirm" class="col-sm-3 control-label">Confirm</label>
      <div class="col-sm-6">
       <input type="password" class="form-control" id="confirm" name="password_match" onkeyup="check_passwords_match()">
      </div>
     </div>

     <div class="form-group">
       <p align='center'><button type="submit" class="btn btn-default">Update account details</button></p>
     </div>

    </form>

    <div class="progress">
     <div id="StrengthProgressBar" class="progress-bar"></div>
    </div>

   </div>
  </div>

 </div>
</div>

<div class="container">
 <div class="col-sm-12">
 
  <div class="panel panel-default">
   <div class="panel-heading clearfix">
    <h3 class="panel-title pull-left" style="padding-top: 7.5px;">Group membership</h3>
   </div>
   <div class="panel-body">

    <div class="row">

         <div class="dual-list list-left col-md-5">
          <strong>Member of</strong>
          <div class="well">
           <div class="row">
            <div class="col-md-10">
             <div class="input-group">
              <span class="input-group-addon glyphicon glyphicon-search"></span>
              <input type="text" name="SearchDualList" class="form-control" placeholder="search" />
             </div>
            </div>
            <div class="col-md-2">
             <div class="btn-group">
              <a class="btn btn-default selector" title="select all"><i class="glyphicon glyphicon-unchecked"></i></a>
             </div>
            </div>
           </div>
           <ul class="list-group" id="member_of_list">
            <?php
            foreach ($member_of as $group) {
              print "<li class='list-group-item'>$group</li>\n";
            }
            ?>
           </ul>
          </div>
         </div>

         <div class="list-arrows col-md-1 text-center">
          <button class="btn btn-default btn-sm move-left">
           <span class="glyphicon glyphicon-chevron-left"></span>
          </button>
          <button class="btn btn-default btn-sm move-right">
           <span class="glyphicon glyphicon-chevron-right"></span>
          </button>
          <form id="update_with_groups" action="<?php print $CURRENT_PAGE; ?>" method="post">
           <input type="hidden" name="update_member_of">
           <input type="hidden" name="username" value="<?php print $username; ?>">
          </form>
          <button id="submit_members" class="btn btn-info" disabled type="submit" onclick="update_form_with_groups()">Save</button>
         </div>

         <div class="dual-list list-right col-md-5">
          <strong>Available groups</strong>
          <div class="well">
           <div class="row">
            <div class="col-md-2">
             <div class="btn-group">
              <a class="btn btn-default selector" title="select all"><i class="glyphicon glyphicon-unchecked"></i></a>
             </div>
            </div>
            <div class="col-md-10">
             <div class="input-group">
              <input type="text" name="SearchDualList" class="form-control" placeholder="search" />
              <span class="input-group-addon glyphicon glyphicon-search"></span>
             </div>
            </div>
           </div>
           <ul class="list-group">
            <?php
             foreach ($not_member_of as $group) {
               print "<li class='list-group-item'>$group</li>\n";
             }
            ?>
           </ul>
          </div>
         </div>

   </div>
	</div>
</div>


<?php

}

render_footer();

?>
