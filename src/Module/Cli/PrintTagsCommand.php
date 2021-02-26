<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=1);

namespace Ampache\Module\Cli;

use Ahc\Cli\Input\Command;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Repository\Model\Catalog;
use Ampache\Module\Channel\ChannelRunnerInterface;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\VaInfo;
use Exception;

final class PrintTagsCommand extends Command
{
    private ConfigContainerInterface $configContainer;

    private ChannelRunnerInterface $channelRunner;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ChannelRunnerInterface $channelRunner
    ) {
        parent::__construct('print:tags', T_('Print file tags'));

        $this->configContainer = $configContainer;
        $this->channelRunner   = $channelRunner;

        $this
            ->argument('<filename>', T_('File Path'))
            ->usage('<bold>  print:tags</end> <comment><filename></end> ## ' . T_('Print tags') . '<eol/>');
    }

    public function execute(
        string $filename
    ): void {
        $io = $this->app()->io();

        $io->info(
            sprintf(T_('Reading: %s'), $filename),
            true
        );

        /* Attempt to figure out what catalog it comes from */
        $sql        = "SELECT `catalog`.`id` FROM `song` INNER JOIN `catalog` ON `song`.`catalog`=`catalog`.`id` WHERE `song`.`file` LIKE '%" . Dba::escape($filename) . "'";
        $db_results = Dba::read($sql);
        $results    = Dba::fetch_assoc($db_results);

        $catalog = Catalog::create_from_id($results['id']);

        $dir_pattern  = $catalog->sort_pattern;
        $file_pattern = $catalog->rename_pattern;

        $info = new VaInfo($filename, array('music'), '', '', '', $dir_pattern, $file_pattern);
        if (isset($dir_pattern) || isset($file_pattern)) {
            /* HINT: %1 $dir_pattern (e.g. %A/%Y %a), %2 $file_pattern (e.g. %d - %t) */
            $io->info(
                sprintf(T_('Using: %1$s AND %2$s for file pattern matching'), $dir_pattern, $file_pattern),
                true
            );
        }
        try {
            $info->get_info();
            $results         = $info->tags;
            $keys            = VaInfo::get_tag_type($results);
            $ampache_results = VaInfo::clean_tag_info($results, $keys, $filename);

            $io->info(
                T_('Raw results:'),
                true
            );
            $io->eol(2);

            print_r($info);

            $io->eol();
            $io->info('------------------------------------------------------------------', true);
            $io->info(
                sprintf(T_('Final results seen by Ampache using %s:'), implode(' + ', $keys)),
                true
            );
            $io->eol(2);
            print_r($ampache_results);
        } catch (Exception $error) {
            debug_event('print_tags', 'get_info exception: ' . $error->getMessage(), 1);
        }
    }
}
