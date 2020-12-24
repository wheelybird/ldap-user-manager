<?php

set_include_path( ".:" . __DIR__ . "/../includes/");

include_once "web_functions.inc.php";
include_once "ldap_functions.inc.php";
include_once "module_functions.inc.php";


render_header("LDAP manager");
render_submenu();

$ldap_connection = open_ldap_connection();


print "<pre>";
print $schema_base_dn . "\n";
print_r($gom_results);
print "\n\n\n\n";
print_r($gom_r_search);
print "</pre>";



render_footer();
?>
