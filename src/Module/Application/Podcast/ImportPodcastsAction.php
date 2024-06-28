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

namespace Ampache\Module\Application\Podcast;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Catalog\CatalogLoaderInterface;
use Ampache\Module\Catalog\Exception\CatalogLoadingException;
use Ampache\Module\Podcast\Exception\InvalidCatalogException;
use Ampache\Module\Podcast\Exchange\PodcastOpmlImporterInterface;
use Ampache\Module\System\AmpError;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Catalog;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Loads and imports podcasts
 */
final class ImportPodcastsAction implements ApplicationActionInterface
{
    /** @var list<string> */
    private const EXPECTED_MIME_TYPES = [
        'text/x-opml+xml',
        'text/xml'
    ];

    public const REQUEST_KEY = 'import_podcasts';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private RequestParserInterface $requestParser;

    private CatalogLoaderInterface $catalogLoader;

    private PodcastOpmlImporterInterface $podcastOpmlImporter;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UiInterface $ui,
        RequestParserInterface $requestParser,
        CatalogLoaderInterface $catalogLoader,
        PodcastOpmlImporterInterface $podcastOpmlImporter
    ) {
        $this->configContainer     = $configContainer;
        $this->ui                  = $ui;
        $this->requestParser       = $requestParser;
        $this->catalogLoader       = $catalogLoader;
        $this->podcastOpmlImporter = $podcastOpmlImporter;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::PODCAST) === false) {
            return null;
        }

        if (
            $gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER) === false ||
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true ||
            !$this->requestParser->verifyForm('import_podcasts')
        ) {
            throw new AccessDeniedException();
        }

        $data = (array)$request->getParsedBody();

        $catalogId = (int) ($data['catalog'] ?? 0);

        try {
            $catalog = $this->catalogLoader->getById($catalogId);

            $importedCount = $this->importPodcasts($request, $catalog);
        } catch (CatalogLoadingException $e) {
            AmpError::add('catalog', T_('Catalog not found'));

            $importedCount = 0;
        }

        $this->ui->showHeader();
        if (AmpError::occurred()) {
            $this->ui->show(
                'show_import_podcasts.inc.php',
                [
                    'catalogId' => (int)($data['catalog'] ?? 0),
                ]
            );
        } else {
            $this->ui->showConfirmation(
                T_('No Problem'),
                sprintf(T_('Podcast-import finished. Imported %d podcasts'), $importedCount),
                sprintf(
                    '%s/browse.php?action=podcast',
                    $this->configContainer->getWebPath()
                )
            );
        }
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }

    /**
     * Invokes the actual import
     */
    private function importPodcasts(
        ServerRequestInterface $request,
        Catalog $catalog
    ): int {
        /** @var null|UploadedFileInterface $file */
        $file = $request->getUploadedFiles()['import_file'] ?? null;

        if ($file === null) {
            AmpError::add('import_file', T_('File is invalid'));

            return 0;
        }

        if (!in_array($file->getClientMediaType(), self::EXPECTED_MIME_TYPES, true)) {
            AmpError::add('import_file', T_('File-type not recognized'));

            return 0;
        }

        try {
            return $this->podcastOpmlImporter->import($catalog, $file->getStream()->getContents());
        } catch (InvalidCatalogException $e) {
            AmpError::add('catalog', T_('Invalid catalog type'));

            return 0;
        }
    }
}
