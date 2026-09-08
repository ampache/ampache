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

use Ampache\Gui\Partial\HeaderChip;
use Ampache\Gui\Partial\ObjectHeaderView;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Art\Art;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Statistics\Userflag;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Share;
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

    public function getArt(): string
    {
        ob_start();
        Art::display('podcast', $this->episode->podcast, (string) $this->episode->get_fullname(), ['width' => 384, 'height' => 384], null, true, false);

        return (string) ob_get_clean();
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

    public function getHeader(): ObjectHeaderView
    {
        $episode = $this->episode;

        return new ObjectHeaderView(
            kind: T_('Podcast Episode'),
            title: $this->e((string) $episode->get_fullname()),
            art: $this->getArt(),
            breadcrumb: $episode->getPodcastLink(),
            chips: HeaderChip::listOf(
                new HeaderChip($this->e($episode->getCategory()), title: T_('Category')),
                ($episode->time > 0) ? new HeaderChip($episode->get_f_time(), true, title: T_('Length')) : null,
                $this->e($episode->getState()->toDescription()),
            ),
            rating: ($this->showRatings) ? Rating::show($episode->id, 'podcast_episode') : '',
            userflag: ($this->showRatings) ? Userflag::show($episode->id, 'podcast_episode') : '',
            ratingKey: $episode->id . '_podcast_episode',
            primaryAction: $this->getPrimaryHeaderAction(),
            actions: $this->getHeaderActions(),
        );
    }

    /**
     * @return list<string>
     */
    public function getHeaderActions(): array
    {
        $episode   = $this->episode;
        $episodeId = $episode->id;
        $actions   = [];

        if ($this->hasFile()) {
            if ($this->directPlay) {
                if ($this->autoplayNext) {
                    $actions[] = Ajax::button_with_text('?page=stream&action=directplay&object_type=podcast_episode&object_id=' . $episodeId . '&playnext=true', 'menu_open', T_('Play next'), 'addnext_podcast_episode_' . $episodeId);
                }

                if ($this->autoplayAppend) {
                    $actions[] = Ajax::button_with_text('?page=stream&action=directplay&object_type=podcast_episode&object_id=' . $episodeId . '&append=true', 'low_priority', T_('Play last'), 'addplay_podcast_episode_' . $episodeId);
                }
            }

            $actions[] = Ajax::button_with_text('?action=basket&type=podcast_episode&id=' . $episodeId, 'new_window', T_('Add to Temporary Playlist'), 'add_podcast_episode_' . $episodeId);
            if ($this->mayInteract) {
                $actions[] = sprintf(
                    '<a id="add_to_playlist_%d" onclick="showPlaylistDialog(event, \'podcast_episode\', \'%d\')">%s %s</a>',
                    $episodeId,
                    $episodeId,
                    Ui::get_material_symbol('playlist_add', Ui::get_add_to_list_label()),
                    Ui::get_add_to_list_label()
                );
            }
        }

        if ($this->mayShout) {
            $actions[] = $this->link($this->getShoutUrl(), 'comment', T_('Post Shout'));
        }

        if ($this->mayShare) {
            $actions[] = Share::display_ui('podcast_episode', $episodeId);
        } else {
            $actions[] = $this->link($episode->get_link(), 'open_in_new', T_('Link'), true);
        }

        if ($this->mayDownload && $this->hasFile()) {
            $actions[] = $this->link($episode->play_url(), 'link', T_('Stream URL'), true);
            $actions[] = $this->link($this->getDownloadUrl(), 'download', T_('Download'), true);
        }

        if ($this->mayManage) {
            if ($this->statisticalGraphsEnabled) {
                $actions[] = $this->link($this->getGraphUrl(), 'bar_chart', T_('Graphs'));
            }

            $actions[] = sprintf(
                '<a onclick="showEditDialog(\'podcast_episode_row\', \'%d\', \'edit_podcast_episode_%d\', \'%s\', \'\')">%s %s</a>',
                $episodeId,
                $episodeId,
                addslashes(T_('Podcast Episode Edit')),
                Ui::get_material_symbol('edit', T_('Edit')),
                T_('Edit')
            );
        }

        if ($this->mayDelete) {
            $actions[] = $this->link($this->getDeleteUrl(), 'close', T_('Delete'));
        }

        return $actions;
    }

    public function getPrimaryHeaderAction(): string
    {
        $episodeId = $this->episode->id;

        return ($this->hasFile() && $this->directPlay)
            ? Ajax::button_with_text('?page=stream&action=directplay&object_type=podcast_episode&object_id=' . $episodeId, 'play_circle', T_('Play'), 'play_podcast_episode_' . $episodeId)
            : '';
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
            ['label' => T_('Description'), 'value' => nl2br($episode->get_description())],
            ['label' => T_('Category'), 'value' => $episode->getCategory()],
            ['label' => T_('Author'), 'value' => $episode->getAuthor()],
            ['label' => T_('Publication Date'), 'value' => $this->e(get_datetime($episode->getPubDate()->getTimestamp()))],
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

    /**
     * @return list<array{label: string, properties: array<string, string>}>
     */
    public function getPropertyGroups(): array
    {
        $file        = [T_('File'), T_('Size'), T_('Bitrate'), T_('Channels')];
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
