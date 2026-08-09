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

namespace Ampache\Gui\Edit;

use Ampache\Gui\View\AbstractView;
use LogicException;
use Override;

/**
 * Shared plumbing for an edit form view.
 *
 * Renderers are shared services, so the previous context is put back after each render: a dialog that
 * rendered a second form of the same type would otherwise leave this one pointing at the wrong item.
 */
abstract class AbstractEditFormRenderer extends AbstractView implements EditFormRendererInterface
{
    private ?EditFormContext $context = null;

    #[Override]
    final public function renderForm(EditFormContext $context): string
    {
        $previous      = $this->context;
        $this->context = $context;

        try {
            return $this->render();
        } finally {
            $this->context = $previous;
        }
    }

    final protected function getContext(): EditFormContext
    {
        if ($this->context === null) {
            throw new LogicException(static::class . ' was rendered outside renderForm()');
        }

        return $this->context;
    }
}
