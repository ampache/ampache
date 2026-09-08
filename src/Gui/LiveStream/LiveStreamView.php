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

namespace Ampache\Gui\LiveStream;

use Ampache\Gui\Partial\HeaderChip;
use Ampache\Gui\Partial\ObjectHeaderView;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Art\Art;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Live_Stream;
use Override;

/**
 * The detail page for a radio station.
 */
final class LiveStreamView extends AbstractView
{
    public function __construct(
        private readonly Live_Stream $liveStream,
        private readonly bool $gridView,
        private readonly bool $directPlay,
        private readonly bool $autoplayNext,
        private readonly bool $autoplayAppend,
        private readonly bool $mayAddToPlaylist,
    ) {}

    public function getArt(): string
    {
        ob_start();
        Art::display('live_stream', $this->liveStream->id, $this->getName(), $this->getArtSize(), null, true, false);

        return (string) ob_get_clean();
    }

    /**
     * @return array{width: int, height: int}
     */
    public function getArtSize(): array
    {
        return $this->gridView
            ? ['width' => 150, 'height' => 150]
            : ['width' => 384, 'height' => 384];
    }

    public function getHeader(): ObjectHeaderView
    {
        $radio = $this->liveStream;

        return new ObjectHeaderView(
            kind: T_('Radio Station'),
            title: $this->e($this->getName()),
            art: $this->getArt(),
            chips: HeaderChip::listOf(
                new HeaderChip($this->e((string) $radio->codec), title: T_('Codec')),
            ),
            primaryAction: $this->getPrimaryHeaderAction(),
            actions: $this->getHeaderActions(),
            wideArt: true,
        );
    }

    /**
     * @return list<string>
     */
    public function getHeaderActions(): array
    {
        $radioId = $this->liveStream->id;
        $actions = [];

        if ($this->directPlay) {
            if ($this->autoplayNext) {
                $actions[] = Ajax::button_with_text('?page=stream&action=directplay&object_type=live_stream&object_id=' . $radioId . '&playnext=true', 'menu_open', T_('Play next'), 'nextplay_live_stream_' . $radioId);
            }

            if ($this->autoplayAppend) {
                $actions[] = Ajax::button_with_text('?page=stream&action=directplay&object_type=live_stream&object_id=' . $radioId . '&append=true', 'low_priority', T_('Play last'), 'addplay_live_stream_' . $radioId);
            }
        }

        $actions[] = Ajax::button_with_text('?action=basket&type=live_stream&id=' . $radioId, 'new_window', T_('Add to Temporary Playlist'), 'add_live_stream_' . $radioId);
        if ($this->mayAddToPlaylist) {
            $actions[] = sprintf(
                '<a id="add_to_playlist_%d" onclick="showPlaylistDialog(event, \'live_stream\', \'%d\')">%s %s</a>',
                $radioId,
                $radioId,
                Ui::get_material_symbol('playlist_add', Ui::get_add_to_list_label()),
                Ui::get_add_to_list_label()
            );
        }

        return $actions;
    }

    public function getLiveStream(): Live_Stream
    {
        return $this->liveStream;
    }

    public function getName(): string
    {
        return (string) $this->liveStream->get_fullname();
    }

    public function getPrimaryHeaderAction(): string
    {
        $radioId = $this->liveStream->id;

        return ($this->directPlay)
            ? Ajax::button_with_text('?page=stream&action=directplay&object_type=live_stream&object_id=' . $radioId, 'play_circle', T_('Play'), 'play_live_stream_' . $radioId)
            : '';
    }

    /**
     * Only the rows with a value are printed, so an unset website or codec leaves no empty term behind.
     *
     * @return list<array{label: string, value: string}>
     */
    public function getProperties(): array
    {
        $properties = [
            ['label' => T_('Name'), 'value' => $this->e($this->getName())],
            ['label' => T_('Website'), 'value' => ((string) $this->liveStream->site_url !== '') ? sprintf(
                '<a target="_blank" href="%s">%s</a>',
                $this->e($this->liveStream->site_url),
                $this->e($this->liveStream->site_url)
            ) : ''],
            ['label' => T_('Stream'), 'value' => sprintf(
                '<a target="_blank" href="%s">%s</a>',
                $this->e($this->liveStream->url),
                $this->e($this->liveStream->url)
            )],
            ['label' => T_('Codec'), 'value' => $this->e($this->liveStream->codec)],
        ];

        return array_values(
            array_filter($properties, static fn(array $property): bool => trim($property['value']) !== '')
        );
    }

    /**
     * @return list<array{label: string, properties: array<string, string>}>
     */
    public function getPropertyGroups(): array
    {
        $information = [];
        foreach ($this->getProperties() as $property) {
            if ($property['label'] === T_('Name')) {
                continue;
            }

            $information[$property['label']] = $property['value'];
        }

        return [['label' => T_('Information'), 'properties' => $information]];
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

    public function mayAddToPlaylist(): bool
    {
        return $this->mayAddToPlaylist;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('live_stream.phtml');
    }
}
