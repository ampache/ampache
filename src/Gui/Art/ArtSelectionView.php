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

namespace Ampache\Gui\Art;

use Ampache\Gui\View\AbstractView;
use Ampache\Module\Art\Art;
use Ampache\Module\System\Core;
use Override;

/**
 * The grid of art candidates a search turned up, for the user to pick one from.
 *
 * The candidates are handed in rather than read back out of the session, so the view does not care how
 * they were stored.
 */
final class ArtSelectionView extends AbstractView
{
    private const int COLUMNS = 5;

    /** @var list<list<array{url: string, title: string, width: int, height: int, selectUrl: null|string}>>|null */
    private ?array $rows = null;

    /**
     * @param array<int, array{title?: string, url?: string, db?: int, mime?: string}> $images
     */
    public function __construct(
        private readonly string $webPath,
        private readonly int $objectId,
        private readonly string $objectType,
        private readonly string $backUrl,
        private readonly array $images,
    ) {}

    public function getColumnCount(): int
    {
        return self::COLUMNS;
    }

    /**
     * The candidates in rows of five, each cell already carrying everything the markup needs.
     *
     * Measuring a candidate means fetching it, so this is built once.
     *
     * @return list<list<array{url: string, title: string, width: int, height: int, selectUrl: null|string}>>
     */
    public function getRows(): array
    {
        if ($this->rows !== null) {
            return $this->rows;
        }

        $cells = [];
        foreach (array_keys($this->images) as $key) {
            $dimensions = $this->getDimensions($key);
            $usable     = $dimensions['width'] > 0 && $dimensions['height'] > 0;

            $cells[] = [
                'url' => $usable ? $this->getImageUrl($key) : $this->getPlaceholderUrl(),
                'title' => (string) ($this->images[$key]['title'] ?? ''),
                'width' => $dimensions['width'],
                'height' => $dimensions['height'],
                // an image that could not be measured cannot be selected either
                'selectUrl' => $usable ? $this->getSelectUrl($key) : null,
            ];
        }

        return $this->rows = array_chunk($cells, self::COLUMNS);
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('art_selection.phtml');
    }

    /**
     * @return array{width: int, height: int}
     */
    private function getDimensions(int $key): array
    {
        if (($this->images[$key] ?? []) === []) {
            return ['width' => 0, 'height' => 0];
        }

        return Core::image_dimensions(Art::get_from_source($this->images[$key], $this->objectType));
    }

    /**
     * The candidate is served back out of the session, and the cache buster keeps a re-search from
     * showing the previous set's image at the same index.
     */
    private function getImageUrl(int $key): string
    {
        return sprintf(
            '%s/image.php?type=session&image_index=%d&cache_bust=%s&object_type=%s',
            $this->webPath,
            $key,
            bin2hex(random_bytes(20)),
            rawurlencode($this->objectType)
        );
    }

    private function getPlaceholderUrl(): string
    {
        return sprintf(
            '%s/images/%s.png',
            $this->webPath,
            ($this->objectType === 'folder') ? 'folder' : 'blankalbum'
        );
    }

    private function getSelectUrl(int $key): string
    {
        return sprintf(
            '%s/arts.php?action=select_art&image=%d&object_type=%s&object_id=%d&burl=%s',
            $this->webPath,
            $key,
            rawurlencode($this->objectType),
            $this->objectId,
            base64_encode($this->backUrl)
        );
    }
}
