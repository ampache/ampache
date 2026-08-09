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

namespace Ampache\Gui\Install;

use Ampache\Gui\View\AbstractView;
use Ampache\Module\Util\EnvironmentInterface;
use Override;

/**
 * The standalone debug page, which is what a visitor gets when the config cannot be read.
 */
final class TestPageView extends AbstractView
{
    public function __construct(
        private readonly EnvironmentInterface $environment,
        private readonly string $configFile,
    ) {}

    public function isConfigReadable(): bool
    {
        return is_readable($this->configFile);
    }

    public function renderChecks(): string
    {
        return (new TestTableView($this->environment, $this->configFile))->render();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('install/test_page.phtml');
    }
}
