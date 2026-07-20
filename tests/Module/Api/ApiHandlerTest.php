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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Authorization\Check\NetworkCheckerInterface;
use Ampache\Repository\UserRepositoryInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * NOTE: only covers normalizeAction()/normalizeType(), which are pure
 * functions of their arguments -- handle() itself needs a real PSR-7
 * request/Gatekeeper flow that isn't in scope here.
 */
class ApiHandlerTest extends TestCase
{
    private ApiHandler $subject;

    /**
     * REST path suffix => the `task` the REST applications derive from it
     *
     * @return array<string, array{0: string}>
     */
    public static function catalogTaskProvider(): array
    {
        return [
            'add => add_to_catalog' => ['add'],
            'clean => clean_catalog' => ['clean'],
            'update => update_catalog' => ['update'],
            'verify => verify_catalog' => ['verify'],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function deletedAliasProvider(): array
    {
        return [
            'songs' => ['deleted-songs', 'deleted_songs'],
            'videos' => ['deleted-videos', 'deleted_videos'],
            'podcast episodes' => ['deleted-podcast-episodes', 'deleted_podcast_episodes'],
        ];
    }

    public static function shareableTypeProvider(): array
    {
        return [
            'album' => ['album'],
            'artist' => ['artist'],
            'playlist' => ['playlist'],
            'smartlist' => ['smartlist'],
            'podcast' => ['podcast'],
            'podcast_episode' => ['podcast_episode'],
            'song' => ['song'],
            'video' => ['video'],
        ];
    }

    public function testNormalizeActionLeavesShareUnchangedForNonShareableTypes(): void
    {
        static::assertSame(
            'share',
            $this->subject->normalizeAction('share', 'label', true)
        );
    }

    public function testNormalizeActionLeavesShareUnchangedWithoutType(): void
    {
        static::assertSame(
            'share',
            $this->subject->normalizeAction('share', null, true)
        );
    }

    public function testNormalizeActionLeavesUpdateUnchangedWithoutType(): void
    {
        // plain `update` (no type) is the system_update alias and must not be remapped
        static::assertSame(
            'update',
            $this->subject->normalizeAction('update', null, false)
        );
    }

    #[DataProvider(methodName: 'catalogTaskProvider')]
    public function testNormalizeActionRoutesCatalogTaskShortcutsToCatalogAction(string $action): void
    {
        static::assertSame(
            'catalog_action',
            $this->subject->normalizeAction($action, 'catalog', true)
        );
    }

    /**
     * The canonical REST path is `{type}/deleted`, but the flat hyphenated resource name is a plausible client
     * guess and every other hyphenated alias is accepted, so it has to resolve to the same action.
     */
    #[DataProvider(methodName: 'deletedAliasProvider')]
    public function testNormalizeActionRoutesHyphenatedDeletedAliases(string $action, string $expected): void
    {
        static::assertSame(
            $expected,
            $this->subject->normalizeAction($action, null, false)
        );
    }

    #[DataProvider(methodName: 'shareableTypeProvider')]
    public function testNormalizeActionRoutesRestShareToShareCreate(string $type): void
    {
        static::assertSame(
            'share_create',
            $this->subject->normalizeAction('share', $type, true)
        );
    }

    public function testNormalizeActionStillRoutesCatalogAddWithoutFilterToCatalogCreate(): void
    {
        static::assertSame(
            'catalog_create',
            $this->subject->normalizeAction('add', 'catalog', false)
        );
    }

    public function testNormalizeActionStillRoutesCatalogCreateToCatalogCreate(): void
    {
        static::assertSame(
            'catalog_create',
            $this->subject->normalizeAction('create', 'catalog', true)
        );
    }

    public function testNormalizeActionStillRoutesLegacyCreateToShareCreate(): void
    {
        // preserved in case anything still relies on the literal 'create' segment
        static::assertSame(
            'share_create',
            $this->subject->normalizeAction('create', 'album', true)
        );
    }

    protected function setUp(): void
    {
        $this->subject = new ApiHandler(
            $this->createMock(StreamFactoryInterface::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(ConfigContainerInterface::class),
            $this->createMock(NetworkCheckerInterface::class),
            $this->createMock(UserRepositoryInterface::class),
            $this->createMock(ContainerInterface::class),
        );
    }
}
