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
 */

namespace Ampache\Repository\Model;

use Ampache\Repository\LabelRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class LabelTest extends TestCase
{
    private ContainerInterface&MockObject $dic;
    private LabelRepositoryInterface&MockObject $labelRepository;

    public function testANewLabelIsActiveLikeTheColumnDefault(): void
    {
        self::assertTrue((new Label())->active);
    }

    public function testCreateDefaultsToActiveWhenTheCallerOmitsIt(): void
    {
        $this->labelRepository->method('lookup')
            ->willReturn(0);

        $this->labelRepository->expects(static::once())
            ->method('persist')
            ->with(self::callback(static fn(Label $label): bool => $label->active));

        Label::create(['name' => 'some-name']);
    }

    public function testCreateHonoursAnExplicitlyInactiveLabel(): void
    {
        $this->labelRepository->method('lookup')
            ->willReturn(0);

        $this->labelRepository->expects(static::once())
            ->method('persist')
            ->with(self::callback(static fn(Label $label): bool => $label->active === false));

        Label::create(['name' => 'some-name', 'active' => '0']);
    }

    public function testCreateRefusesAnExistingName(): void
    {
        $this->labelRepository->expects(static::once())
            ->method('lookup')
            ->with('some-name')
            ->willReturn(21);

        $this->labelRepository->expects(static::never())
            ->method('persist');

        self::assertNull(Label::create(['name' => 'some-name']));
    }

    public function testGetArtistsDelegatesToTheRepositoryAndCaches(): void
    {
        $subject = new Label();

        $subject->id = 666;

        $this->labelRepository->expects(static::once())
            ->method('getArtists')
            ->with($subject)
            ->willReturn([1, 2]);

        self::assertSame([1, 2], $subject->get_artists());
        self::assertSame([1, 2], $subject->get_artists());
    }

    public function testMigrateMovesArtistAssociationsOnly(): void
    {
        $this->labelRepository->expects(static::once())
            ->method('migrateArtist')
            ->with(21, 33);

        Label::migrate('artist', 21, 33);
        Label::migrate('album', 21, 33);
    }

    public function testUpdateAppliesTheDataAndPersists(): void
    {
        $subject = new Label();

        $subject->id     = 666;
        $subject->active = false;

        $this->labelRepository->expects(static::once())
            ->method('lookup')
            ->with('some-name', 666)
            ->willReturn(0);

        $this->labelRepository->expects(static::once())
            ->method('persist')
            ->with($subject);

        self::assertSame(
            666,
            $subject->update([
                'name' => 'some-name',
                'category' => 'Some-Category',
                'summary' => 'some-summary',
                'website' => 'https%3A%2F%2Fsome-site',
                'active' => '1',
            ])
        );

        self::assertSame('some-name', $subject->name);
        self::assertSame('some-summary', $subject->summary);
        // the category is stored lower case regardless of how the caller spelled it
        self::assertSame('some-category', $subject->category);
        self::assertSame('https://some-site', $subject->website);
        self::assertTrue($subject->active);
    }

    public function testUpdateDiscardsAnInvalidWebsite(): void
    {
        $subject = new Label();

        $subject->id     = 666;
        $subject->active = true;

        $this->labelRepository->method('lookup')
            ->willReturn(0);

        $subject->update(['name' => 'some-name', 'website' => 'not-a-url']);

        self::assertNull($subject->website);
        // an absent `active` key leaves the current value alone
        self::assertTrue($subject->active);
    }

    public function testUpdateRejectsADuplicateName(): void
    {
        $subject = new Label();

        $subject->id = 666;

        $this->labelRepository->expects(static::once())
            ->method('lookup')
            ->willReturn(21);

        $this->labelRepository->expects(static::never())
            ->method('persist');

        self::assertNull($subject->update(['name' => 'some-name']));
    }

    protected function setUp(): void
    {
        $this->labelRepository = $this->createMock(LabelRepositoryInterface::class);
        $this->dic             = $this->createMock(ContainerInterface::class);

        $this->dic->method('get')
            ->with(LabelRepositoryInterface::class)
            ->willReturn($this->labelRepository);

        // the model reaches its repository through the `global $dic` bridge; phpunit.xml sets
        // backupGlobals so the real container is restored after every test
        $GLOBALS['dic'] = $this->dic;
    }
}
