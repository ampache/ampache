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
use Ampache\Gui\System\ConfigViewAdapterInterface;
use Ampache\MockeryTestCase;
use Mockery\MockInterface;
use PhpTal\PHPTAL;
use PhpTal\PhpTalInterface;
use PhpTal\TranslationServiceInterface;

class TalViewTest extends MockeryTestCase
{
    /** @var MockInterface|TalFactoryInterface|null */
    private MockInterface $talFactory;

    /** @var MockInterface|ConfigContainerInterface|null */
    private MockInterface $configContainer;

    /** @var MockInterface|GuiFactoryInterface|null */
    private MockInterface $guiFactory;

    private TalView $subject;

    protected function setUp(): void
    {
        $this->talFactory      = $this->mock(TalFactoryInterface::class);
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->guiFactory      = $this->mock(GuiFactoryInterface::class);

        $this->subject = new TalView(
            $this->talFactory,
            $this->configContainer,
            $this->guiFactory
        );
    }

    public function testRenderRenders(): void
    {
        $engine             = $this->mock(PhpTalInterface::class);
        $configViewAdapter  = $this->mock(ConfigViewAdapterInterface::class);
        $translationService = $this->mock(TranslationServiceInterface::class);

        $themePath    = 'some-path';
        $debugMode    = false;
        $contextKey   = 'some-key';
        $context      = 'some-context';
        $templateFile = 'some-template-file';
        $output       = 'some-output';

        $this->guiFactory->shouldReceive('createConfigViewAdapter')
            ->withNoArgs()
            ->once()
            ->andReturn($configViewAdapter);

        $this->talFactory->shouldReceive('createTalTranslationService')
            ->withNoArgs()
            ->once()
            ->andReturn($translationService);
        $this->talFactory->shouldReceive('createPhpTal')
            ->withNoArgs()
            ->once()
            ->andReturn($engine);

        $this->configContainer->shouldReceive('isDebugMode')
            ->withNoArgs()
            ->once()
            ->andReturn($debugMode);
        $this->configContainer->shouldReceive('getThemePath')
            ->withNoArgs()
            ->once()
            ->andReturn($themePath);

        $engine->shouldReceive('set')
            ->with('CONFIG', $configViewAdapter)
            ->once();
        $engine->shouldReceive('set')
            ->with($contextKey, $context)
            ->once();
        $engine->shouldReceive('setForceReparse')
            ->with($debugMode)
            ->once();
        $engine->shouldReceive('setOutputMode')
            ->with(PHPTAL::HTML5)
            ->once();
        $engine->shouldReceive('setTranslator')
            ->with($translationService)
            ->once();
        $engine->shouldReceive('setTemplateRepository')
            ->with([
                false,
                realpath(__DIR__ . '/../../resources/templates/'),
            ])
            ->once();
        $engine->shouldReceive('setTemplate')
            ->with($templateFile)
            ->once();
        $engine->shouldReceive('execute')
            ->withNoArgs()
            ->once()
            ->andReturn($output);

        $this->assertSame(
            $output,
            $this->subject->setTemplate($templateFile)
                ->setContext($contextKey, $context)
                ->render()
        );
    }
}
