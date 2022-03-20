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
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\Share;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\User\PasswordGeneratorInterface;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class ShowCreateAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show_create';

    private RequestParserInterface $requestParser;

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private LoggerInterface $logger;

    private PasswordGeneratorInterface $passwordGenerator;

    public function __construct(
        RequestParserInterface $requestParser,
        ConfigContainerInterface $configContainer,
        UiInterface $ui,
        LoggerInterface $logger,
        PasswordGeneratorInterface $passwordGenerator
    ) {
        $this->requestParser     = $requestParser;
        $this->configContainer   = $configContainer;
        $this->ui                = $ui;
        $this->logger            = $logger;
        $this->passwordGenerator = $passwordGenerator;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (!$this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHARE)) {
            throw new AccessDeniedException('Access Denied: sharing features are not enabled.');
        }

        $this->ui->showHeader();

        $type = Share::format_type($this->requestParser->getFromRequest('type'));
        if (!empty($type) && !empty($_REQUEST['id'])) {
            $object_id = $this->requestParser->getFromRequest('id');
            if (is_array($object_id)) {
                $object_id = $object_id[0];
            }

            $class_name = ObjectTypeToClassNameMapper::map($type);
            $object     = new $class_name($object_id);
            if ($object->id) {
                $object->format();
                require_once Ui::find_template('show_add_share.inc.php');
            }
        }
        $this->ui->showFooter();

        return null;
    }
}
