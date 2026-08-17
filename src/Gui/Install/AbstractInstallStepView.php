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

/**
 * Shared plumbing for the wizard steps: each is a whole document with its own header and footer.
 */
abstract class AbstractInstallStepView extends AbstractView
{
    public function __construct(
        private readonly string $webPath,
        private readonly string $charset,
        private readonly string $documentLanguage,
    ) {}

    final public function getCharset(): string
    {
        return $this->charset;
    }

    final public function getDocumentLanguage(): string
    {
        return $this->documentLanguage;
    }

    final public function getWebPath(): string
    {
        return $this->webPath;
    }

    final public function renderFooter(): string
    {
        return new InstallFooterView($this->webPath)->render();
    }

    final public function renderHeader(): string
    {
        return new InstallHeaderView($this->charset, $this->documentLanguage)->render();
    }
}
