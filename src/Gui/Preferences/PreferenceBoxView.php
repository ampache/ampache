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

use Ampache\Gui\View\AbstractView;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Util\UiInterface;
use Override;

/**
 * One category of preferences, as a table.
 *
 * Its subcategory heading spanned four columns whether or not the admin columns were there, and the
 * access level was picked by a five-case ladder assigning five locals.
 */
final class PreferenceBoxView extends AbstractView
{
    /**
     * @param array<string, mixed> $preferences
     */
    public function __construct(
        private readonly array $preferences,
        private readonly UiInterface $ui,
    ) {}

    /**
     * The heading spans whatever the table actually has.
     */
    public function getColumnCount(): int
    {
        return ($this->showAdminColumns()) ? 4 : 2;
    }

    public function getInput(string $name, mixed $value, ?string $type): string
    {
        ob_start();
        $this->ui->createPreferenceInput($name, $value, $type);

        return (string) ob_get_clean();
    }

    /**
     * @return array<int, string>
     */
    public function getLevels(): array
    {
        return [
            5 => T_('Guest'),
            25 => T_('User'),
            50 => T_('Content Manager'),
            75 => T_('Catalog Manager'),
            100 => T_('Admin'),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function getPreferences(): array
    {
        return $this->preferences['prefs'] ?? [];
    }

    public function getTitle(): string
    {
        return (string) $this->preferences['title'];
    }

    /**
     * The system category is shared by everyone, so it has nothing to apply to all or to gate per level.
     */
    public function showAdminColumns(): bool
    {
        return $this->getTitle() !== 'System'
            && Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)
            && ($_REQUEST['action'] ?? '') === 'admin';
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('preferences/preference_box.phtml');
    }
}
