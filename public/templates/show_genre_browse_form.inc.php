<?php

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Video;
use Ampache\Repository\VideoRepositoryInterface;

/** @var string $browse_type */

global $dic;

$videoRepository = $dic->get(VideoRepositoryInterface::class);
$web_path        = AmpConfig::get('web_path'); ?>

<div class="category_options">
    <a class="category <?php echo ($browse_type == 'song') ? 'current' : '' ?>" href="<?php echo $web_path; ?>/browse.php?action=tag&type=song">
        <?php echo T_('Songs'); ?>
    </a>
    <a class="category <?php echo ($browse_type == 'album') ? 'current' : '' ?>" href="<?php echo $web_path; ?>/browse.php?action=tag&type=album">
        <?php echo T_('Albums'); ?>
    </a>
    <a class="category <?php echo ($browse_type == 'artist' || $browse_type == 'album_artist') ? 'current'  : '' ?>" href="<?php echo $web_path; ?>/browse.php?action=tag&type=artist">
        <?php echo T_('Artists'); ?>
    </a>
    <?php if (AmpConfig::get('allow_video') && $videoRepository->getItemCount(Video::class)) { ?>
        <a class="category <?php echo ($browse_type == 'video') ? 'current'  : '' ?>" href="<?php echo $web_path; ?>/browse.php?action=tag&type=video">
            <?php echo T_('Videos'); ?>
        </a>
    <?php } ?>
</div>