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

namespace Ampache\Module\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * An id list handed to a formatter can name an object that no longer exists, because the rows it
 * came from outlive the object (`object_count`, `rating`, `user_flag`, a stale client id). A builder
 * that hydrates the model anyway emits an entry of empty fields under a real-looking id.
 *
 * The builders themselves need a database, so this reads their source instead: every hydration has
 * to be followed by an existence check on the object it just built.
 */
class OutputObjectValidityTest extends TestCase
{
    /**
     * Models whose constructor reads the database and yields an empty object for an unknown id
     */
    private const string MODELS = 'Album|AlbumDisk|Artist|Song|Video|Podcast_Episode|Live_Stream|Tag|Share|Playlist|Search|User|Collection';

    /**
     * @return array<string, array{0: string}>
     */
    public static function formatterProvider(): array
    {
        $names = [
            'Json4',
            'Json5',
            'Json6',
            'Json8',
            'Xml3',
            'Xml4',
            'Xml5',
            'Xml6',
            'Xml8',
            'Subsonic_Json',
            'Subsonic_Xml',
            'OpenSubsonic_Json',
            'OpenSubsonic_Xml',
        ];

        $formatters = [];
        foreach ($names as $name) {
            $formatters[$name . '_Data'] = [__DIR__ . '/../../../src/Module/Api/' . $name . '_Data.php'];
        }

        return $formatters;
    }

    #[DataProvider('formatterProvider')]
    public function testAnAbsentObjectSkipsItselfRatherThanEndingTheList(string $path): void
    {
        $source = (string) file_get_contents($path);

        // `break` here drops every remaining object as well, so one dead id truncates a whole page
        $truncating = preg_match_all('/->isNew\(\)\)\s*\{\s*break;/', $source);

        static::assertSame(
            0,
            $truncating,
            basename($path) . ' ends the loop on a missing object, which silently drops the objects after it'
        );
    }

    #[DataProvider('formatterProvider')]
    public function testEveryHydratedObjectIsCheckedForExistence(string $path): void
    {
        $source = (string) file_get_contents($path);

        $unguarded = [];

        /**
         * Only a hydration whose id came from the list being walked is checked. One built from a
         * property of the object being rendered — a message's sender, a share's playlist — is the
         * attribute of an entry rather than the entry itself, so skipping it would drop the parent.
         */
        preg_match_all(
            '/\$(\w+)\s*=\s*new (' . self::MODELS . ')\((?:\(int\) )?\$(?:\w+|\w+\[[^]]+\])\)/',
            $source,
            $matches,
            PREG_OFFSET_CAPTURE
        );

        foreach ($matches[1] as $index => $match) {
            [$variable, $offset] = $match;

            // the check belongs immediately after the hydration, before any field is read
            $following = substr($source, $offset, 400);
            if (!preg_match('/\$' . preg_quote($variable, '/') . '->isNew\(\)/', $following)) {
                $unguarded[] = sprintf(
                    'line %d: $%s = new %s(...)',
                    substr_count(substr($source, 0, $offset), "\n") + 1,
                    $variable,
                    $matches[2][$index][0]
                );
            }
        }

        static::assertSame(
            [],
            $unguarded,
            sprintf(
                "%s hydrates an object without checking it exists, so a deleted id is returned as an entry of empty fields:\n  %s",
                basename($path),
                implode("\n  ", $unguarded)
            )
        );
    }
}
