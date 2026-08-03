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

namespace Ampache\Module\Api\Output;

use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Json5_Data;
use Ampache\Module\Api\Json6_Data;
use Ampache\Module\Api\Json8_Data;
use Ampache\Module\Api\Xml4_Data;
use Ampache\Module\Api\Xml5_Data;
use Ampache\Module\Api\Xml6_Data;
use Ampache\Module\Api\Xml8_Data;

final class ApiOutputFactory implements ApiOutputFactoryInterface
{
    public function __construct(
        private Json4_Data $json4Data,
        private Json5_Data $json5Data,
        private Json6_Data $json6Data,
        private Json8_Data $json8Data,
        private Xml4_Data $xml4Data,
        private Xml5_Data $xml5Data,
        private Xml6_Data $xml6Data,
        private Xml8_Data $xml8Data,
    ) {}

    public function createJsonOutput(): ApiOutputInterface
    {
        return new JsonOutput($this->json4Data, $this->json5Data, $this->json6Data, $this->json8Data);
    }

    public function createXmlOutput(): ApiOutputInterface
    {
        return new XmlOutput($this->xml4Data, $this->xml5Data, $this->xml6Data, $this->xml8Data);
    }
}
