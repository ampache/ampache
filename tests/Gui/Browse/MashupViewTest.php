<?php

declare(strict_types=1);

namespace Ampache\Gui\Browse;

use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\VideoRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The mashup boxes are never stored, so they must not take a tmp_browse row.
 */
class MashupViewTest extends TestCase
{
    private BrowseFactoryInterface&MockObject $browseFactory;

    public function testCreateBrowseAsksForAnUncachedBrowse(): void
    {
        $this->browseFactory = $this->createMock(BrowseFactoryInterface::class);
        $browse              = $this->createMock(Browse::class);

        // cached = false is what keeps Query out of tmp_browse
        $this->browseFactory->expects(static::once())
            ->method('create')
            ->with(null, false)
            ->willReturn($browse);

        $subject = new MashupView(
            'artist',
            $this->createMock(User::class),
            $this->browseFactory,
            $this->createMock(VideoRepositoryInterface::class),
            '/web',
            true
        );

        static::assertSame($browse, $subject->createBrowse());
    }
}
