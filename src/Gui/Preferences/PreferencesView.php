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
use Ampache\Repository\Model\User;
use Override;

/**
 * The preferences page: the same screen for the visitor's own preferences, the server's, or another account's
 */
final class PreferencesView extends AbstractView
{
    /** Tabs that carry their own form instead of a list of preferences, so no category matches them */
    public const string ACCOUNT_TAB = 'account';

    public const string QUICK_CONNECT_TAB = 'quickconnect';

    /**
     * @param list<PreferenceItem> $items the preferences of the tab being shown
     */
    public function __construct(
        private readonly string $webPath,
        private readonly PreferenceSubject $subject,
        private readonly array $items,
        private readonly string $tab,
        private readonly bool $isAdmin,
        private readonly bool $simpleUserMode,
        private readonly PreferenceInputRenderer $renderer,
    ) {}

    public function countAll(): int
    {
        return count($this->items);
    }

    /**
     * How many of the shown preferences no longer match what Ampache ships
     */
    public function countDiffering(): int
    {
        return count(array_filter(
            $this->items,
            static fn(PreferenceItem $item): bool => $item->differsFromShipped()
        ));
    }

    public function getAccountView(User $client): AccountView
    {
        return new AccountView($client, $this->webPath, $this->simpleUserMode && !$this->isAdmin);
    }

    /**
     * Another account's preferences post to the admin endpoint, which already guards and redirects.
     */
    public function getActionUrl(): string
    {
        return $this->webPath . '/preferences.php?action='
            . ($this->isOtherUser() ? 'admin_update_preferences' : 'update_preferences');
    }

    public function getBoxView(): PreferenceBoxView
    {
        return new PreferenceBoxView($this->items, $this->subject, $this->renderer);
    }

    /**
     * The export link, which needs `nohtml` or the global link interception swallows the download
     */
    public function getExportUrl(): string
    {
        return $this->webPath . '/preferences.php?action=export_preferences' . match (true) {
            $this->subject->isServer => '&method=admin',
            $this->isOtherUser() => '&user_id=' . $this->subject->userId,
            default => '',
        };
    }

    public function getQuickConnectView(): QuickConnectView
    {
        return new QuickConnectView($this->webPath);
    }

    /**
     * What the save posts back as `method`, which is how the write path knows it targets the server.
     */
    public function getRequestAction(): string
    {
        return ($this->subject->isServer) ? 'admin' : 'show';
    }

    /**
     * What the visitor is looking at, in their words: an account, or the server itself.
     */
    public function getSubjectKind(): string
    {
        return ($this->subject->isServer) ? T_('Server') : T_('Account');
    }

    /**
     * The server has no name of its own, so naming it twice would just repeat the word.
     */
    public function getSubjectName(): string
    {
        return ($this->subject->isServer) ? '' : $this->subject->label;
    }

    /**
     * What the visitor is told they are about to change, which is the part that goes wrong silently.
     */
    public function getSubjectWarning(): string
    {
        return match (true) {
            $this->subject->isServer => T_('Server values. Saving here changes what new accounts start with; existing accounts keep what they already have.'),
            $this->subject->isSelf => '',
            default => sprintf(/* HINT: Username */ T_('You are editing the preferences of %s'), $this->subject->label),
        };
    }

    public function getTab(): string
    {
        return $this->tab;
    }

    /**
     * The box renders its title with raw(), and a full name is whatever its owner typed.
     */
    public function getTitle(): string
    {
        /* HINT: Username FullName */
        return sprintf(T_('Editing %s Preferences'), $this->e($this->subject->label));
    }

    public function getUserId(): int
    {
        return $this->subject->userId;
    }

    public function hasPreferenceForm(): bool
    {
        return $this->items !== [] && !$this->isAccountTab() && !$this->isQuickConnectTab();
    }

    public function hasTab(): bool
    {
        return $this->tab !== '';
    }

    public function isAccountTab(): bool
    {
        return $this->tab === self::ACCOUNT_TAB;
    }

    public function isOtherUser(): bool
    {
        return !$this->subject->isSelf && !$this->subject->isServer;
    }

    public function isQuickConnectTab(): bool
    {
        return $this->tab === self::QUICK_CONNECT_TAB;
    }

    public function isServerSubject(): bool
    {
        return $this->subject->isServer;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('preferences.phtml');
    }
}
