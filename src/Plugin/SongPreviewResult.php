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

namespace Ampache\Plugin;

/**
 * One sample a preview provider found for a track
 *
 * `file` is the provider's own url: Ampache stores it and the player fetches it directly, so nothing
 * is proxied and no provider credentials ever reach the client.
 */
final readonly class SongPreviewResult
{
    /**
     * How closely a result's artist and title must each match what was asked for, as a percentage
     *
     * No provider indexes MusicBrainz ids, so a track is found by text and the top hit is often a
     * different recording. Below this, no preview is better than the wrong one.
     */
    private const int MATCH_THRESHOLD = 70;

    public function __construct(
        public string $file,
        public string $title,
        public string $artist,
    ) {}

    /**
     * Keeps the results that really are the requested track, best match first
     *
     * @param list<self> $results
     * @return list<self>
     */
    public static function rank(array $results, string $artist, string $title): array
    {
        $scored = [];
        foreach ($results as $result) {
            $artistScore = self::similarity($result->artist, $artist);
            $titleScore  = self::similarity($result->title, $title);
            if ($artistScore < self::MATCH_THRESHOLD || $titleScore < self::MATCH_THRESHOLD) {
                continue;
            }

            $scored[] = [$artistScore + $titleScore, $result];
        }

        usort($scored, static fn(array $left, array $right): int => $right[0] <=> $left[0]);

        return array_map(static fn(array $row): self => $row[1], $scored);
    }

    /**
     * Lowercases, drops bracketed suffixes like "(Radio Edit)" and reduces the rest to words
     */
    private static function normalise(string $value): string
    {
        $value = strtolower($value);
        $value = str_replace('&', ' and ', $value);
        $value = (string) preg_replace('/\\([^)]*\\)|\\[[^\\]]*\\]/', ' ', $value);
        $value = (string) preg_replace('/[^a-z0-9]+/', ' ', $value);

        return trim((string) preg_replace('/\\s+/', ' ', $value));
    }

    /**
     * How alike two names are once the noise a provider adds is taken off, as a percentage
     */
    private static function similarity(string $left, string $right): float
    {
        $left  = self::normalise($left);
        $right = self::normalise($right);
        if ($left === '' || $right === '') {
            return 0.0;
        }

        if ($left === $right) {
            return 100.0;
        }

        // a provider often returns the requested name with extra credits or a suffix attached, which is a
        // match but a weaker one than the plain name, so an exact result still sorts above it
        if (str_contains($left, $right) || str_contains($right, $left)) {
            return 90.0;
        }

        similar_text($left, $right, $percent);

        return $percent;
    }
}
