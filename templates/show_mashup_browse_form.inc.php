<h3 class="box-title"><?php echo T_('Browse Ampache...'); ?></h3>
<table class="tabledata">
    <tr id="browse_location">
        <td>
            <a href="<?php echo AmpConfig::get('web_path'); ?>/browse.php?action=song"><?php echo T_('Songs'); ?></a>
        </td>
        <td>
                <a href="<?php echo AmpConfig::get('web_path'); ?>/browse.php?action=album"><?php echo T_('Albums'); ?></a>
        </td>
        <td>
            <a href="<?php echo AmpConfig::get('web_path'); ?>/browse.php?action=album_artist"><?php echo T_('Artists'); ?></a>
        </td>
        <?php if (AmpConfig::get('label')) { ?>
            <td>
                <a href="<?php echo AmpConfig::get('web_path'); ?>/browse.php?action=label"><?php echo T_('Labels'); ?></a>
            </td>
        <?php }
        if (AmpConfig::get('channel')) { ?>
            <td>
                <a href="<?php echo AmpConfig::get('web_path'); ?>/browse.php?action=channel"><?php echo T_('Channels'); ?></a>
            </td>
        <?php }
        if (AmpConfig::get('broadcast')) { ?>
            <td>
                <a href="<?php echo AmpConfig::get('web_path'); ?>/browse.php?action=broadcast"><?php echo T_('Broadcasts'); ?></a>
            </td>
        <?php }
        if (AmpConfig::get('live_stream')) { ?>
            <td>
                <a href="<?php echo AmpConfig::get('web_path'); ?>/browse.php?action=live_stream"><?php echo T_('Radio Stations'); ?>
            </td>
        <?php }
        if (AmpConfig::get('podcast')) { ?>
            <td>
                <a href="<?php echo AmpConfig::get('web_path'); ?>/browse.php?action=podcast"><?php echo T_('Podcasts'); ?></a>
            </td>
        <?php }
        if (AmpConfig::get('allow_video') && Video::get_item_count('Video')) { ?>
            <td>
                <a href="<?php echo AmpConfig::get('web_path'); ?>/browse.php?action=video"><?php echo T_('Videos'); ?></a>
            </td>
        <?php } ?>
    </tr>
</table>