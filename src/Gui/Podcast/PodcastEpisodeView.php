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

namespace Ampache\Gui\Podcast;

use Ampache\Gui\View\AbstractView;
use Ampache\Repository\Model\Podcast_Episode;
use Override;

/**
 * The detail page for a single podcast episode.
 */
final class PodcastEpisodeView extends AbstractView
{
    public function __construct(
        private readonly string $webPath,
        private readonly Podcast_Episode $episode,
        private readonly bool $directPlay,
        private readonly bool $autoplayNext,
        private readonly bool $autoplayAppend,
        private readonly bool $showRatings,
        private readonly bool $showWaveform,
        private readonly bool $mayInteract,
        private readonly bool $mayShout,
        private readonly bool $mayShare,
        private readonly bool $mayDownload,
        private readonly bool $mayManage,
        private readonly bool $mayDelete,
        private readonly bool $statisticalGraphsEnabled,
    ) {}

    public function areRatingsShown(): bool
    {
        return $this->showRatings;
    }

    public function getDeleteUrl(): string
    {
        return $this->webPath . '/podcast_episode.php?action=delete&podcast_episode_id=' . $this->episode->id;
    }

    public function getDownloadUrl(): string
    {
        return $this->webPath . '/stream.php?action=download&podcast_episode_id=' . $this->episode->id;
    }

    public function getEpisode(): Podcast_Episode
    {
        return $this->episode;
    }

    public function getGraphUrl(): string
    {
        return $this->webPath . '/stats.php?action=graph&object_type=podcast_episode&object_id=' . $this->episode->id;
    }

    /**
     * The values are html, not text: the model's getters already escape what they return, so escaping
     * again here would show the entities. The two raw values are escaped explicitly below.
     *
     * @return list<array{label: string, value: string}>
     */
    public function getProperties(): array
    {
        $episode    = $this->episode;
        $properties = [
            ['label' => T_('Title'), 'value' => $this->e($episode->get_fullname())],
            ['label' => T_('Description'), 'value' => $episode->get_description()],
            ['label' => T_('Category'), 'value' => $episode->getCategory()],
            ['label' => T_('Author'), 'value' => $episode->getAuthor()],
            ['label' => T_('Publication Date'), 'value' => $this->e($episode->getPubDate()->format(DATE_ATOM))],
            ['label' => T_('Status'), 'value' => $this->e($episode->getState()->toDescription())],
            ['label' => T_('Website'), 'value' => $episode->getWebsite()],
        ];

        if ($episode->time > 0) {
            $properties[] = ['label' => T_('Length'), 'value' => $this->e($episode->get_f_time())];
        }

        if ($this->hasFile()) {
            $properties[] = ['label' => T_('File'), 'value' => $this->e($episode->file)];
            $properties[] = ['label' => T_('Size'), 'value' => $this->e($episode->getSizeFormatted())];
            $properties[] = ['label' => T_('Bitrate'), 'value' => $episode->getBitrateFormatted()];
            $properties[] = ['label' => T_('Channels'), 'value' => $this->e($episode->channels)];
        }

        return array_values(
            array_filter($properties, static fn(array $property): bool => trim($property['value']) !== '')
        );
    }

    public function getShoutUrl(): string
    {
        return $this->webPath . '/shout.php?action=show_add_shout&type=podcast_episode&id=' . $this->episode->id;
    }

    public function getWaveformUrl(): string
    {
        return $this->webPath . '/waveform.php?podcast_episode=' . $this->episode->id;
    }

    /**
     * An episode that has not downloaded yet cannot be played, queued or downloaded.
     */
    public function hasFile(): bool
    {
        return $this->episode->file !== null && $this->episode->file !== '';
    }

    public function isAutoplayAppendEnabled(): bool
    {
        return $this->autoplayAppend;
    }

    public function isAutoplayNextEnabled(): bool
    {
        return $this->autoplayNext;
    }

    public function isDirectPlayEnabled(): bool
    {
        return $this->directPlay;
    }

    public function isStatisticalGraphsEnabled(): bool
    {
        return $this->statisticalGraphsEnabled;
    }

    public function isWaveformShown(): bool
    {
        return $this->showWaveform;
    }

    public function mayDelete(): bool
    {
        return $this->mayDelete;
    }

    public function mayDownload(): bool
    {
        return $this->mayDownload;
    }

    public function mayInteract(): bool
    {
        return $this->mayInteract;
    }

    public function mayManage(): bool
    {
        return $this->mayManage;
    }

    public function mayShare(): bool
    {
        return $this->mayShare;
    }

    public function mayShout(): bool
    {
        return $this->mayShout;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('podcast_episode.phtml');
    }
}
