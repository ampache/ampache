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

namespace Ampache\Gui\View;

use PHPUnit\Framework\TestCase;

/**
 * A narrow screen folds the columns a row cannot show into a panel that has no header above it, so a folded
 * cell holding a bare number says nothing unless its own `data-label` names it.
 */
class TemplateRowListTest extends TestCase
{
    /**
     * Per row template, the cells that need no label: the ones the stylesheet keeps on the two visible lines,
     * plus the two toolbars, whose contents name themselves. A dynamic class is matched on its accessor.
     *
     * @var array<string, list<string>>
     */
    private const array NEEDS_NO_LABEL = [
        'song_row.phtml' => [
            'cel_select', 'cel_play', 'cel_song', 'cel_artist', 'cel_album', 'cel_time', 'cel_add', 'cel_action', 'cel_drag',
        ],
        'album_row.phtml' => [
            'cel_play', 'getClassCover', 'getClassAlbum', 'getClassArtist', 'cel_year', 'cel_add', 'cel_action',
        ],
        'album_disk_row.phtml' => [
            'cel_play', 'getClassCover', 'getClassAlbum', 'getClassArtist', 'cel_year', 'cel_add', 'cel_action',
        ],
        'artist_row.phtml' => [
            'cel_play', 'getClassCover', 'getClassArtist', 'cel_add', 'cel_action',
        ],
        'playlist_row.phtml' => [
            'cel_play', 'getClassCover', 'cel_playlist', 'cel_add_list', 'cel_time', 'cel_action',
        ],
        'playlist_media_row.phtml' => [
            'cel_select', 'cel_play', 'getCoverClass', 'cel_title', 'cel_artist', 'cel_add', 'getTimeClass', 'cel_action', 'cel_drag',
        ],
        'label_row.phtml' => [
            'getCoverCellClass', 'cel_label', 'cel_action',
        ],
        'live_stream_row.phtml' => [
            'cel_play', 'getClassCover', 'cel_streamname', 'cel_add', 'cel_action',
        ],
    ];

    public function testEveryFoldedCellSaysWhatItHolds(): void
    {
        $violations = [];
        foreach (self::NEEDS_NO_LABEL as $template => $exempt) {
            foreach ($this->lines($template) as $line) {
                if (!str_contains($line, '<td ') || str_contains($line, 'class="mash_') || str_contains($line, 'class="grid_')) {
                    continue;
                }

                if (preg_match('#class="([^"]*)"#', $line, $matches) !== 1) {
                    continue;
                }

                if (array_intersect($this->cellTokens($matches[1]), $exempt) !== []) {
                    continue;
                }

                if (!str_contains($line, 'data-label=')) {
                    $violations[] = $template . ': ' . trim($matches[1]);
                }
            }
        }

        self::assertSame([], array_values(array_unique($violations)), 'folded cells with no `data-label` to name them');
    }

    /**
     * A class is either written out or built by an accessor, and both forms name the cell.
     *
     * @return list<string>
     */
    private function cellTokens(string $class): array
    {
        preg_match_all('#get[A-Za-z]+(?=\()#', $class, $accessors);
        $tokens = $accessors[0];

        foreach (preg_split('#\s+#', trim((string) preg_replace('#<\?php.*?\?>#s', ' ', $class))) ?: [] as $literal) {
            if ($literal !== '') {
                $tokens[] = $literal;
            }
        }

        return $tokens;
    }

    /**
     * A cell is opened on one line, and a `<?php ?>` block inside the tag makes any regex over the whole file
     * stop at the wrong `>`, so the scan works line by line.
     *
     * @return list<string>
     */
    private function lines(string $template): array
    {
        $path = __DIR__ . '/../../../resources/templates/' . $template;
        self::assertFileExists($path);

        return explode("\n", (string) file_get_contents($path));
    }
}
