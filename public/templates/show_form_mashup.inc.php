<?php

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\VideoRepositoryInterface;

global $dic;

$web_path = AmpConfig::get_web_path();

$videoRepository = $dic->get(VideoRepositoryInterface::class);
$filter_str      = (string) filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS);
$albumString     = (AmpConfig::get('album_group'))
    ? 'album'
    : 'album_disk'; ?>
<h3 class="box-title"><?php echo T_('Dashboards'); ?></h3>
<div class="category_options">
    <a class="category <?php echo ($filter_str == 'album_disk' || $filter_str == 'album') ? 'current' : ''; ?>" href="<?php echo $web_path; ?>/mashup.php?action=<?php echo $albumString; ?>">
        <?php echo T_('Albums'); ?>
    </a>
    <a class="category <?php echo ($filter_str == 'artist') ? 'current' : ''; ?>" href="<?php echo $web_path; ?>/mashup.php?action=artist">
        <?php echo T_('Artists'); ?>
    </a>
    <?php if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)) { ?>
    <a class="category <?php echo ($filter_str == 'playlist') ? 'current' : ''; ?>" href="<?php echo $web_path; ?>/mashup.php?action=playlist">
        <?php echo T_('Playlists'); ?>
    </a>
    <?php } ?>
    <?php if (AmpConfig::get('podcast')) { ?>
        <a class="category <?php echo ($filter_str == 'podcast_episode') ? 'current' : ''; ?>" href="<?php echo $web_path; ?>/mashup.php?action=podcast_episode"><?php echo T_('Podcast Episodes'); ?></a>
    <?php } ?>
    <?php if (AmpConfig::get('allow_video') && $videoRepository->getItemCount()) { ?>
        <a class="category <?php echo ($filter_str == 'video') ? 'current' : ''; ?>" href="<?php echo $web_path; ?>/mashup.php?action=video"><?php echo T_('Videos'); ?></a>
    <?php } ?>
</div>
