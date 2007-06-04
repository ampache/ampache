<h4><?php echo _('Catalogs'); ?></h4>
<span><a href="<?php echo $web_path; ?>/admin/catalog.php?action=show_add_catalog"><?php echo _('Add a Catalog'); ?></a></span>
<hr />
<?php 
	$catalogs = Catalog::get_catalogs(); 
	foreach ($catalogs as $catalog_id) { 
		$catalog = new Catalog($catalog_id); 
?>
<strong><a href="<?php echo $web_path; ?>/admin/catalog?action=show_customize_catalog">
	<?php echo $catalog->name; ?>
</a></strong><br />
<a href="<?php echo $web_path; ?>/admin/catalog.php?action=add_to_catalog&amp;catalogs[]=<?php echo $catalog->id; ?>"><?php echo _('Add'); ?></a>
| <a href="<?php echo $web_path; ?>/admin/catalog.php?action=update_catalog&amp;catalogs[]=<?php echo $catalog->id; ?>"><?php echo _('Verify'); ?></a>
| <a href="<?php echo $web_path; ?>/admin/catalog.php?action=clean_catalog&amp;catalogs[]=<?php echo $catalog->id; ?>"><?php echo _('Clean'); ?></a>
<?php } ?>
<hr />
<h4><?php echo _('User Tools'); ?></h4>
<span><a href="<?php echo $web_path; ?>/admin/users.php?action=show_add_user"><?php echo _('Add User'); ?></a></span>
<span><a href="<?php echo $web_path; ?>/admin/users.php"><?php echo _('Browse Users'); ?></a></span>
<hr />
<h4><?php echo _('Other Tools'); ?></h4>
<span><a href="<?php echo $web_path; ?>/admin/catalog.php?action=clear_now_playing"><?php echo _('Clear Now Playing'); ?></a></span>
<span><a href="<?php echo $web_path; ?>/admin/catalog.php?action=clear_stats"><?php echo _('Clear Catalog Stats'); ?></a></span>
<span><a href="<?php echo $web_path; ?>/admin/catalog.php?action=gather_album_art"><?php echo _('Gather Album Art'); ?></a></span>
<hr />


