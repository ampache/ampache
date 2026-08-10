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

use Ampache\Module\Application\Admin\Access\Lib\AccessListItemInterface;
use Override;

/**
 * The edit-an-access-control-entry form.
 */
final class EditAccessFormView extends AbstractFormView
{
    public function __construct(
        string $webPath,
        private readonly AccessListItemInterface $access,
    ) {
        parent::__construct($webPath);
    }

    public function getAccess(): AccessListItemInterface
    {
        return $this->access;
    }

    /**
     * The attribute marking the entry's current access level, for the radio whose value is `$level`.
     */
    public function getLevelChecked(int $level): string
    {
        return ($this->access->getLevel() === $level) ? 'checked="checked"' : '';
    }

    /**
     * The attribute marking the entry's current type, for the option whose value is `$type`.
     */
    public function getTypeSelected(string $type): string
    {
        return ($this->access->getType() === $type) ? ' selected="selected"' : '';
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('form/edit_access.phtml');
    }
}
