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

namespace Ampache\Module\Cli;

use Ahc\Cli\Input\Command;
use Ampache\Module\Util\BulkMailerInterface;
use Override;

final class AdminMailUsersCommand extends Command
{
    public function __construct(
        private readonly BulkMailerInterface $bulkMailer,
    ) {
        parent::__construct('admin:mailUsers', T_('Send an e-mail to a group of users'));

        $this
            ->option('-s|--subject', T_('Subject'), 'strval', '')
            ->option('-m|--message', T_('Message'), 'strval', '')
            ->argument('[group]', T_('User group') . " ('all', 'users', 'admins', 'inactive')", 'all')
            ->usage('<bold>  admin:mailUsers users --subject "Hi" --message "Server maintenance tonight"</end> <comment> ## ' . T_('Mail all standard users') . '</end><eol/>');
    }

    public function execute(
        string $group,
    ): void {
        if ($this->app() === null) {
            return;
        }

        $interactor = $this->io();
        $values     = $this->values();
        $subject    = (string) $values['subject'];
        $message    = (string) $values['message'];

        if ($subject === '' || $message === '') {
            $interactor->error(T_('A subject and message are required'), true);

            return;
        }

        // isEnabled() is false when mail is unconfigured or the instance is in demo mode, covering the DEMO_MODE guard
        if (!$this->bulkMailer->isEnabled()) {
            $interactor->error(T_('Mail is not enabled'), true);

            return;
        }

        if ($this->bulkMailer->sendToGroup($group, $subject, $message)) {
            $interactor->ok(sprintf(T_('Your e-mail has been sent to the %s group'), $group), true);
        } else {
            $interactor->error(T_('Your e-mail has not been sent'), true);
        }
    }

    #[Override]
    protected function defaults(): self
    {
        $this->option('-h, --help', T_('Help'))->on($this->showHelp(...));

        $this->onExit(static fn($exitCode = 0) => exit($exitCode));

        return $this;
    }
}
