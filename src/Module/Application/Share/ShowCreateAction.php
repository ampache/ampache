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

namespace Ampache\Module\Application\Share;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\User\PasswordGeneratorInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Video;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShowCreateAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show_create';

    public function __construct(
        private readonly RequestParserInterface $requestParser,
        private readonly ConfigContainerInterface $configContainer,
        private readonly UiInterface $ui,
        private readonly PasswordGeneratorInterface $passwordGenerator,
        private readonly ZipHandlerInterface $zipHandler,
        private readonly LibraryItemLoaderInterface $libraryItemLoader,
    ) {
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (!$this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHARE)) {
            throw new AccessDeniedException('Access Denied: sharing features are not enabled.');
        }

        $object_type = LibraryItemEnum::from($this->requestParser->getFromRequest('type'));
        $object_id   = (int) $this->requestParser->getFromRequest('id');

        $this->ui->showHeader();

        if (
            in_array($object_type, Share::VALID_TYPES, true)
            && !empty($object_id)
        ) {
            $object = $this->libraryItemLoader->load(
                $object_type,
                $object_id,
                [Song::class, Album::class, AlbumDisk::class, Playlist::class, Video::class]
            );

            if ($object !== null) {
                $object->format();

                $this->ui->show(
                    'show_add_share.inc.php',
                    [
                        'has_failed' => false,
                        'message' => '',
                        'object' => $object,
                        'object_type' => $object_type,
                        'token' => $this->passwordGenerator->generate_token(),
                        'isZipable' => $this->zipHandler->isZipable($object_type->value)
                    ]
                );
            }
        }
        $this->ui->showFooter();

        return null;
    }
}
