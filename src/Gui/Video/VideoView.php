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

use Ampache\Gui\Partial\HeaderChip;
use Ampache\Gui\Partial\ObjectHeaderView;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Art\Art;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Statistics\Userflag;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Share;
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

    public function getArt(): string
    {
        ob_start();
        Art::display('video', $this->video->getId(), $this->getName(), ['width' => 200, 'height' => 300], null, true, false, 'preview');

        return (string) ob_get_clean();
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

    public function getHeader(): ObjectHeaderView
    {
        $video = $this->video;

        return new ObjectHeaderView(
            kind: T_('Video'),
            title: $this->e($this->getName()),
            art: $this->getArt(),
            chips: HeaderChip::listOf(
                ($video->release_date) ? new HeaderChip($this->e(get_datetime((int) $video->release_date, 'short', 'none')), title: T_('Release Date')) : null,
                new HeaderChip((string) $video->get_f_time(), true, title: T_('Length')),
                new HeaderChip((string) $video->get_f_resolution(), true, title: T_('Resolution')),
            ),
            rating: ($this->showRatings)
                ? Rating::show($video->getId(), 'video') . Userflag::show($video->getId(), 'video')
                : '',
            primaryAction: $this->getPrimaryHeaderAction(),
            actions: $this->getHeaderActions(),
        );
    }

    /**
     * @return list<string>
     */
    public function getHeaderActions(): array
    {
        $video   = $this->video;
        $videoId = $video->getId();
        $actions = [];

        if ($this->directPlay) {
            if ($this->autoplayNext) {
                $actions[] = Ajax::button_with_text('?page=stream&action=directplay&object_type=video&object_id=' . $videoId . '&playnext=true', 'menu_open', T_('Play next'), 'nextplay_video_' . $videoId);
            }

            if ($this->autoplayAppend) {
                $actions[] = Ajax::button_with_text('?page=stream&action=directplay&object_type=video&object_id=' . $videoId . '&append=true', 'low_priority', T_('Play last'), 'addplay_video_' . $videoId);
            }
        }

        $actions[] = Ajax::button_with_text('?action=basket&type=video&id=' . $videoId, 'new_window', T_('Add to Temporary Playlist'), 'add_video_' . $videoId);
        if ($this->mayInteract) {
            $actions[] = sprintf(
                '<a id="add_to_playlist_%d" onclick="showPlaylistDialog(event, \'video\', \'%d\')">%s %s</a>',
                $videoId,
                $videoId,
                Ui::get_material_symbol('playlist_add', Ui::get_add_to_list_label()),
                Ui::get_add_to_list_label()
            );
        }

        if ($this->mayShout) {
            $actions[] = $this->link($this->getShoutUrl(), 'comment', T_('Post Shout'));
        }

        if ($this->mayShare) {
            $actions[] = Share::display_ui('video', $videoId);
        }

        if ($this->mayDownload) {
            $actions[] = $this->link($video->play_url(), 'link', T_('Link'), true);
            $actions[] = $this->link($this->getDownloadUrl(), 'download', T_('Download'), true);
        }

        if ($this->mayManage) {
            if ($this->statisticalGraphsEnabled) {
                $actions[] = $this->link($this->getGraphUrl(), 'bar_chart', T_('Graphs'));
            }

            $actions[] = sprintf(
                '<a onclick="showEditDialog(\'video_row\', \'%d\', \'edit_video_%d\', \'%s\', \'\')">%s %s</a>',
                $videoId,
                $videoId,
                addslashes(T_('Video Edit')),
                Ui::get_material_symbol('edit', T_('Edit')),
                T_('Edit')
            );
        }

        if ($this->mayDelete) {
            $actions[] = $this->link($this->getDeleteUrl(), 'close', T_('Delete'));
        }

        return $actions;
    }

    public function getName(): string
    {
        return $this->video->get_fullname() ?? '';
    }

    public function getPrimaryHeaderAction(): string
    {
        $videoId = $this->video->getId();

        return ($this->directPlay)
            ? Ajax::button_with_text('?page=stream&action=directplay&object_type=video&object_id=' . $videoId, 'play_circle', T_('Play'), 'play_video_' . $videoId)
            : '';
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
            ['label' => T_('Video Bitrate'), 'value' => $this->e((string) (int) ($video->video_bitrate / 1024))],
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

    /**
     * @return list<array{label: string, properties: array<string, string>}>
     */
    public function getPropertyGroups(): array
    {
        $file        = [T_('Path'), T_('Filename'), T_('Size'), T_('Last Updated'), T_('Added'), T_('Played')];
        $information = [];
        $technical   = [];

        foreach ($this->getProperties() as $property) {
            if ($property['label'] === T_('Title')) {
                continue;
            }

            if (in_array($property['label'], $file, true)) {
                $technical[$property['label']] = $property['value'];
            } else {
                $information[$property['label']] = $property['value'];
            }
        }

        return [
            ['label' => T_('Information'), 'properties' => $information],
            ['label' => T_('File'), 'properties' => $technical],
        ];
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

    private function link(string $url, string $icon, string $label, bool $external = false): string
    {
        return sprintf(
            '<a %shref="%s">%s %s</a>',
            ($external) ? 'class="nohtml" rel="nofollow" ' : '',
            $this->e($url),
            Ui::get_material_symbol($icon, $label),
            $this->e($label)
        );
    }
}
