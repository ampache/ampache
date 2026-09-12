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

use Ampache\MockeryTestCase;
use Ampache\Module\Database\database_object;
use Ampache\Module\System\Dba;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\BookmarkRepositoryInterface;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\SongRepositoryInterface;
use SimpleXMLElement;

/**
 * The Subsonic schema types `Indexes` as an ordered sequence of shortcut, index then child. A client that reads
 * the document as it arrives, which is what the older ones do, has stopped looking for indexes by the time the
 * children start, and so offers no folder to browse into. JSON clients never noticed: key order means nothing there.
 */
class SubsonicIndexOrderTest extends MockeryTestCase
{
    /** @var array<int, array{object_id: int, object_type: LibraryItemEnum}> one folder and one loose file; a video keeps the song cache warm-up out of a test about order */
    private const array CHILDREN = [
        ['object_id' => 44951, 'object_type' => LibraryItemEnum::FOLDER],
        ['object_id' => 44952, 'object_type' => LibraryItemEnum::VIDEO],
    ];

    public function testTheLegacySerialiserPutsIndexesFirst(): void
    {
        $subject = new class ($this->mock(AlbumRepositoryInterface::class), $this->mock(FolderRepositoryInterface::class), $this->mock(SongRepositoryInterface::class), $this->fields()) extends Subsonic_Xml_Data {
            #[\Override]
            protected function _addChildObject(SimpleXMLElement $xml, array $child): void
            {
                $xml->addChild('child');
            }
        };

        self::assertSame(['index', 'child'], $this->emittedOrder($subject->addFolderIndexes($this->document(), self::CHILDREN)));
    }

    public function testTheSerialiserPutsIndexesBeforeChildren(): void
    {
        $subject = new class ($this->mock(AlbumRepositoryInterface::class), $this->mock(FolderRepositoryInterface::class), $this->fields(), $this->mock(SongRepositoryInterface::class)) extends OpenSubsonic_Xml_Data {
            #[\Override]
            protected function _addChildObject(SimpleXMLElement $xml, array $child): void
            {
                $xml->addChild('child');
            }
        };

        self::assertSame(['index', 'child'], $this->emittedOrder($subject->addFolderIndexes($this->document(), self::CHILDREN)));
    }

    protected function setUp(): void
    {
        parent::setUp();

        // priming the cache is what keeps `new Folder()` off the database, since get_info() reads it first.
        // The index it reads under is the escaped table name, which is empty with no connection to escape against
        database_object::add_to_cache((string) Dba::escape('folder'), 44951, [
            'id' => 44951,
            'name' => 'Alpha',
            'catalog' => 1,
            'parent' => null,
            'path_name' => '/music/alpha',
        ]);

        // same reason: the index entry asks whether the folder has art, which would otherwise go looking for it
        database_object::add_to_cache('art_has_db_folder', 44951, [0]);
    }

    private function document(): SimpleXMLElement
    {
        return new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><subsonic-response/>');
    }

    /**
     * The element names written under `indexes`, in the order they were written
     *
     * @return list<string>
     */
    private function emittedOrder(SimpleXMLElement $xml): array
    {
        $names = [];
        foreach ($xml->indexes->children() as $element) {
            $names[] = $element->getName();
        }

        return $names;
    }

    private function fields(): OpenSubsonic_Fields
    {
        return new OpenSubsonic_Fields(
            $this->mock(BookmarkRepositoryInterface::class),
            $this->mock(LabelRepositoryInterface::class)
        );
    }
}
