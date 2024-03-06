<?php

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\Video;
use Ampache\Repository\VideoRepositoryInterface;

global $dic;

$videoRepository = $dic->get(VideoRepositoryInterface::class);
$web_path        = (string)AmpConfig::get('web_path', '');
$filter_str      = $type ?? (string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS); ?>

<div class="category_options">
    <a class="category <?php echo ($filter_str == 'song') ? 'current' : ''; ?>" href="<?php echo $web_path; ?>/browse.php?action=tag&type=song">
        <?php echo T_('Songs'); ?>
    </a>
    <a class="category <?php echo ($filter_str == 'album') ? 'current' : ''; ?>" href="<?php echo $web_path; ?>/browse.php?action=tag&type=album">
        <?php echo T_('Albums'); ?>
    </a>
    <a class="category <?php echo ($filter_str == 'artist' || $filter_str == 'album_artist') ? 'current' : ''; ?>" href="<?php echo $web_path; ?>/browse.php?action=tag&type=artist">
        <?php echo T_('Artists'); ?>
    </a>
    <?php if (AmpConfig::get('allow_video') && $videoRepository->getItemCount(Video::class)) { ?>
        <a class="category <?php echo ($filter_str == 'video') ? 'current' : ''; ?>" href="<?php echo $web_path; ?>/browse.php?action=tag&type=video">
            <?php echo T_('Videos'); ?>
        </a>
    <?php } ?>
    <?php if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER) && Tag::get_merged_count() > 0) { ?>
    <a class="category <?php echo ($filter_str == 'tag_hidden') ? 'current' : ''; ?>" href="<?php echo $web_path; ?>/browse.php?action=tag&type=tag_hidden">
        <?php echo T_('Hidden'); ?>
    </a>
    <?php } ?>
</div>
