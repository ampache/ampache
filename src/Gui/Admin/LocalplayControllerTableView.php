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

namespace Ampache\Gui\Admin;

use Ampache\Module\Playback\Localplay\LocalPlay;
use Override;

/**
 * The localplay controllers an install can turn on.
 */
final class LocalplayControllerTableView extends AbstractModuleTableView
{
    /**
     * @param list<string> $controllers
     */
    public function __construct(
        string $adminPath,
        private readonly array $controllers,
    ) {
        parent::__construct($adminPath);
    }

    #[Override]
    public function getColumns(): array
    {
        return [
            ['class' => 'cel_name', 'label' => T_('Name')],
            ['class' => 'cel_description', 'label' => T_('Description')],
            ['class' => 'cel_version', 'label' => T_('Version')],
            ['class' => 'cel_action', 'label' => T_('Action')],
        ];
    }

    #[Override]
    protected function buildRows(): array
    {
        $rows = [];
        foreach ($this->controllers as $controller) {
            $localplay = new LocalPlay($controller);
            if (!$localplay->player_loaded()) {
                continue;
            }

            $action = LocalPlay::is_enabled($controller)
                ? ['confirm_uninstall_localplay', T_('Disable')]
                : ['confirm_install_localplay', T_('Enable')];

            $rows[] = [
                $this->e(ucfirst($localplay->type)),
                $this->e($localplay->get_f_description()),
                $this->e($localplay->get_f_version()),
                sprintf(
                    '<a href="%s/modules.php?action=%s&type=%s">%s</a>',
                    $this->e($this->getAdminPath()),
                    $action[0],
                    urlencode($controller),
                    $this->e($action[1])
                ),
            ];
        }

        return $rows;
    }
}
