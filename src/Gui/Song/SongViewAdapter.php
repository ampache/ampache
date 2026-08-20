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

namespace Ampache\Gui\Song;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Gui\System\ConfigViewAdapterInterface;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Application\Song\DeleteAction;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Statistics\Userflag;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\Waveform;
use Ampache\Repository\CatalogRepositoryInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Override;

final class SongViewAdapter extends AbstractView implements SongViewAdapterInterface
{
    public function __construct(
        private readonly CatalogRepositoryInterface $catalogRepository,
        private readonly ConfigContainerInterface $configContainer,
        private readonly ModelFactoryInterface $modelFactory,
        private readonly ConfigViewAdapterInterface $config,
        private readonly GuiGatekeeperInterface $gatekeeper,
        private readonly Song $song,
    ) {}

    public function canAppendNext(): bool
    {
        return Stream_Playlist::check_autoplay_append();
    }

    public function canAutoplayNext(): bool
    {
        return Stream_Playlist::check_autoplay_next();
    }

    public function canBeDeleted(): bool
    {
        return Catalog::can_remove($this->song);
    }

    public function canBeReordered(): bool
    {
        return $this->gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER);
    }

    public function canDisplayStats(): bool
    {
        $owner_id = $this->song->get_user_owner();

        return (
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::STATISTICAL_GRAPHS)
            && (
                (
                    $owner_id !== null
                    && !empty($GLOBALS['user'])
                )
                && $owner_id == $GLOBALS['user']->id
            )
            || $this->gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
        );
    }

    public function canDownload(): bool
    {
        return $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DOWNLOAD);
    }

    public function canEditPlaylist(): bool
    {
        return $this->gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER);
    }

    public function canPostShout(): bool
    {
        return (
            $this->configContainer->isAuthenticationEnabled() === false
            || $this->gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)
        )
            && $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SOCIABLE);
    }

    public function canShare(): bool
    {
        return $this->gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)
            && $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHARE);
    }

    public function canToggleState(): bool
    {
        return (
            $this->gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)
            || (
                (
                    Core::get_global('user') instanceof User
                    && $this->song->get_user_owner() == Core::get_global('user')->id
                )
                && $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::UPLOAD_ALLOW_EDIT)
            )
        );
    }

    public function getAddToPlaylistIcon(): string
    {
        return Ui::get_material_symbol('playlist_add', Ui::get_add_to_list_label());
    }

    public function getAlbumDiskLink(): string
    {
        return $this->song->get_f_album_disk_link();
    }

    public function getAlbumLink(): string
    {
        return $this->song->get_f_album_link();
    }

    public function getAppendNextButton(): string
    {
        $songId = $this->song->getId();

        return Ajax::button(
            '?page=stream&action=directplay&object_type=song&object_id=' . $songId . '&append=true',
            'low_priority',
            T_('Play last'),
            'addplay_song_' . $songId
        );
    }

    public function getAdditionTime(): string
    {
        return get_datetime($this->song->getAdditionTime());
    }

    public function getArtistLink(): string
    {
        return (string) $this->song->get_f_parent_link();
    }

    public function getAutoplayNextButton(): string
    {
        $songId = $this->song->getId();

        return Ajax::button(
            '?page=stream&action=directplay&object_type=song&object_id=' . $songId . '&playnext=true',
            'menu_open',
            T_('Play next'),
            'nextplay_song_' . $songId
        );
    }

    public function getAverageRating(): string
    {
        $rating = $this->modelFactory->createRating(
            $this->song->getId(),
            'song'
        );

        return (string) $rating->get_average_rating();
    }

    public function getComposer(): string
    {
        return (string) $this->song->composer;
    }

    public function getConfig(): ConfigViewAdapterInterface
    {
        return $this->config;
    }

    public function getCustomPlayActions(): string
    {
        $actions = Song::get_custom_play_actions();

        $buttons = '';
        foreach ($actions as $action) {
            $buttons .= Ajax::button(
                '?page=stream&action=directplay&object_type=song&object_id=' . $this->song->getId() . '&custom_play_action=' . $action['index'],
                $action['icon'],
                T_($action['title']),
                $action['icon'] . '_song_' . $this->song->getId()
            );
        }

        return $buttons;
    }

    public function getDeletionIcon(): string
    {
        return Ui::get_material_symbol('close', T_('Delete'));
    }

    public function getDeletionUrl(): string
    {
        return sprintf(
            '%s/song.php?action=%s&song_id=%d',
            $this->configContainer->getWebPath(),
            DeleteAction::REQUEST_KEY,
            $this->song->getId()
        );
    }

    public function getDirectplayButton(): string
    {
        $songId = $this->song->getId();

        return Ajax::button(
            '?page=stream&action=directplay&object_type=song&object_id=' . $songId,
            'play_circle',
            T_('Play'),
            'play_song_' . $songId
        );
    }

    public function getDisplayStatsIcon(): string
    {
        return Ui::get_material_symbol('bar_chart', T_('Graphs'));
    }

    public function getDisplayStatsUrl(): string
    {
        return sprintf(
            '%s/stats.php?action=graph&object_type=song&object_id=%d',
            $this->configContainer->getWebPath(),
            $this->song->getId()
        );
    }

    public function getDownloadIcon(): string
    {
        return Ui::get_material_symbol('download', T_('Download'));
    }

    public function getDownloadUrl(): string
    {
        return sprintf(
            '%s/stream.php?action=download&song_id=%d',
            $this->configContainer->getWebPath(),
            $this->song->getId()
        );
    }

    public function getEditButtonTitle(): string
    {
        return T_('Song Edit');
    }

    public function getEditIcon(): string
    {
        return Ui::get_material_symbol('edit', T_('Edit'));
    }

    public function getExternalPlayIcon(): string
    {
        return Ui::get_material_symbol('link', T_('Link'));
    }

    public function getExternalPlayUrl(): string
    {
        return $this->song->play_url(
            '',
            '',
            false,
            Core::get_global('user')?->getId() ?? 0
        );
    }

    public function getGenre(): string
    {
        return $this->song->get_f_tags();
    }

    public function getId(): int
    {
        return $this->song->getId();
    }

    public function getLicenseLink(): string
    {
        $license = $this->song->getLicense();

        if ($license !== null) {
            return $license->getLinkFormatted();
        }

        return '';
    }

    public function getMoods(): string
    {
        return $this->song->get_f_moods();
    }

    public function getNumberPlayed(): int
    {
        return $this->song->total_count;
    }

    public function getNumberSkipped(): int
    {
        return $this->song->total_skip;
    }

    public function getPlayDuration(): string
    {
        return $this->song->get_f_time();
    }

    public function getPostShoutIcon(): string
    {
        return Ui::get_material_symbol('comment', T_('Post Shout'));
    }

    public function getPostShoutUrl(): string
    {
        return sprintf(
            '%s/shout.php?action=show_add_shout&type=song&id=%d',
            $this->configContainer->getWebPath(),
            $this->song->getId()
        );
    }

    public function getPreferencesIcon(): string
    {
        return Ui::get_material_symbol('page_info', T_('Song Information'));
    }

    /**
     * @return array<string, float|int|string|null>
     */
    public function getProperties(): array
    {
        $this->song->fill_ext_info();
        $songprops = [];

        $songprops[T_('Title')]        = scrub_out($this->song->title);
        $songprops[T_('Song Artist')]  = $this->song->get_f_parent_link();
        $songprops[T_('Album Artist')] = $this->song->get_f_albumartist_link();
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALBUM_GROUP)) {
            $songprops[T_('Album')] = $this->song->get_f_album_link();
        } else {
            $songprops[T_('Album')] = $this->song->get_f_album_disk_link();
        }

        $songprops[T_('Composer')]      = scrub_out($this->song->composer);
        $songprops[T_('Genres')]        = $this->song->get_f_tags();
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHOW_MOOD) && $this->song->get_f_moods() !== '') {
            $songprops[T_('Moods')] = $this->song->get_f_moods();
        }

        $songprops[T_('Track')]         = $this->song->track;
        $songprops[T_('Disk')]          = $this->song->disk;
        $songprops[T_('Disk Subtitle')] = scrub_out($this->song->disksubtitle ?? '');
        $songprops[T_('Year')]          = $this->song->year;
        $songprops[T_('Original Year')] = $this->song->get_album_original_year($this->song->album);
        $songprops[T_('Length')]        = scrub_out($this->song->get_f_time());
        $songprops[T_('Links')]         = "";
        if ($this->configContainer->get(ConfigurationKeyEnum::EXTERNAL_LINKS_GOOGLE)) {
            $songprops[T_('Links')] .= '<a href="https://www.google.com/search?q=%22' . rawurlencode($this->song->get_parent_fullname()) . "%22+%22" . rawurlencode((string) $this->song->get_fullname()) . '%22" target="_blank">' . Ui::get_icon('google', sprintf(T_('Search on %s ...'), 'Google')) . "</a>";
        }

        if ($this->configContainer->get(ConfigurationKeyEnum::EXTERNAL_LINKS_DUCKDUCKGO)) {
            $songprops[T_('Links')] .= '&nbsp;<a href="https://www.duckduckgo.com/?q=' . rawurlencode($this->song->get_parent_fullname()) . "+" . rawurlencode((string) $this->song->get_fullname()) . '" target="_blank">' . Ui::get_icon('duckduckgo', sprintf(T_('Search on %s ...'), 'DuckDuckGo')) . "</a>";
        }

        if ($this->configContainer->get(ConfigurationKeyEnum::EXTERNAL_LINKS_LASTFM)) {
            $songprops[T_('Links')] .= '&nbsp;<a href="https://www.last.fm/search?q=%22' . rawurlencode($this->song->get_parent_fullname()) . "%22+%22" . rawurlencode((string) $this->song->get_fullname()) . '%22&type=track" target="_blank">' . Ui::get_icon('lastfm', sprintf(T_('Search on %s ...'), 'Last.fm')) . "</a>";
        }

        if ($this->configContainer->get(ConfigurationKeyEnum::EXTERNAL_LINKS_BANDCAMP)) {
            $songprops[T_('Links')] .= '&nbsp;<a href="https://bandcamp.com/search?q=' . rawurlencode($this->song->get_parent_fullname()) . "+" . rawurlencode((string) $this->song->get_fullname()) . '&item_type=t" target="_blank">' . Ui::get_icon('bandcamp', sprintf(T_('Search on %s ...'), 'Bandcamp')) . "</a>";
        }

        if ($this->configContainer->get(ConfigurationKeyEnum::EXTERNAL_LINKS_MUSICBRAINZ)) {
            $songprops[T_('Links')] .= ($this->song->mbid)
                ? '&nbsp;<a href="https://musicbrainz.org/recording/' . $this->song->mbid . '" target="_blank">' . Ui::get_icon('musicbrainz', sprintf(T_('Search on %s ...'), 'Musicbrainz')) . "</a>"
                : '&nbsp;<a href="https://musicbrainz.org/taglookup?tag-lookup.artist=%22' . rawurlencode($this->song->get_parent_fullname()) . "%22&tag-lookup.track=%22" . rawurlencode((string) $this->song->get_fullname()) . '%22" target="_blank">' . Ui::get_icon('musicbrainz', sprintf(T_('Search on %s ...'), 'Musicbrainz')) . "</a>";
        }

        $songprops[T_('Comment')] = scrub_out($this->song->comment ?? '');
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::LABEL)) {
            $label_string = '';
            foreach (array_map(trim(...), explode(';', (string) $this->song->label)) as $label_name) {
                $label_string .= '<a href="' . $this->configContainer->getWebPath() . "/labels.php?action=show&name=" . scrub_out($label_name) . '">' . scrub_out($label_name) . "</a>, ";
            }

            $songprops[T_('Label')] = rtrim($label_string, ', ');
        } else {
            $songprops[T_('Label')] = scrub_out($this->song->label ?? '');
        }

        if ($this->song->language !== null) {
            $songprops[T_('Song Language')] = scrub_out($this->song->language);
        }

        $songprops[T_('Catalog Number')] = scrub_out($this->song->get_album_catalog_number($this->song->album));
        $songprops[T_('Barcode')]        = scrub_out($this->song->get_album_barcode($this->song->album));
        $songprops[T_('Bitrate')]        = scrub_out((int) ($this->song->bitrate / 1024) . "-" . strtoupper((string) $this->song->mode));
        $songprops[T_('Channels')]       = $this->song->channels;
        if ($this->song->bpm !== null) {
            $songprops[T_('BPM')] = $this->song->bpm;
        }

        $songprops[T_('Song MBID')]      = scrub_out($this->song->mbid);
        $songprops[T_('Album MBID')]     = scrub_out($this->song->get_album_mbid());
        $songprops[T_('Artist MBID')]    = scrub_out($this->song->get_artist_mbid());
        if ($this->song->replaygain_track_gain !== null) {
            $songprops[T_('ReplayGain Track Gain')] = $this->song->replaygain_track_gain;
        }

        if ($this->song->replaygain_album_gain !== null) {
            $songprops[T_('ReplayGain Album Gain')] = $this->song->replaygain_album_gain;
        }

        if ($this->song->r128_track_gain !== null) {
            $songprops[T_('R128 Track Gain')] = $this->song->r128_track_gain;
        }

        if ($this->song->r128_album_gain !== null) {
            $songprops[T_('R128 Album Gain')] = $this->song->r128_album_gain;
        }

        if ($this->song->get_isrcs() !== []) {
            $songprops[T_('ISRC')] = implode(', ', array_map(scrub_out(...), $this->song->get_isrcs()));
        }

        if ($this->gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER) && $this->song->file !== null) {
            $data                      = pathinfo($this->song->file);
            $songprops[T_('Path')]     = scrub_out((string) ($data['dirname'] ?? ''));
            $songprops[T_('Filename')] = scrub_out($data['filename'] . "." . ($data['extension'] ?? ''));
            $songprops[T_('Size')]     = Ui::format_bytes($this->song->size);
            $songprops[T_('Catalog')]  = $this->catalogRepository->findName($this->song->catalog);
        }

        if ($this->song->update_time !== 0) {
            $songprops[T_('Last Updated')] = get_datetime((int) $this->song->update_time);
        }

        $songprops[T_('Added')] = get_datetime($this->song->getAdditionTime());
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHOW_PLAYED_TIMES)) {
            $songprops[T_('Played')] = $this->song->total_count;
        }

        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHOW_SKIPPED_TIMES)) {
            $songprops[T_('Skipped')] = $this->song->total_skip;
        }

        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHOW_LYRICS)) {
            $songprops[T_('Lyrics')] = '<a title="' . scrub_out($this->song->title) . '" href="' . $this->configContainer->getWebPath() . "/song.php?action=show_lyrics&song_id=" . $this->song->getId() . '">' . T_('Show Lyrics') . "</a>";
        }

        $license = $this->song->getLicense();
        if ($license !== null) {
            $songprops[T_('Licensing')] = $license->getLinkFormatted();
        }

        $owner_id = $this->song->get_user_owner();
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SOCIABLE) && $owner_id > 0) {
            $owner                        = $this->modelFactory->createUser($owner_id);
            $songprops[T_('Uploaded by')] = $owner->get_f_link();
        }

        return $songprops;
    }

    public function getRating(): string
    {
        return Rating::show($this->song->getId(), 'song');
    }

    public function getRefreshIcon(): string
    {
        return Ui::get_material_symbol('sync_alt', T_('Update from tags'));
    }

    public function getReorderIcon(): string
    {
        return Ui::get_material_symbol('drag_indicator', T_('Reorder'));
    }

    public function getShareUi(): string
    {
        return Share::display_ui('song', $this->song->getId(), false);
    }

    public function getSongLink(): string
    {
        return $this->song->get_f_link();
    }

    public function getSongUrl(): string
    {
        return $this->song->get_link();
    }

    public function getTemporaryPlaylistButton(): string
    {
        $songId = $this->song->getId();

        return Ajax::button(
            '?action=basket&type=song&id=' . $songId,
            'new_window',
            T_('Add to Temporary Playlist'),
            'add_song_' . $songId
        );
    }

    public function getToggleStateButton(): string
    {
        $songId = $this->song->getId();

        if ($this->song->enabled) {
            $icon     = 'unpublished';
            $icontext = T_('Disable');
        } else {
            $icon     = 'check_circle';
            $icontext = T_('Enable');
        }

        return Ajax::button(
            '?page=song&action=flip_state&song_id=' . $songId,
            $icon,
            $icontext,
            'flip_song_' . $songId
        );
    }

    public function getTrackNumber(): string
    {
        return (string) $this->song->track;
    }

    public function getUpdateFromTagsUrl(): string
    {
        return sprintf(
            '%s/song.php?action=update_from_tags&song_id=%d',
            $this->configContainer->getWebPath(),
            $this->song->getId()
        );
    }

    public function getUserFlags(): string
    {
        return Userflag::show($this->song->getId(), 'song');
    }

    public function getWaveformUrl(): string
    {
        return sprintf(
            '%s/waveform.php?song_id=%d',
            $this->configContainer->getWebPath(),
            $this->song->getId()
        );
    }

    public function getYear(): int
    {
        return $this->song->year;
    }

    public function hasWaveform(): bool
    {
        return Waveform::is_available($this->song, 'song');
    }

    public function isEditable(): bool
    {
        return (
            $this->gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
            || (
                (
                    Core::get_global('user') instanceof User
                    && $this->song->get_user_owner() == Core::get_global('user')->id
                )
                && $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::UPLOAD_ALLOW_EDIT)
            )
        );
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('song.phtml');
    }
}
