<?php

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Video;
use Ampache\Repository\VideoRepositoryInterface;

global $dic;

$videoRepository = $dic->get(VideoRepositoryInterface::class);
$web_path        = AmpConfig::get('web_path');
$filter_str      = $type ?? (string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES); ?>

<div class="category_options">
    <a class="category <?php echo ($filter_str == 'song') ? 'current' : '' ?>" href="<?php echo $web_path; ?>/browse.php?action=tag&type=song">
        <?php echo T_('Songs'); ?>
    </a>
    <a class="category <?php echo ($filter_str == 'album') ? 'current' : '' ?>" href="<?php echo $web_path; ?>/browse.php?action=tag&type=album">
        <?php echo T_('Albums'); ?>
    </a>
    <a class="category <?php echo ($filter_str == 'artist' || $filter_str == 'album_artist') ? 'current'  : '' ?>" href="<?php echo $web_path; ?>/browse.php?action=tag&type=artist">
        <?php echo T_('Artists'); ?>
    </a>
    <?php if (AmpConfig::get('allow_video') && $videoRepository->getItemCount(Video::class)) { ?>
        <a class="category <?php echo ($filter_str == 'video') ? 'current'  : '' ?>" href="<?php echo $web_path; ?>/browse.php?action=tag&type=video">
            <?php echo T_('Videos'); ?>
        </a>
    <?php } ?>
</div>