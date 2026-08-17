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
use Ampache\Module\Catalog\GarbageCollector\CatalogGarbageCollectorInterface;
use Ampache\Module\Statistics\Stats;
use Override;

final class StatsRestoreCommand extends Command
{
    public function __construct(
        private readonly CatalogGarbageCollectorInterface $catalogGarbageCollector,
    ) {
        parent::__construct('cleanup:restoreStats', T_('Restore consolidated play history from the archive'));

        $this
            ->option('-e|--execute', T_('Disables dry-run'), 'boolval', false)
            ->usage('<bold>  cleanup:restoreStats</end> <comment> ## ' . T_('Show what would be restored (dry-run)') . '<eol/>');
    }

    public function execute(): void
    {
        if ($this->app() === null) {
            return;
        }

        $interactor = $this->io();
        $values     = $this->values();
        $result     = Stats::restore($values['execute'] === false);

        if ($result['rows'] === 0) {
            $interactor->white(T_('The play history archive is empty, there is nothing to restore'), true);

            return;
        }

        if ($result['executed']) {
            /* HINT: %1 archived row count, %2 rebuilt album/artist/podcast row count */
            $interactor->red(sprintf(T_('%d rows restored, %d album/artist/podcast rows rebuilt'), $result['rows'], $result['derived']), true);
            $interactor->white(T_('Updating counts'), true);
            // restoring history moves every play counter, so the repair pass is what puts them back in step
            $this->catalogGarbageCollector->collect();
        } else {
            $interactor->green(sprintf(T_('%d rows would be restored (dry-run)'), $result['rows']), true);
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
