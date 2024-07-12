<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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
use Ampache\Module\System\Dba;
use Ampache\Module\Util\UtilityFactoryInterface;
use Ampache\Module\Util\VaInfo;
use Ampache\Repository\Model\Catalog;
use Exception;

final class PrintTagsCommand extends Command
{
    private UtilityFactoryInterface $utilityFactory;

    public function __construct(
        UtilityFactoryInterface $utilityFactory
    ) {
        parent::__construct('print:tags', T_('Print file tags'));

        $this->utilityFactory = $utilityFactory;

        $this
            ->argument('<filename>', T_('File Path'))
            ->usage('<bold>  print:tags</end> <comment><filename></end> ## ' . T_('Print tags') . '<eol/>');
    }

    public function execute(
        string $filename
    ): void {
        $interactor = $this->app()->io();

        $interactor->info(
            sprintf(T_('Reading File: "%s"'), $filename),
            true
        );

        /* Attempt to figure out what catalog it comes from */
        $sql        = "SELECT `catalog`.`id` FROM `song` INNER JOIN `catalog` ON `song`.`catalog`=`catalog`.`id` WHERE `song`.`file` LIKE '%" . Dba::escape($filename) . "'";
        $db_results = Dba::read($sql);
        $row        = Dba::fetch_assoc($db_results);
        $catalog    = Catalog::create_from_id($row['id']);
        if ($catalog === null) {
            return;
        }

        $dir_pattern  = $catalog->sort_pattern;
        $file_pattern = $catalog->rename_pattern;

        $vainfo = $this->utilityFactory->createVaInfo(
            $filename,
            ['music'],
            '',
            '',
            (string) $dir_pattern,
            (string) $file_pattern
        );

        if (
            $dir_pattern !== '' ||
            $file_pattern !== ''
        ) {
            /* HINT: %1 $dir_pattern (e.g. %A/%Y %a), %2 $file_pattern (e.g. %d - %t) */
            $interactor->info(
                sprintf(T_('Using: %1$s AND %2$s for file pattern matching'), $dir_pattern, $file_pattern),
                true
            );
        }
        try {
            $vainfo->gather_tags();
            $results         = $vainfo->tags;
            $keys            = VaInfo::get_tag_type($results);
            $ampache_results = VaInfo::clean_tag_info($results, $keys, $filename);

            $interactor->info(
                T_('Raw results:'),
                true
            );
            $interactor->eol(2);

            print_r($vainfo);

            $interactor->eol();
            $interactor->info(
                '------------------------------------------------------------------',
                true
            );
            $interactor->info(
                sprintf(T_('Final results seen by Ampache using %s:'), implode(' + ', $keys)),
                true
            );
            $interactor->eol(2);
            print_r($ampache_results);
        } catch (Exception $error) {
            debug_event('print_tags', 'get_info exception: ' . $error->getMessage(), 1);
        }
    }
}
