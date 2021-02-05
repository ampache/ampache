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
use Ampache\Module\Util\FileSystem\FileNameConverterInterface;

final class ConvertFileNamesCommand extends Command
{
    private ConfigContainerInterface $configContainer;

    private FileNameConverterInterface $fileNameCorrector;

    public function __construct(
        ConfigContainerInterface $configContainer,
        FileNameConverterInterface $fileNameCorrector
    ) {
        parent::__construct('run:convertFilenames', 'Converts filesnames using a charset');

        $this->configContainer   = $configContainer;
        $this->fileNameCorrector = $fileNameCorrector;

        $this
            ->option('-f|--fire', 'Enables `fire-and-forget`-mode (Disables prompting on rename)', 'boolval', false)
            ->option('-c|--charset', 'The destination charset', 'strval', iconv_get_encoding('output_encoding'))
            ->usage('<bold>  run:convertFilenames</end> <comment>-c utf8</end> ## Converts filesnames to utf8<eol/>');
    }

    public function execute(): void
    {
        $io     = $this->app()->io();
        $values = $this->values();

        if (!function_exists('iconv')) {
            $io->warn(T_('php-iconv is required for this functionality, quitting'), true);

            return;
        }

        $this->fileNameCorrector->convert(
            $io,
            $values['charset'],
            $values['fire']
        );
    }
}
