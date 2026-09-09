<?php

declare(strict_types=1);

namespace Ampache\Gui\Stats;

use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Only the section that stores its browse should get a tmp_browse row.
 */
class WrappedViewTest extends TestCase
{
    private Browse&MockObject $browse;
    private BrowseFactoryInterface&MockObject $browseFactory;

    public function testASectionThatIsNotStoredAsksForAnUncachedBrowse(): void
    {
        // cached = false is what keeps Query out of tmp_browse
        $this->browseFactory->expects(static::once())
            ->method('create')
            ->with(null, false)
            ->willReturn($this->browse);

        $this->browse->expects(static::never())
            ->method('store');

        $this->subject()->renderSection($this->section(false));
    }

    public function testAStoredSectionStillGetsACachedBrowse(): void
    {
        $this->browseFactory->expects(static::once())
            ->method('create')
            ->with(null, true)
            ->willReturn($this->browse);

        $this->browse->expects(static::once())
            ->method('store');

        $this->subject()->renderSection($this->section(true));
    }

    protected function setUp(): void
    {
        $this->browseFactory = $this->createMock(BrowseFactoryInterface::class);
        $this->browse        = $this->createMock(Browse::class);
    }

    /**
     * @return array{title: string, type: string, objectIds: array<int>, grid: bool, mashup: bool, store: bool}
     */
    private function section(bool $store): array
    {
        return [
            'title' => 'Artists',
            'type' => 'artist',
            'objectIds' => [1, 2, 3],
            'grid' => false,
            'mashup' => true,
            'store' => $store,
        ];
    }

    private function subject(): WrappedView
    {
        return new WrappedView($this->browseFactory, '2026', 0, '0', []);
    }
}
