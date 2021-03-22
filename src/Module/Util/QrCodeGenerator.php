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

namespace Ampache\Module\Util;

use Endroid\QrCode\Encoding\Encoding;

final class QrCodeGenerator implements QrCodeGeneratorInterface
{
    private UtilityFactoryInterface $utilityFactory;

    public function __construct(
        UtilityFactoryInterface $utilityFactory
    ) {
        $this->utilityFactory = $utilityFactory;
    }

    /**
     * Creates a qrcode with the given size and content
     * Returns a string suitable for usage as data-uri in <img> tags
     */
    public function generate(
        string $content,
        int $size
    ): string {
        return $this->utilityFactory
            ->createQrCodeBuilder()
            ->data($content)
            ->encoding(new Encoding('UTF-8'))
            ->size($size)
            ->margin(0)
            ->build()
            ->getDataUri();
    }
}
