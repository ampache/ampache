<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
 */

declare(strict_types=1);

namespace Ampache\Module\Podcast\Gui;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Catalog\MediaDeletionCheckerInterface;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\PodcastEpisodeInterface;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;

final class PodcastEpisodeViewAdapter implements PodcastEpisodeViewAdapterInterface
{
    private ConfigContainerInterface $configContainer;

    private MediaDeletionCheckerInterface $mediaDeletionChecker;

    private PodcastEpisodeInterface $podcastEpisode;

    private User $user;

    public function __construct(
        ConfigContainerInterface $configContainer,
        MediaDeletionCheckerInterface $mediaDeletionChecker,
        PodcastEpisodeInterface $podcastEpisode,
        User $user
    ) {
        $this->configContainer      = $configContainer;
        $this->mediaDeletionChecker = $mediaDeletionChecker;
        $this->podcastEpisode       = $podcastEpisode;
        $this->user                 = $user;
    }

    public function isRealUser(): bool
    {
        return User::is_registered();
    }

    public function getUserFlags(): string
    {
        return Userflag::show($this->podcastEpisode->getId(), 'podcast_episode');
    }

    public function getRating(): string
    {
        return Rating::show($this->podcastEpisode->getId(), 'podcast_episode');
    }

    public function getBoxTitle(): string
    {
        return sprintf(
            '%s - %s',
            $this->podcastEpisode->getTitleFormatted(),
            $this->podcastEpisode->getPodcast()->getLinkFormatted()
        );
    }

    public function getTitle(): string
    {
        return $this->podcastEpisode->getTitleFormatted();
    }

    public function getDescription(): string
    {
        return $this->podcastEpisode->getDescriptionFormatted();
    }

    public function getCategory(): string
    {
        return $this->podcastEpisode->getCategoryFormatted();
    }

    public function getAuthor(): string
    {
        return $this->podcastEpisode->getAuthorFormatted();
    }

    public function getPublicationDate(): string
    {
        return $this->podcastEpisode->getPublicationDateFormatted();
    }

    public function getState(): string
    {
        return $this->podcastEpisode->getState();
    }

    public function getWebsite(): string
    {
        return $this->podcastEpisode->getWebsiteFormatted();
    }

    public function getDuration(): string
    {
        if ($this->podcastEpisode->getTime() > 0) {
            return $this->podcastEpisode->getDurationFormatted();
        } else {
            return T_('N/A');
        }
    }

    public function hasFile(): bool
    {
        return $this->podcastEpisode->hasFile();
    }

    public function getFile(): string
    {
        return $this->podcastEpisode->getFile();
    }

    public function getSize(): string
    {
        return $this->podcastEpisode->getSizeFormatted();
    }

    public function getDirectplayButton(): string
    {
        $episodeId = $this->podcastEpisode->getId();

        return Ajax::button(
            sprintf('?page=stream&action=directplay&object_type=podcast_episode&object_id=%d', $episodeId),
            'play',
            T_('Play'),
            sprintf('play_podcast_episode_%d', $episodeId)
        );
    }

    public function canAppendNext(): bool
    {
        return Stream_Playlist::check_autoplay_append();
    }

    public function getAppendNextButton(): string
    {
        $episodeId = $this->podcastEpisode->getId();

        return Ajax::button(
            sprintf(
                '?page=stream&action=directplay&object_type=podcast_episode&object_id=%d&playnext=true',
                $episodeId
            ),
            'play_next',
            T_('Play next'),
            sprintf('nextplay_podcast_episode_%d', $episodeId)
        );
    }

    public function canAutoplayAppend(): bool
    {
        return Stream_Playlist::check_autoplay_append();
    }

    public function getAutoplayAppendButton(): string
    {
        $episodeId = $this->podcastEpisode->getId();

        return Ajax::button(
            sprintf(
                '?page=stream&action=directplay&object_type=podcast_episode&object_id=%d&append=true',
                $episodeId
            ),
            'play_add',
            T_('Play last'),
            sprintf('addplay_podcast_episode_%d', $episodeId)
        );
    }

    public function getTemporaryPlaylistButton(): string
    {
        $episodeId = $this->podcastEpisode->getId();

        return Ajax::button(
            sprintf('?action=basket&type=podcast_episode&id=%d', $episodeId),
            'add',
            T_('Add to Temporary Playlist'),
            sprintf('add_podcast_episode_%s', $episodeId)
        );
    }

    public function canDelete(): bool
    {
        return $this->mediaDeletionChecker->mayDelete($this->podcastEpisode, $this->user->getId());
    }

    public function getDeletionIcon(): string
    {
        return Ui::get_icon('delete', T_('Delete'));
    }

    public function getEditButtonLabel(): string
    {
        return T_('Podcast Episode Edit');
    }

    public function getEditButtonIcon(): string
    {
        return Ui::get_icon('edit', T_('Edit'));
    }

    public function canEdit(): bool
    {
        return Access::check('interface', 50);
    }

    public function canShowStats(): bool
    {
        return $this->canEdit() &&
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::STATISTICAL_GRAPHS) &&
            is_dir(__DIR__ . '/../../../../vendor/szymach/c-pchart/src/Chart/');
    }

    public function getStatsButtonIcon(): string
    {
        return Ui::get_icon('statistics', T_('Graphs'));
    }

    public function canPostShout(): bool
    {
        return (
            !$this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::USE_AUTH) ||
            Access::check('interface', 25)
        ) && $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SOCIABLE);
    }

    public function getShoutButtonIcon(): string
    {
        return Ui::get_icon('comment', T_('Post Shout'));
    }

    public function canShare(): bool
    {
        return Access::check('interface', 25) && AmpConfig::get('share');
    }

    public function getShareButton(): string
    {
        return Ui::displayShareUi('podcast_episode', $this->podcastEpisode->getId(), false);
    }

    public function canDownload(): bool
    {
        return Access::check_function('download') && !empty($this->podcastEpisode->getFile());
    }

    public function getDownloadButtonIcon(): string
    {
        return Ui::get_icon('download', T_('Download'));
    }

    public function getPlayUrl(): string
    {
        return $this->podcastEpisode->play_url();
    }

    public function getPlayButtonIcon(): string
    {
        return Ui::get_icon('link', T_('Link'));
    }

    public function getBitrate(): string
    {
        $bitrate = $this->podcastEpisode->getBitrate();
        $mode    = $this->podcastEpisode->getMode();

        if ($bitrate !== null && $mode !== null) {
            return sprintf(
                '%d-%s',
                (int) ($bitrate / 1000),
                strtoupper($mode)
            );
        }

        return '';
    }
}
