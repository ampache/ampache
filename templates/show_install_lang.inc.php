<html>
<head>
<title>Ampache :: For The Love Of Music - Install</title>
</head>
<body>
<script src="lib/general.js" language="javascript" type="text/javascript"></script>
<?php require_once(conf('prefix') . "/templates/install.css"); ?>
<div id="header"> 
<h1><?php echo _('Ampache Installation'); ?></h1>
<p>For the love of Music</p>
</div>
<div id="text-box">
	<div class="notify">
		<b><?php echo _("Requirements"); ?></b>
		<p>
		<?php echo _('This Page handles the installation of the Ampache database and the creation of the ampache.cfg.php file. Before you continue please make sure that you have the following pre-requisites'); ?>
		<br />
		<ul>
			<li><?php echo _('A MySQL Server with a username and password that can create/modify databases'); ?></li>
			<li><?php echo _('Your webserver has read access to the /sql/ampache.sql file and the /config/ampache.cfg.php.dist file'); ?></li>
		</ul>
<?php echo _("Once you have ensured that you have the above requirements please fill out the information below. You will only be asked for the required config values. If you would like to make changes to your ampache install at a later date simply edit /config/ampache.cfg.php"); ?>
		</p>
	</div>
	
	<div class="content">
		<b>Choose installation language.</b>
		<p>
		<?php echo $GLOBALS['error']->print_error('general'); ?>

<form method="post" action="<?php echo $http_type . $_SERVER['HTTP_HOST'] .  $_SERVER['PHP_SELF'] . "?action=init"; ?>" enctype="multipart/form-data" >

<?
$languages = get_languages();
$var_name = $value . "_lang";
${$var_name} = "selected=\"selected\"";

echo "<select name=\"htmllang\">\n";

foreach ($languages as $lang=>$name) {
	$var_name = $lang . "_lang";

	echo "\t<option value=\"$lang\" " . ${$var_name} . ">$name</option>\n";
} // end foreach
echo "</select>\n";
?>

<input type="submit" value="<?php echo _('Start configuration'); ?>">

	</form>
 </p>
	</div>
	<div id="bottom">
    	<p><b>Ampache Installation.</b><br />
    	For the love of Music.</p>
   </div>
</div>

</body>
</html>
