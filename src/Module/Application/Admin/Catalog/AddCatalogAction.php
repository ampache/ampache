<?php

declare(strict_types=0);

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

namespace Ampache\Module\Application\Admin\Catalog;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\System\AmpError;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Catalog;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AddCatalogAction extends AbstractCatalogAction
{
    public const REQUEST_KEY = 'add_catalog';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private RequestParserInterface $requestParser;

    public function __construct(
        UiInterface $ui,
        ConfigContainerInterface $configContainer,
        RequestParserInterface $requestParser
    ) {
        parent::__construct($ui);
        $this->configContainer = $configContainer;
        $this->ui              = $ui;
        $this->requestParser   = $requestParser;
    }

    /**
     * @param int[] $catalogIds
     * @throws AccessDeniedException
     */
    protected function handle(
        ServerRequestInterface $request,
        array $catalogIds
    ): ?ResponseInterface {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true) {
            return null;
        }

        ob_end_flush();

        $body = (array)$request->getParsedBody();
        $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_SPECIAL_CHARS);
        if (
            empty($type) ||
            $type == 'none'
        ) {
            AmpError::add('general', T_('Please select a Catalog type'));
        }

        if (!strlen(htmlspecialchars($body['name'] ?? '', ENT_NOQUOTES))) {
            AmpError::add('general', T_('Please enter a Catalog name'));
        }

        if (!$this->requestParser->verifyForm('add_catalog')) {
            throw new AccessDeniedException();
        }

        // If an error hasn't occurred
        if (!AmpError::occurred()) {
            // mandatory catalog information
            $data = [
                'name' => $_POST['name'],
                'type' => $_POST['type'],
                'rename_pattern' => $_POST['rename_pattern'],
                'sort_pattern' => $_POST['sort_pattern'],
                'gather_media' => $_POST['gather_media'],
            ];

            // optional data depending on the catalog type
            if (array_key_exists('path', $_POST)) {
                $data['path'] = $_POST['path'];
            }
            if (array_key_exists('uri', $_POST)) {
                $data['uri'] = $_POST['uri'];
            }
            if (array_key_exists('username', $_POST)) {
                $data['username'] = $_POST['username'];
            }
            if (array_key_exists('password', $_POST)) {
                $data['password'] = $_POST['password'];
            }
            if (array_key_exists('library_name', $_POST)) {
                $data['library_name'] = $_POST['library_name'];
            }
            if (array_key_exists('server_uri', $_POST)) {
                $data['server_uri'] = $_POST['server_uri'];
            }
            if (array_key_exists('api_call_delay', $_POST)) {
                $data['api_call_delay'] = $_POST['api_call_delay'];
            }
            if (array_key_exists('beetsdb', $_POST)) {
                $data['beetsdb'] = $_POST['beetsdb'];
            }
            if (array_key_exists('apikey', $_POST)) {
                $data['apikey'] = $_POST['apikey'];
            }
            if (array_key_exists('secret', $_POST)) {
                $data['secret'] = $_POST['secret'];
            }
            if (array_key_exists('authtoken', $_POST)) {
                $data['authtoken'] = $_POST['authtoken'];
            }
            if (array_key_exists('getchunk', $_POST)) {
                $data['getchunk'] = $_POST['getchunk'];
            }
            $catalog_id = Catalog::create($data);

            if (!$catalog_id) {
                $this->ui->show('show_add_catalog.inc.php');

                return null;
            }

            // Add catalog to filter table
            Catalog::add_catalog_filter_group_map($catalog_id);

            $catalogIds[] = $catalog_id;
            catalog_worker('add_to_catalog', $catalogIds, $_POST);

            $this->ui->showConfirmation(
                T_('No Problem'),
                T_('The Catalog creation process has started'),
                sprintf('%s/catalog.php', $this->configContainer->getWebPath('/admin')),
                0,
                'confirmation',
                false
            );
        } else {
            $this->ui->show('show_add_catalog.inc.php');
        }

        return null;
    }
}
