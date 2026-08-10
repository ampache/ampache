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

namespace Ampache\Gui\Video;

use Ampache\Gui\View\AbstractView;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Video;
use Override;

/**
 * The detail page for a video.
 */
final class VideoView extends AbstractView
{
    /**
     * @param array<array{lang_code: string, lang_name: string, ...}> $subtitles
     */
    public function __construct(
        private readonly string $webPath,
        private readonly Video $video,
        private readonly array $subtitles,
        private readonly string $selectedSubtitle,
        private readonly bool $subtitlesEnabled,
        private readonly bool $directPlay,
        private readonly bool $autoplayNext,
        private readonly bool $autoplayAppend,
        private readonly bool $showRatings,
        private readonly bool $showPlayedTimes,
        private readonly bool $mayInteract,
        private readonly bool $mayShout,
        private readonly bool $mayShare,
        private readonly bool $mayDownload,
        private readonly bool $mayManage,
        private readonly bool $maySeePath,
        private readonly bool $mayDelete,
        private readonly bool $statisticalGraphsEnabled,
    ) {}

    public function areRatingsShown(): bool
    {
        return $this->showRatings;
    }

    public function areSubtitlesShown(): bool
    {
        return $this->subtitlesEnabled;
    }

    public function getDeleteUrl(): string
    {
        return $this->webPath . '/video.php?action=delete&video_id=' . $this->video->id;
    }

    public function getDownloadUrl(): string
    {
        return $this->webPath . '/stream.php?action=download&video_id=' . $this->video->id;
    }

    public function getGraphUrl(): string
    {
        return $this->webPath . '/stats.php?action=graph&object_type=video&object_id=' . $this->video->id;
    }

    public function getName(): string
    {
        return $this->video->get_fullname() ?? '';
    }

    /**
     * The values are html: the model's getters escape what they return, so escaping again would show
     * the entities. Anything raw is escaped explicitly here.
     *
     * @return list<array{label: string, value: string}>
     */
    public function getProperties(): array
    {
        $video      = $this->video;
        $properties = [
            ['label' => T_('Title'), 'value' => $this->e($this->getName())],
            ['label' => T_('Length'), 'value' => $this->e($video->get_f_time())],
            ['label' => T_('Release Date'), 'value' => $this->e($video->release_date ? get_datetime((int) $video->release_date, 'short', 'none') : '')],
            ['label' => T_('Codec'), 'value' => $this->e($video->video_codec . ' / ' . $video->audio_codec)],
            ['label' => T_('Resolution'), 'value' => $this->e($video->get_f_resolution())],
            ['label' => T_('Display'), 'value' => $this->e($video->get_f_display())],
            ['label' => T_('Audio Bitrate'), 'value' => $this->e((int) ($video->bitrate / 1024) . '-' . strtoupper((string) $video->mode))],
            ['label' => T_('Video Bitrate'), 'value' => $this->e((string) ($video->video_bitrate / 1024))],
            ['label' => T_('Frame Rate'), 'value' => $this->e($video->frame_rate ? $video->frame_rate . ' fps' : '')],
            ['label' => T_('Channels'), 'value' => $this->e($video->channels)],
        ];

        if ($this->maySeePath && $video->file !== null && $video->file !== '') {
            $path         = pathinfo($video->file);
            $properties[] = ['label' => T_('Path'), 'value' => $this->e($path['dirname'])];
            $properties[] = ['label' => T_('Filename'), 'value' => isset($path['extension'])
                ? $this->e($path['filename'] . '.' . $path['extension'])
                : ''];
            $properties[] = ['label' => T_('Size'), 'value' => $this->e(Ui::format_bytes($video->size))];
        }

        if ($video->update_time) {
            $properties[] = ['label' => T_('Last Updated'), 'value' => $this->e(get_datetime((int) $video->update_time))];
        }

        $properties[] = ['label' => T_('Added'), 'value' => $this->e(get_datetime((int) $video->addition_time))];

        if ($this->showPlayedTimes) {
            $properties[] = ['label' => T_('Played'), 'value' => $this->e($video->total_count)];
        }

        return array_values(
            array_filter($properties, static fn(array $property): bool => trim($property['value']) !== '')
        );
    }

    public function getSelectedSubtitle(): string
    {
        return $this->selectedSubtitle;
    }

    public function getShoutUrl(): string
    {
        return $this->webPath . '/shout.php?action=show_add_shout&type=video&id=' . $this->video->id;
    }

    /**
     * @return array<array{lang_code: string, lang_name: string, ...}>
     */
    public function getSubtitles(): array
    {
        return $this->subtitles;
    }

    public function getVideo(): Video
    {
        return $this->video;
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
        return $this->findTemplate('video.phtml');
    }
}
