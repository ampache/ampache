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

declare(strict_types=0);

namespace Ampache\Module\Application\Share;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\User\PasswordGenerator;
use Ampache\Module\User\PasswordGeneratorInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Video;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShowCreateAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show_create';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private PasswordGeneratorInterface $passwordGenerator;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UiInterface $ui,
        PasswordGeneratorInterface $passwordGenerator,
        ModelFactoryInterface $modelFactory
    ) {
        $this->configContainer   = $configContainer;
        $this->ui                = $ui;
        $this->passwordGenerator = $passwordGenerator;
        $this->modelFactory      = $modelFactory;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (!$this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHARE)) {
            throw new AccessDeniedException('Access Denied: sharing features are not enabled.');
        }

        $queryParams = $request->getQueryParams();

        $type      = $queryParams['type'] ?? '';
        $object_id = $queryParams['id'] ?? null;

        $this->ui->showHeader();

        if (in_array($type, Share::ALLOWED_SHARE_TYPES) && $object_id !== null) {
            if (is_array($object_id)) {
                $object_id = $object_id[0];
            }

            /** @var ?Song|Album|Playlist|Video $object */
            $object = $this->modelFactory->mapObjectType($type, (int) $object_id);
            if ($object !== null && !$object->isNew()) {
                $object->format();

                $this->ui->show(
                    'show_add_share.inc.php',
                    [
                        'objectLink' => $object->f_link,
                        'secret' => $this->passwordGenerator->generate(PasswordGenerator::DEFAULT_LENGTH)
                    ]
                );
            }
        }
        $this->ui->showFooter();

        return null;
    }
}
