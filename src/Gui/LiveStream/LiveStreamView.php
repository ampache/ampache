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

use Ampache\Gui\View\AbstractView;
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

    /**
     * @return array{width: int, height: int}
     */
    public function getArtSize(): array
    {
        return $this->gridView
            ? ['width' => 150, 'height' => 150]
            : ['width' => 128, 'height' => 128];
    }

    public function getLiveStream(): Live_Stream
    {
        return $this->liveStream;
    }

    public function getName(): string
    {
        return (string) $this->liveStream->get_fullname();
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
            ['label' => T_('Website'), 'value' => $this->e($this->liveStream->site_url)],
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
