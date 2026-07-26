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
use Ampache\Module\Catalog\CatalogLoaderInterface;
use Ampache\Module\Catalog\Exception\CatalogLoadingException;
use Ampache\Module\Catalog\Export\CatalogExportFactoryInterface;
use Ampache\Module\Catalog\Export\CatalogExportTypeEnum;
use Ampache\Repository\Model\Catalog;
use Override;

final class ExportCatalogCommand extends Command
{
    public function __construct(
        private readonly CatalogExportFactoryInterface $catalogExportFactory,
        private readonly CatalogLoaderInterface $catalogLoader,
    ) {
        parent::__construct('export:catalog', T_('Export catalog metadata to a file'));

        $this
            ->option('-c|--catalog', T_('Catalog ID') . ' (0 = ' . T_('All') . ')', 'intval', 0)
            ->argument('<file>', T_('Output file'))
            ->argument('[format]', T_('Export Format') . " ('csv', 'itunes')", 'csv')
            ->usage('<bold>  export:catalog /tmp/library.csv csv</end> <comment> ## ' . T_('Export all catalogs as CSV') . '</end><eol/>');
    }

    public function execute(
        string $file,
        string $format,
    ): void {
        if ($this->app() === null) {
            return;
        }

        $interactor   = $this->io();
        $catalogId    = (int) $this->values()['catalog'];
        $exportFormat = CatalogExportTypeEnum::tryFrom($format) ?? CatalogExportTypeEnum::CSV;

        $catalog = null;
        if ($catalogId > 0) {
            try {
                $catalog = $this->catalogLoader->getById($catalogId);
            } catch (CatalogLoadingException) {
                /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                $interactor->error(
                    sprintf(T_('Missing: %d'), $catalogId),
                    true
                );

                return;
            }
        }

        // The exporter echoes straight to php://output, so capture the buffer here and write it to the requested file
        set_time_limit(0);
        $exporter = $this->catalogExportFactory->createFromExportType($exportFormat);
        ob_start();
        $exporter->export($catalog instanceof Catalog ? $catalog : null);
        $data = (string) ob_get_clean();

        if (file_put_contents($file, $data) === false) {
            $interactor->error(
                sprintf(T_('Could not write to %s'), $file),
                true
            );

            return;
        }

        $interactor->ok(
            sprintf(T_('Exported catalog metadata to %s'), $file),
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
