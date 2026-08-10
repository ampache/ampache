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
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\User;
use Override;

/**
 * The preferences page, which is one of four different things depending on the tab.
 *
 * The template this replaced opened its `<form>` inside a conditional and closed it outside every
 * conditional, so three of the four paths emitted a stray `</form>`; the account tab brings its own form
 * and the modules tab has none. `show_box_top()` was likewise inside the tab check while
 * `show_box_bottom()` was outside it, so no tab at all produced a box that only closed.
 */
final class PreferencesView extends AbstractView
{
    /**
     * @param array<string, mixed> $preferences
     */
    public function __construct(
        private readonly UiInterface $ui,
        private readonly string $webPath,
        private readonly string $fullname,
        private readonly array $preferences,
        private readonly string $tab,
        private readonly string $requestAction,
        private readonly int $userId,
        private readonly bool $isAdmin,
        private readonly bool $simpleUserMode,
    ) {}

    /**
     * The account tab brings its own form; simple mode gets a read-only version of the same fields.
     */
    public function getAccountView(User $client): AccountView
    {
        return new AccountView($client, $this->webPath, $this->simpleUserMode && !$this->isAdmin);
    }

    public function getActionUrl(): string
    {
        return $this->webPath . '/preferences.php?action=update_preferences';
    }

    public function getRequestAction(): string
    {
        return $this->requestAction;
    }

    public function getTab(): string
    {
        return $this->tab;
    }

    public function getTitle(): string
    {
        /* HINT: Username FullName */
        return sprintf(T_('Editing %s Preferences'), $this->e($this->fullname));
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * The modules tab has no preference form of its own; everything else does.
     */
    public function hasPreferenceForm(): bool
    {
        return $this->hasTab() && !$this->isAccountTab() && $this->tab !== 'modules';
    }

    /**
     * With no tab there is nothing to edit, so the page renders nothing rather than an empty box.
     */
    public function hasTab(): bool
    {
        return $this->tab !== '';
    }

    public function isAccountTab(): bool
    {
        return $this->tab === 'account';
    }

    /**
     * Only an admin edits someone else's preferences, so only then is the user id carried in the form.
     */
    public function isUserIdCarried(): bool
    {
        return $this->isAdmin;
    }

    /**
     * The box builder echoes rather than returns.
     */
    public function renderPreferenceBox(): string
    {
        ob_start();
        $this->ui->showPreferenceBox($this->preferences[$this->tab] ?? []);

        return (string) ob_get_clean();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('preferences.phtml');
    }
}
