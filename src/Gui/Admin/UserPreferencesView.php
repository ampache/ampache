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

use Ampache\Gui\View\AbstractView;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\User;
use Override;

/**
 * An admin editing another user's preferences.
 */
final class UserPreferencesView extends AbstractView
{
    /**
     * @param array<int, array{name: string, description: string, value: mixed, ...}> $preferences
     */
    public function __construct(
        private readonly UiInterface $ui,
        private readonly string $webPath,
        private readonly User $client,
        private readonly array $preferences,
    ) {}

    public function getActionUrl(): string
    {
        return $this->webPath . '/preferences.php?action=admin_update_preferences';
    }

    public function getClientId(): int
    {
        return $this->client->getId();
    }

    /**
     * @return array<int, array{name: string, description: string, value: mixed, ...}>
     */
    public function getPreferences(): array
    {
        return $this->preferences;
    }

    public function getTitle(): string
    {
        /* HINT: Username FullName */
        return sprintf(T_('Editing %s Preferences'), $this->e($this->client->fullname));
    }

    /**
     * The input widget is built by the ui helper, which echoes rather than returns.
     */
    public function renderInput(string $name, mixed $value, ?string $type = null): string
    {
        ob_start();
        $this->ui->createPreferenceInput($name, $value, $type);

        return (string) ob_get_clean();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('user_preferences.phtml');
    }
}
