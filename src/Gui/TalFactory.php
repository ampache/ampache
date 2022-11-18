<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

declare(strict_types=1);

namespace Ampache\Gui;

use Ampache\Config\ConfigContainerInterface;
use PhpTal\PHPTAL;
use PhpTal\PhpTalInterface;
use PhpTal\TranslationServiceInterface;

final class TalFactory implements TalFactoryInterface
{
    private ConfigContainerInterface $configContainer;

    private GuiFactoryInterface $guiFactory;

    public function __construct(
        ConfigContainerInterface $configContainer,
        GuiFactoryInterface $guiFactory
    ) {
        $this->configContainer = $configContainer;
        $this->guiFactory      = $guiFactory;
    }

    public function createPhpTal(): PhpTalInterface
    {
        return new PHPTAL();
    }

    public function createTalView(): TalViewInterface
    {
        return new TalView(
            $this,
            $this->configContainer,
            $this->guiFactory
        );
    }

    public function createTalTranslationService(): TranslationServiceInterface
    {
        return new TalTranslationService();
    }
}
