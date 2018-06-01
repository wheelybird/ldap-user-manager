<?php

 #Modules and how they can be accessed.
 
 #access:
 #user = need to be logged-in to see it
 #hidden_on_login = only visible when not logged in
 #admin = need to be logged in as an admin to see it
 
 $MODULES = array(  
                    'log_in'          => 'hidden_on_login',
                    'change_password' => 'auth',
                    'ldap_manager'    => 'admin',
                    'log_out'         => 'auth'
                  );

?>
