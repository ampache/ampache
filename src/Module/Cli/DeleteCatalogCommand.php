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
use Ampache\Module\Catalog\Catalog;
use Override;

final class DeleteCatalogCommand extends Command
{
    public function __construct()
    {
        parent::__construct('run:deleteCatalog', T_('Delete a media catalog'));

        $this
            ->argument('<catalogId>', T_('Catalog'))
            ->usage('<bold>  run:deleteCatalog 3</end> <comment> ## ' . T_('Delete the catalog with ID 3') . '</end><eol/>');
    }

    public function execute(
        string $catalogId,
    ): void {
        if ($this->app() === null) {
            return;
        }

        $interactor = $this->io();

        if (Catalog::delete((int) $catalogId)) {
            $interactor->ok(
                sprintf(T_('%s has been deleted'), $catalogId),
                true
            );
        } else {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            $interactor->error(
                sprintf(T_('Missing: %s'), $catalogId),
                true
            );
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
