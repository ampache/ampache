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
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\SongRepositoryInterface;
use Override;

final class CleanupEnableDisabledCommand extends Command
{
    public function __construct(
        private readonly SongRepositoryInterface $songRepository,
    ) {
        parent::__construct('cleanup:enableDisabled', T_('Re-enable all disabled songs'));

        $this
            ->usage('<bold>  cleanup:enableDisabled</end> <comment> ## ' . T_('Re-enable every disabled song') . '</end><eol/>');
    }

    public function execute(): void
    {
        if ($this->app() === null) {
            return;
        }

        $interactor = $this->io();

        // The shared model setter gates on MANAGER access; the CLI runs as the privileged system user so elevate it
        $user = Core::get_global('user');
        if ($user instanceof User) {
            $user->access = AccessLevelEnum::ADMIN->value;
        }

        $count = 0;
        foreach ($this->songRepository->getDisabled() as $song) {
            Song::update_enabled(true, $song->id);
            $count++;
        }

        $interactor->ok(
            sprintf(T_('Re-enabled %d songs'), $count),
            true
        );
    }

    #[Override]
    protected function defaults(): self
    {
        $this->option('-h, --help', T_('Help'))->on($this->showHelp(...));

        $this->onExit(static fn($exitCode = 0) => exit($exitCode));

        return $this;
    }
}
