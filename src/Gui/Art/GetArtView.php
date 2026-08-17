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

use Ampache\Config\AmpConfig;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Art\Collector\ArtCollector;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\library_item;
use Override;

/**
 * The art search form.
 *
 * Its Spotify filters opened table rows inside a conditional and closed them outside it, so the
 * non-Spotify path emitted two stray cells.
 */
final class GetArtView extends AbstractView
{
    /**
     * These are identifiers rather than something to search on, so the form does not offer them.
     */
    private const array HIDDEN_KEYWORDS = ['mb_albumid_group', 'mb_artistid', 'keyword'];

    public function __construct(
        private readonly library_item $item,
        private readonly int $objectId,
        private readonly string $objectType,
        private readonly string $returnUrl,
        private readonly string $webPath,
    ) {}

    /**
     * @return list<array{key: string, label: string, value: string, required: bool}>
     */
    public function getFields(): array
    {
        $fields = [];
        foreach ($this->item->get_keywords() as $key => $word) {
            if (in_array($key, self::HIDDEN_KEYWORDS, true) || $key === 'year' || empty($word['label'])) {
                continue;
            }

            $fields[] = [
                'key' => (string) $key,
                'label' => (string) $word['label'],
                'value' => unhtmlentities((string) $word['value']),
                'required' => $key === 'album' || ($key === 'artist' && $this->objectType === 'artist'),
            ];
        }

        return $fields;
    }

    public function getFormAction(): string
    {
        return $this->webPath . '/arts.php?action=find_art'
            . '&object_type=' . $this->objectType
            . '&object_id=' . $this->objectId
            . '&burl=' . base64_encode($this->returnUrl)
            . '&artist_name=' . urlencode(Core::get_request('artist_name'))
            . '&album_name=' . urlencode(Core::get_request('album_name'))
            . '&cover=' . urlencode(Core::get_request('cover'));
    }

    public function getMaxUploadSize(): int
    {
        return AmpConfig::get_int('max_upload_size');
    }

    public function getObjectId(): int
    {
        return $this->objectId;
    }

    public function getObjectType(): string
    {
        return $this->objectType;
    }

    public function getReturnUrl(): string
    {
        return $this->returnUrl;
    }

    public function getSearchLimit(): int
    {
        return (int) AmpConfig::get('art_search_limit', ArtCollector::ART_SEARCH_LIMIT);
    }

    public function getTitle(): string
    {
        return ($this->objectType === 'artist') ? T_('Artist Art Search') : T_('Cover Art Search');
    }

    /**
     * A four-digit year is offered back to the Spotify filter; anything else is not a year.
     */
    public function getYear(): string
    {
        $keywords = $this->item->get_keywords();
        $value    = (int) ($keywords['year']['value'] ?? 0);

        return ($value > 999) ? (string) $value : '';
    }

    public function showArtistLimit(): bool
    {
        return $this->objectType === 'artist' && $this->isSpotifyEnabled();
    }

    public function showSpotifyFilters(): bool
    {
        return $this->objectType === 'album' && $this->isSpotifyEnabled();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('art/get_art.phtml');
    }

    private function isSpotifyEnabled(): bool
    {
        return in_array('spotify', AmpConfig::get_array('art_order'), true);
    }
}
