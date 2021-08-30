<?php

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Video;
use Ampache\Repository\VideoRepositoryInterface;

global $dic;
$videoRepository = $dic->get(VideoRepositoryInterface::class);
$web_path        = AmpConfig::get('web_path'); ?>
<h3 class="box-title"><?php echo T_('Dashboards'); ?></h3>
<table class="tabledata">
    <tr id="browse_location">
        <td>
            <a href="<?php echo $web_path; ?>/mashup.php?action=album"><?php echo T_('Albums'); ?></a>
        </td>
        <td>
            <a href="<?php echo $web_path; ?>/mashup.php?action=artist"><?php echo T_('Artists'); ?></a>
        </td>
        <td>
            <a href="<?php echo $web_path; ?>/mashup.php?action=playlist"><?php echo T_('Playlists'); ?></a>
        </td>
        <?php if (AmpConfig::get('podcast')) { ?>
            <td>
                <a href="<?php echo $web_path; ?>/mashup.php?action=podcast_episode"><?php echo T_('Podcast Episodes'); ?></a>
            </td>
        <?php }
        if (AmpConfig::get('allow_video') && $videoRepository->getItemCount(Video::class)) { ?>
            <td>
                <a href="<?php echo $web_path; ?>/mashup.php?action=video"><?php echo T_('Videos'); ?></a>
            </td>
        <?php } ?>
    </tr>
</table>