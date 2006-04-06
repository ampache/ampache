<?
  Copyright 2006 Kevin Riker
  All Rights Reserved 
  
$url = "query.php";
$get = "search=hello";

require_once('ajax.js');

echo "<script type=\"text/javascript\">
		javascript:makeRequest(\"$url\",\"$get\");
</script>";
?>
