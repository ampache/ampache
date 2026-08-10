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

namespace Ampache\Gui\Form;

use Ampache\Config\AmpConfig;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;

/**
 * Shared plumbing for the form pages.
 *
 * The submitted values a form re-displays are constructor arguments rather than `$_REQUEST` reads, so the
 * action decides what the form is seeded with and the template only prints it.
 */
abstract class AbstractFormView extends AbstractView
{
    public function __construct(
        private readonly string $webPath,
    ) {}

    /**
     * admin sits beside the web root rather than inside it, so it cannot be derived from the ui path
     */
    final public function getAdminPath(): string
    {
        return AmpConfig::get_web_path('/admin');
    }

    /**
     * The validation message for a field, already formatted as markup, or an empty string when it passed.
     */
    final public function getError(string $field): string
    {
        return AmpError::display($field);
    }

    /**
     * The hidden form token; registering it has the side effect of storing the token in the session.
     */
    final public function getFormToken(string $formName): string
    {
        return Core::form_register($formName);
    }

    final public function getWebPath(): string
    {
        return $this->webPath;
    }
}
