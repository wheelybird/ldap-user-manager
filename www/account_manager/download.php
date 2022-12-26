<?php

set_include_path( ".:" . __DIR__ . "/../includes/");
include_once "web_functions.inc.php";
include_once "ldap_functions.inc.php";
include_once "module_functions.inc.php";
set_page_access("admin");

if (!isset($_GET['resource_identifier']) or !isset($_GET['attribute'])) {
  exit(0);
}
else {
  $this_resource=ldap_escape($_GET['resource_identifier'], "", LDAP_ESCAPE_FILTER);
  $this_attribute=ldap_escape($_GET['attribute'], "", LDAP_ESCAPE_FILTER);
}


$exploded = ldap_explode_dn($this_resource,0);
$filter = $exploded[0];
$ldap_connection = open_ldap_connection();
$ldap_search_query="($filter)";
$ldap_search = ldap_search($ldap_connection, $this_resource, $ldap_search_query,array($this_attribute));

if ($ldap_search) {

  $records = ldap_get_entries($ldap_connection, $ldap_search);
  if ($records['count'] == 1) {
    $this_record = $records[0];
      if (isset($this_record[$this_attribute][0])) {
        header("Content-Type: application/octet-stream");
        header("Cache-Control: no-cache private");
        header("Content-Transfer-Encoding: Binary");
        header("Content-disposition: attachment; filename='{$this_resource}.{$this_attribute}'");
        header("Content-Length: ". strlen($this_record[$this_attribute][0]));
        print $this_record[$this_attribute][0];
      }
  }

}

?>
