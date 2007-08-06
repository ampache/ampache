<ul class="sb2" id="sb_admin">
  <li><?php echo _('Catalogs'); ?>
    <div class="sb3"><a href="<?php echo $web_path; ?>/admin/catalog.php?action=show_add_catalog"><?php echo _('Add a Catalog'); ?></a></div>
  </li>
  <li>
    <ul class="sb3" id="sb_admin_catalogs">
    <?php 
    	$catalogs = Catalog::get_catalog_ids(); 
    	foreach ($catalogs as $catalog_id) { 
    		$catalog = new Catalog($catalog_id); 
    ?>
      <li>
        <strong><a href="<?php echo $web_path; ?>/admin/catalog.php?action=show_customize_catalog"><?php echo $catalog->name; ?></a></strong>
        <a href="<?php echo Config::get('web_path'); ?>/admin/catalog.php?action=show_delete_catalog&amp;catalog_id=<?php echo $catalog->id; ?>">
    		  <?php echo get_user_icon('delete',_('Delete Catalog')); ?>
    	  </a>
        <br />
        <a href="<?php echo $web_path; ?>/admin/catalog.php?action=add_to_catalog&amp;catalogs[]=<?php echo $catalog->id; ?>"><?php echo _('Add'); ?></a>
        | <a href="<?php echo $web_path; ?>/admin/catalog.php?action=update_catalog&amp;catalogs[]=<?php echo $catalog->id; ?>"><?php echo _('Verify'); ?></a>
        | <a href="<?php echo $web_path; ?>/admin/catalog.php?action=clean_catalog&amp;catalogs[]=<?php echo $catalog->id; ?>"><?php echo _('Clean'); ?></a>
      </li>
      <?php } // end foreach catalogs ?>
    </ul>
  </li>

  <li><?php echo _('User Tools'); ?>
    <ul class="sb3" id="sb_admin_ut">
      <li id="sb_admin_ut_AddUser"><a href="<?php echo $web_path; ?>/admin/users.php?action=show_add_user"><?php echo _('Add User'); ?></a></li>
      <li id="sb_admin_ut_BrowseUsers"><a href="<?php echo $web_path; ?>/admin/users.php"><?php echo _('Browse Users'); ?></a></li>
    </ul>
  </li>

  <li><?php echo _('Other Tools'); ?>
    <ul class="sb3" id="sb_admin_ot">
      <li id="sb_admin_ot_ClearNowPlaying"><a href="<?php echo $web_path; ?>/admin/catalog.php?action=clear_now_playing"><?php echo _('Clear Now Playing'); ?></a></li>
      <li id="sb_admin_ot_ClearCatStats"><a href="<?php echo $web_path; ?>/admin/catalog.php?action=clear_stats"><?php echo _('Clear Catalog Stats'); ?></a></li>
      <li id="sb_admin_ot_GatherArt"><a href="<?php echo $web_path; ?>/admin/catalog.php?action=gather_album_art"><?php echo _('Gather Album Art'); ?></a></li>
    </ul>
  </li>

</ul>


