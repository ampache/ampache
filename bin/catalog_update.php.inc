<?php 

$no_session='1';
require ("../modules/init.php");

$sql = "SELECT id FROM catalog WHERE catalog_type='local'";
$db_results = mysql_query($sql, dbh());

while ($r = mysql_fetch_row($db_results)) { 
	$catalog = new Catalog($r[0]);

	// Clean out dead files
	$catalog->clean_catalog();

	$catalog->count = 0;

	// Verify Existing
	$catalog->verify_catalog();

	$catalog->count = 0;
	
	echo "\n\n";

	// Look for new files
	$catalog->add_to_catalog();
	echo "\n\n";
}


?>
