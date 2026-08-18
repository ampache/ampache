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

namespace Ampache\Repository\Model;

use Ampache\MockeryTestCase;
use Ampache\Repository\TagRepositoryInterface;
use DI\Container;
use Override;

/**
 * `Tag::build_object_tag_cache()` fills the genre list for a whole page and `get_top_tags()` prefers that over a
 * read, so a write that leaves the entry in place has every later read in the same request answering with the
 * genres from before the edit.
 */
class TagCacheInvalidationTest extends MockeryTestCase
{
    private ?object $previousDic = null;

    public function testAnUnknownObjectTypeIsLeftAlone(): void
    {
        Tag::add_to_cache('object_tags_album', 42, [['id' => 7, 'name' => 'rock']]);

        $tag = new Tag(0);
        $tag->remove_map('not_a_library_item', 42);

        self::assertTrue(Tag::is_cached('object_tags_album', 42));
    }

    public function testMigrateForgetsBothObjects(): void
    {
        Tag::add_to_cache('object_tags_song', 1, [['id' => 7, 'name' => 'rock']]);
        Tag::add_to_cache('object_tags_song', 2, [['id' => 8, 'name' => 'jazz']]);
        Tag::add_to_cache('object_tags_song', 3, [['id' => 9, 'name' => 'folk']]);

        Tag::migrate('song', 1, 2);

        self::assertFalse(Tag::is_cached('object_tags_song', 1));
        self::assertFalse(Tag::is_cached('object_tags_song', 2));
        // an unrelated object keeps its entry, or the page loses its warm cache on every edit
        self::assertTrue(Tag::is_cached('object_tags_song', 3));
    }

    public function testRemoveMapForgetsTheObject(): void
    {
        Tag::add_to_cache('object_tags_album', 42, [['id' => 7, 'name' => 'rock']]);

        $tag = new Tag(0);
        $tag->remove_map('album', 42);

        self::assertFalse(Tag::is_cached('object_tags_album', 42));
    }

    #[Override]
    protected function setUp(): void
    {
        global $dic;

        $this->previousDic = $dic;

        $repository = $this->mock(TagRepositoryInterface::class);
        $repository->shouldIgnoreMissing();

        $container = $this->mock(Container::class);
        $container->shouldReceive('get')
            ->with(TagRepositoryInterface::class)
            ->andReturn($repository);

        $dic = $container;

        Tag::clear_cache();
    }

    #[Override]
    protected function tearDown(): void
    {
        global $dic;

        $dic = $this->previousDic;

        Tag::clear_cache();

        parent::tearDown();
    }
}
