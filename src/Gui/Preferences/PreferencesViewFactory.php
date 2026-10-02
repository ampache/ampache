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

namespace Ampache\Gui\Preferences;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Repository\Model\User;
use Override;

final readonly class PreferencesViewFactory implements PreferencesViewFactoryInterface
{
    public function __construct(
        private ConfigContainerInterface $configContainer,
        private PreferenceCollector $collector,
        private PreferenceInputRenderer $renderer,
    ) {}

    #[Override]
    public function create(
        GuiGatekeeperInterface $gatekeeper,
        PreferenceSubject $subject,
        User $operator,
        string $tab,
    ): PreferencesView {
        // a bare `preferences.php` names no tab, and the old screen answered it with the first category
        [$tab, $items] = $this->collector->collectTab($subject, $operator, $tab);

        return new PreferencesView(
            $this->configContainer->getWebPath(),
            $subject,
            $items,
            $tab,
            $gatekeeper->mayAdminister(),
            (bool) $this->configContainer->get('simple_user_mode'),
            $this->renderer,
        );
    }
}
