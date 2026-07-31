<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

// show_share.inc.php

use Ampache\Config\AmpConfig;
use Ampache\Module\Playback\WebPlayer;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Song;

/** @var Share $share */

$embed    = !empty($_REQUEST['embed']);
$isShare  = true;
$iframed  = false;
$playlist = $share->create_fake_playlist();
$web_path = AmpConfig::get_web_path();

$isVideo      = WebPlayer::is_playlist_video($playlist);
$isDemocratic = false;
$isRandom     = false;

// Resolve hero artwork. Songs generally carry their artwork on the album.
$artType = (string) $share->object_type;
$artId   = $share->object_id;
if ($artType === 'song') {
    $song = new Song($share->object_id);
    if ($song->isNew() === false && $song->album) {
        $artType = 'album';
        $artId   = (int) $song->album;
    }
}
// Shares are public (NO_SESSION), so only link the real artwork when image.php will actually serve it without a
// session; otherwise fall back to the site logo so the hero and og:image are never a broken/forbidden image.
$artUrl = '';
if (Art::isPublic() && Art::has_db($artId, $artType)) {
    $artUrl = Art::url($artId, $artType, null) ?? '';
}
if ($artUrl === '') {
    $artUrl = Ui::get_logo_url();
}

$shareTitle   = $share->getObjectName();
$sharedBy     = $share->getUserName();
$siteTitle    = (string) AmpConfig::get('site_title', 'Ampache');
$sharedByText = sprintf(T_('Shared by %s'), $sharedBy); ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo scrub_out($shareTitle . ' - ' . $siteTitle); ?></title>
<?php if (!$embed) { ?>
<meta property="og:type" content="music.song" />
<meta property="og:title" content="<?php echo scrub_out($shareTitle); ?>" />
<meta property="og:image" content="<?php echo scrub_out($artUrl); ?>" />
<meta property="og:description" content="<?php echo scrub_out($sharedByText); ?>" />
<meta property="og:url" content="<?php echo scrub_out((string) $share->public_url); ?>" />
<meta property="og:site_name" content="<?php echo scrub_out($siteTitle); ?>" />
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="<?php echo scrub_out($shareTitle); ?>" />
<meta name="twitter:description" content="<?php echo scrub_out($sharedByText); ?>" />
<meta name="twitter:image" content="<?php echo scrub_out($artUrl); ?>" />
<?php }
// Load the player CSS/JS into the head; the player itself is rendered as a fragment below.
require_once Ui::find_template('show_html5_player_headers.inc.php'); ?>
<style>
    body {
        margin: 0;
        background-color: #191919;
        color: #ccc;
        font-family: 'Verdana', Arial, sans-serif;
    }

    .share-hero {
        display: flex;
        align-items: center;
        gap: 20px;
        max-width: 800px;
        margin: 0 auto;
        padding: 24px 20px;
    }

    .share-hero img {
        width: 180px;
        height: 180px;
        object-fit: cover;
        border-radius: 8px;
        flex-shrink: 0;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.5);
    }

    .share-hero .share-meta {
        min-width: 0;
    }

    .share-hero .share-meta h1 {
        margin: 0 0 8px;
        font-size: 1.5em;
        color: #fff;
        word-break: break-word;
    }

    .share-hero .share-meta .share-sub {
        font-size: 1em;
        color: #999;
        margin-bottom: 4px;
    }

    .share-hero .share-meta .share-sub a {
        color: #999;
        text-decoration: none;
    }

    .jp-area {
        width: 480px;
        min-width: 0;
        max-width: 100%;
        margin: 0 auto;
        padding: 0 20px 8px;
        box-sizing: border-box;
    }

    .jp-area.jp-area-embed {
        padding: 0;
    }

    .jp-audio {
        width: 100%;
    }

    .jp-playlist {
        height: auto;
        max-height: 40vh;
    }

    div.jp-interface,
    .jp-type-playlist {
        background-color: #191919;
    }

    div.jp-title,
    div.jp-playlist {
        color: #ccc;
        background-color: #202020;
        border-top: 1px solid #191919;
    }

    div.jp-playlist li {
        border-bottom: 1px solid #121212;
    }

    .jp-type-playlist .jp-playlist a {
        color: #fff;
    }

    .jp-type-playlist .jp-playlist a:hover,
    .jp-type-playlist .jp-playlist a:active,
    .jp-type-playlist .jp-playlist a:focus,
    .jp-type-playlist .jp-playlist a.jp-playlist-current {
        color: #ff9d00;
    }

    .jp-current-time,
    .jp-duration {
        color: #999;
    }

    .jp-audio .jp-type-playlist .jp-interface {
        height: 98px;
    }

    .jp-audio .jp-type-playlist .jp-progress {
        top: 58px;
        left: 20px;
        right: 20px;
        height: 6px;
        background-color: #3a3a3a;
        border-radius: 5px;
    }

    .jp-time-row {
        position: absolute;
        top: 70px;
        left: 20px;
        right: 20px;
    }

    .share-footer {
        text-align: center;
        padding: 20px;
        font-size: 0.95em;
    }

    .share-footer a {
        color: #ccc;
        text-decoration: none;
        margin: 0 8px;
    }

    @media (max-width: 768px) {
        .jp-title,
        .jp-playlist {
            width: 100%;
        }
    }

    @media (max-width: 600px) {
        .share-hero {
            flex-direction: column;
            text-align: center;
        }
    }
</style>
</head>
<body>
<?php if (!$embed) { ?>
<div class="share-hero">
    <img src="<?php echo scrub_out($artUrl); ?>" alt="<?php echo scrub_out($shareTitle); ?>">
    <div class="share-meta">
        <h1><?php echo scrub_out($shareTitle); ?></h1>
        <?php if ($share->getObjectUrl() !== '') { ?>
            <div class="share-sub"><?php echo $share->getObjectUrl(); ?></div>
        <?php } ?>
        <div class="share-sub"><?php echo scrub_out($sharedByText); ?></div>
    </div>
</div>
<?php }
// Render the compact player as a document fragment (head/body are managed here).
$playerFragment = true;
require Ui::find_template('show_html5_player.inc.php');

if (!$embed) { ?>
<div class="share-footer">
    <a href="<?php echo $share->public_url; ?>"><?php echo scrub_out($sharedByText); ?></a>
    <?php if ($share->allow_download) { ?>
        <a href="<?php echo $web_path; ?>/share.php?action=download&id=<?php echo $share->id; ?>&secret=<?php echo $share->secret; ?>" rel="nofollow"><?php echo Ui::get_material_symbol('download', T_('Download')) . ' ' . T_('Download'); ?></a>
    <?php } ?>
</div>
<?php } ?>
<?php echo Ui::material_symbol_sprite(); ?>
</body>
</html>
