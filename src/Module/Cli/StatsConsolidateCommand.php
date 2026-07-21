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
use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Statistics\Stats;

final class StatsConsolidateCommand extends Command
{
    public function __construct()
    {
        parent::__construct('cleanup:consolidateStats', T_('Consolidate old play history into summary counts'));

        $this
            ->option('-c|--count-type', T_('Only consolidate this count type (stream, skip, download)'))
            ->option('-e|--execute', T_('Disables dry-run'), 'boolval', false)
            ->usage('<bold>  cleanup:consolidateStats</end> <comment> ## ' . T_('Show what would be consolidated (dry-run)') . '<eol/>');
    }

    public function execute(): void
    {
        if ($this->app() === null) {
            return;
        }

        $interactor = $this->io();
        $values     = $this->values();
        $older_than = (int) AmpConfig::get(ConfigurationKeyEnum::STATS_CONSOLIDATE_THRESHOLD, 0);
        if ($older_than <= 0) {
            $interactor->error(T_('Stats consolidation is disabled. Set stats_consolidate_threshold in your ampache.cfg.php to the number of days of detailed play history to keep'), true);

            return;
        }

        $count_type = (in_array($values['countType'], ['stream', 'skip', 'download'], true))
            ? $values['countType']
            : null;
        $result = Stats::consolidate($older_than, $count_type, $values['execute'] === false);

        if ($result['executed']) {
            $interactor->red(sprintf(T_('%d rows consolidated into %d summary rows'), $result['rows'], $result['groups']), true);
            $interactor->white(T_('Run OPTIMIZE TABLE on object_count to reclaim disk space'), true);
        } else {
            /* HINT: %1 row count, %2 group count */
            $interactor->green(sprintf(T_('%d rows would be consolidated into %d summary rows (dry-run)'), $result['rows'], $result['groups']), true);
        }
    }

    protected function defaults(): self
    {
        $this->option('-h, --help', T_('Help'))->on([$this, 'showHelp']);

        $this->onExit(static fn($exitCode = 0) => exit($exitCode));

        return $this;
    }
}
