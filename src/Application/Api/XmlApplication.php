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

namespace Ampache\Application\Api;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Api\ApiHandlerInterface;
use Ampache\Module\Api\Output\ApiOutputFactoryInterface;
use Ampache\Application\ApplicationInterface;

final class XmlApplication implements ApplicationInterface
{
    private ApiOutputFactoryInterface $apiOutputFactory;

    private ApiHandlerInterface $apiHandler;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        ApiOutputFactoryInterface $apiOutputFactory,
        ApiHandlerInterface $apiHandler,
        ConfigContainerInterface $configContainer
    ) {
        $this->apiOutputFactory = $apiOutputFactory;
        $this->apiHandler       = $apiHandler;
        $this->configContainer  = $configContainer;
    }

    public function run(): void
    {
        /* Set the correct headers */
        header(sprintf('Content-type: text/xml; charset=%s', $this->configContainer->get('site_charset')));
        header('Content-Disposition: attachment; filename=information.xml');

        $_GET['api_format'] = 'xml';

        $result = $this->apiHandler->handle(
            $this->apiOutputFactory->createXmlOutput()
        );

        if ($result !== null) {
            echo $result;
        }
    }
}
