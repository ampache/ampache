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
 *
 */

declare(strict_types=1);

namespace Ampache\Module\Podcast\Gui;

use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\AmpacheRss;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\PodcastInterface;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;

/**
 * Provides accessors to podcast related data for gui templates
 * The template for this adapter is `podcast.xhtml`
 */
final class PodcastViewAdapter implements PodcastViewAdapterInterface
{
    private ModelFactoryInterface $modelFactory;

    private PodcastEpisodeRepositoryInterface $podcastEpisodeRepository;

    private PodcastInterface $podcast;

    public function __construct(
        PodcastEpisodeRepositoryInterface $podcastEpisodeRepository,
        ModelFactoryInterface $modelFactory,
        PodcastInterface $podcast
    ) {
        $this->podcastEpisodeRepository = $podcastEpisodeRepository;
        $this->modelFactory             = $modelFactory;
        $this->podcast                  = $podcast;
    }

    public function getArt(): ?string
    {
        return Art::display(
            'podcast',
            $this->podcast->getId(),
            $this->podcast->getTitleFormatted(),
            Ui::is_grid_view('podcast') ? 32 : 11
        );
    }

    public function getDescription(): string
    {
        return $this->podcast->getDescription();
    }

    public function isRealUser(): bool
    {
        return User::is_registered();
    }

    public function getUserFlags(): string
    {
        return Userflag::show($this->podcast->getId(), 'podcast');
    }

    public function getRating(): string
    {
        return Rating::show($this->podcast->getId(), 'podcast');
    }

    public function getDirectplayButton(): string
    {
        $podcastId = $this->podcast->getId();

        return Ajax::button_with_text(
            '?page=stream&action=directplay&object_type=podcast&object_id=' . $podcastId,
            'play',
            T_('Play All'),
            'directplay_full_' . $podcastId
        );
    }

    public function canAutoplayNext(): bool
    {
        return Stream_Playlist::check_autoplay_next();
    }

    public function getAutoplayNextButton(): string
    {
        $podcastId = $this->podcast->getId();

        return Ajax::button_with_text(
            '?page=stream&action=directplay&object_type=podcast&object_id=' . $podcastId . '&playnext=true',
            'play_next',
            T_('Play All Next'),
            'addnext_podcast_' . $podcastId
        );
    }

    public function canAppendNext(): bool
    {
        return Stream_Playlist::check_autoplay_append();
    }

    public function getAppendNextButton(): string
    {
        $podcastId = $this->podcast->getId();

        return Ajax::button_with_text(
            '?page=stream&action=directplay&object_type=podcast&object_id=' . $podcastId . '&append=true',
            'play_add',
            T_('Play All Last'),
            'addplay_podcast_' . $podcastId
        );
    }

    public function canBeManaged(): bool
    {
        return Access::check('interface', 50);
    }

    public function getStatsIcon(): string
    {
        return sprintf('%s %s', Ui::get_icon('statistics', T_('Graphs')), T_('Graphs'));
    }

    public function getRssLink(): string
    {
        return AmpacheRss::get_display(
            'podcast',
            -1,
            T_('RSS Feed'),
            ['object_type' => 'podcast', 'object_id' => $this->podcast->getId()]
        );
    }

    public function getWebsite(): string
    {
        return $this->podcast->getWebsite();
    }

    public function getWebsiteIcon(): string
    {
        return sprintf('%s %s', Ui::get_icon('link', T_('Website')), T_('Website'));
    }

    public function getEditTitle(): string
    {
        return T_('Podcast Edit');
    }

    public function getEditIcon(): string
    {
        return sprintf('%s %s', Ui::get_icon('edit', T_('Edit')), T_('Edit Podcast'));
    }

    public function getSyncButton(): string
    {
        $podcastId = $this->podcast->getId();

        return Ajax::button_with_text(
            '?page=podcast&action=sync&podcast_id=' . $podcastId,
            'file_refresh',
            T_('Sync'),
            'sync_podcast_' . $podcastId
        );
    }

    public function canDelete(): bool
    {
        return Access::check('interface', 75);
    }

    public function getDeleteIcon(): string
    {
        return sprintf('%s %s', Ui::get_icon('delete', T_('Delete')), T_('Delete'));
    }

    public function getEpisodeList(): string
    {
        $podcastEpisodeIds = $this->podcastEpisodeRepository->getEpisodeIds($this->podcast);

        $browse = $this->modelFactory->createBrowse();
        $browse->set_type('podcast_episode');

        ob_start();

        $browse->show_objects($podcastEpisodeIds, true);

        $result = ob_get_contents();
        ob_end_clean();

        $browse->store();

        return $result;
    }

    public function getTitleFormatted(): string
    {
        return $this->podcast->getTitleFormatted();
    }
}
