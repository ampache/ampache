<?php

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Video;
use Ampache\Repository\VideoRepositoryInterface;

global $dic;

$videoRepository  = $dic->get(VideoRepositoryInterface::class);
$web_path         = AmpConfig::get('web_path'); ?>
<?php $filter_str = (string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES) ?>
<table class="tabledata">
    <tr id="browse_location">
        <td><?php if ($filter_str !== 'song') {
    ?><a href="<?php echo $web_path; ?>/browse.php?action=tag&type=song"><?php echo T_('Songs'); ?></a><?php
} else {
        echo T_('Songs');
    } ?></td>
        <td><?php if ($filter_str !== 'album') {
        ?><a href="<?php echo $web_path; ?>/browse.php?action=tag&type=album"><?php echo T_('Albums'); ?></a><?php
    } else {
        echo T_('Albums');
    } ?></td>
        <td><?php if ($filter_str !== 'artist' && $filter_str !== 'album_artist') {
        ?><a href="<?php echo $web_path; ?>/browse.php?action=tag&type=artist"><?php echo T_('Artists'); ?></a><?php
    } else {
        echo T_('Artists');
    } ?></td>
    <?php if (AmpConfig::get('allow_video') && $videoRepository->getItemCount(Video::class)) { ?>
        <td><?php if ($filter_str != 'video') { ?>
            <a href="<?php echo $web_path; ?>/browse.php?action=tag&type=video"><?php echo T_('Videos'); ?></a><?php
        } else {
            echo T_('Videos');
        } ?></td>
    <?php } ?>
    </tr>
</table>
