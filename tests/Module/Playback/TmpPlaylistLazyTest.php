<?php

declare(strict_types=1);

namespace Ampache\Module\Playback;

use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\TmpPlaylistRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * A visitor who never queues anything should not get a tmp_playlist row.
 */
class TmpPlaylistLazyTest extends TestCase
{
    private ContainerInterface&MockObject $dic;

    /**  */
    private $oldDic;

    private TmpPlaylistRepositoryInterface&MockObject $repository;

    public function testAddObjectsQueuesTheSelectionInOneCall(): void
    {
        $this->repository->method('getRow')
            ->willReturn(['id' => 42, 'session' => 'a-session', 'type' => 'user', 'object_type' => 'song']);

        // one statement for the whole selection, not one per song
        $this->repository->expects(static::once())
            ->method('addItems')
            ->with(42, [21, 33], 'song');

        $this->repository->expects(static::never())
            ->method('addItem');

        new Tmp_Playlist(42)->add_objects([21, 33], LibraryItemEnum::SONG);
    }

    public function testFindFromSessionReturnsNullAndCreatesNothingWhenTheSessionHasNoQueue(): void
    {
        $this->repository->expects(static::once())
            ->method('findBySession')
            ->with('a-session')
            ->willReturn(null);

        // the whole point: no row is written for a passing visitor
        $this->repository->expects(static::never())
            ->method('create');

        self::assertNull(Tmp_Playlist::find_from_session('a-session'));
    }

    public function testFindFromSessionReturnsTheExistingQueue(): void
    {
        $this->repository->expects(static::once())
            ->method('findBySession')
            ->with('a-session')
            ->willReturn(42);

        $this->repository->expects(static::once())
            ->method('getRow')
            ->with(42)
            ->willReturn([
                'id' => 42,
                'session' => 'a-session',
                'type' => 'user',
                'object_type' => 'song',
            ]);

        $this->repository->expects(static::never())
            ->method('create');

        $playlist = Tmp_Playlist::find_from_session('a-session');

        self::assertInstanceOf(Tmp_Playlist::class, $playlist);
        self::assertSame(42, $playlist->id);
    }

    public function testGetFromSessionStillCreatesTheQueueWhenThereIsNone(): void
    {
        $this->repository->expects(static::once())
            ->method('findBySession')
            ->with('a-session')
            ->willReturn(null);

        $this->repository->expects(static::once())
            ->method('create')
            ->willReturn(7);

        $this->repository->expects(static::once())
            ->method('getRow')
            ->with(7)
            ->willReturn([
                'id' => 7,
                'session' => 'a-session',
                'type' => 'user',
                'object_type' => 'song',
            ]);

        self::assertSame(7, Tmp_Playlist::get_from_session('a-session')->id);
    }

    protected function setUp(): void
    {
        $this->repository = $this->createMock(TmpPlaylistRepositoryInterface::class);
        $this->dic        = $this->createMock(ContainerInterface::class);
        $this->dic->method('get')
            ->willReturnCallback(fn(string $id) => ($id === TmpPlaylistRepositoryInterface::class)
                ? $this->repository
                : null);

        // the model reaches its repository through the `global $dic` bridge
        $this->oldDic   = $GLOBALS['dic'] ?? null;
        $GLOBALS['dic'] = $this->dic;
    }

    protected function tearDown(): void
    {
        $GLOBALS['dic'] = $this->oldDic;
    }
}
