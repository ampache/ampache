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

namespace Ampache\Gui\Partial;

/**
 * One fact in an object header, such as a year, a genre or a duration.
 */
final readonly class HeaderChip
{
    public string $text;

    public function __construct(
        string $text,
        public bool $numeric = false,
        public string $url = '',
        public bool $accent = false,
        public bool $external = false,
        public bool $genre = false,
    ) {
        $this->text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * @param array<array{id: int|string, name: ?string}> $tags as the models' get_tags() returns them
     * @return list<self>
     */
    public static function genres(array $tags, string $urlPrefix): array
    {
        $chips = [];
        foreach ($tags as $tag) {
            $name = trim((string) ($tag['name'] ?? ''));
            if ($name !== '') {
                $chips[] = new self($name, url: $urlPrefix . (int) $tag['id'], genre: true);
            }
        }

        return $chips;
    }

    /**
     * @param HeaderChip|string|int|float|list<self>|null ...$values
     * @return list<self>
     */
    public static function listOf(HeaderChip|string|int|float|array|null ...$values): array
    {
        $chips = [];
        foreach ($values as $value) {
            if (is_array($value)) {
                foreach ($value as $chip) {
                    $chips[] = $chip;
                }
            } elseif ($value instanceof self) {
                if ($value->text !== '') {
                    $chips[] = $value;
                }
            } elseif (trim((string) $value) !== '') {
                $chips[] = new self(trim((string) $value));
            }
        }

        return $chips;
    }
}
