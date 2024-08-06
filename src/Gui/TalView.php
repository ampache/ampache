<?php

declare(strict_types=0);

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
use PhpTal\PHPTAL;
use PhpTal\PhpTalInterface;

/**
 * Renders templates using the phptal rendering engine
 */
final class TalView implements TalViewInterface
{
    private TalFactoryInterface $talFactory;

    private ConfigContainerInterface $configContainer;

    private GuiFactoryInterface $guiFactory;

    private ?PhpTalInterface $engine = null;

    public function __construct(
        TalFactoryInterface $talFactory,
        ConfigContainerInterface $configContainer,
        GuiFactoryInterface $guiFactory
    ) {
        $this->talFactory      = $talFactory;
        $this->configContainer = $configContainer;
        $this->guiFactory      = $guiFactory;
    }

    public function render(): string
    {
        $engine = $this->getEngine();
        $engine->set('CONFIG', $this->guiFactory->createConfigViewAdapter());

        return $engine->execute();
    }

    public function setTemplate(string $templateFilePath): TalViewInterface
    {
        $this->getEngine()->setTemplate($templateFilePath);

        return $this;
    }

    public function setContext(string $key, $context): TalViewInterface
    {
        $this->getEngine()->set($key, $context);

        return $this;
    }

    private function getEngine(): PhpTalInterface
    {
        if ($this->engine === null) {
            $theme_path = sprintf(
                '%s/../../public/client/%s/templates/',
                __DIR__,
                $this->configContainer->getThemePath()
            );

            $this->engine = $this->talFactory->createPhpTal();
            $this->engine->setForceReparse($this->configContainer->isDebugMode());
            $this->engine->setTemplateRepository([
                realpath($theme_path),
                realpath(__DIR__ . '/../../resources/templates/'),
            ]);
            $this->engine->setTranslator($this->talFactory->createTalTranslationService());
            $this->engine->setOutputMode(PHPTAL::HTML5);
        }

        return $this->engine;
    }
}
