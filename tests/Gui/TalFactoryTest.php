<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Gui;

use Ampache\Config\ConfigContainerInterface;
use Ampache\MockeryTestCase;
use Mockery\MockInterface;
use PhpTal\PHPTAL;

class TalFactoryTest extends MockeryTestCase
{
    /** @var MockInterface|ConfigContainerInterface|null */
    private MockInterface $configContainer;

    /** @var MockInterface|GuiFactoryInterface|null */
    private MockInterface $guiFactory;

    private TalFactory $subject;

    protected function setUp(): void
    {
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->guiFactory      = $this->mock(GuiFactoryInterface::class);

        $this->subject = new TalFactory(
            $this->configContainer,
            $this->guiFactory
        );
    }

    public function testCreatePhpTalReturnsInstance(): void
    {
        $this->assertInstanceOf(
            PHPTAL::class,
            $this->subject->createPhpTal()
        );
    }

    public function testCreateTalViewReturnsInstance(): void
    {
        $this->assertInstanceOf(
            TalView::class,
            $this->subject->createTalView()
        );
    }

    public function testCreateTalTranslationSeviceReturnsInstance(): void
    {
        $this->assertInstanceOf(
            TalTranslationService::class,
            $this->subject->createTalTranslationService()
        );
    }
}
