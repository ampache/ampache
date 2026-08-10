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
use DateTimeImmutable;
use Override;
use Traversable;

/**
 * A user's login history.
 *
 * Every column but the date is client-supplied, so the template escapes all of them.
 */
final class IpHistoryView extends AbstractView
{
    /**
     * @param Traversable<array{date: DateTimeImmutable, ip: string, agent: string, action: null|string}> $history
     */
    public function __construct(
        private readonly string $adminPath,
        private readonly int $userId,
        private readonly Traversable $history,
        private readonly bool $showingAll,
    ) {}

    /**
     * @return Traversable<array{date: DateTimeImmutable, ip: string, agent: string, action: null|string}>
     */
    public function getHistory(): Traversable
    {
        return $this->history;
    }

    public function getToggleLabel(): string
    {
        return $this->showingAll ? T_('Recent') : T_('Show All');
    }

    public function getToggleUrl(): string
    {
        return sprintf(
            '%s/users.php?action=show_ip_history&user_id=%d%s',
            $this->adminPath,
            $this->userId,
            $this->showingAll ? '' : '&all=1'
        );
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('ip_history.phtml');
    }
}
