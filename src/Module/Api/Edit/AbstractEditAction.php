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

namespace Ampache\Module\Api\Edit;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractEditAction implements ApplicationActionInterface
{
    private ConfigContainerInterface $configContainer;

    private LoggerInterface $logger;

    public function __construct(
        ConfigContainerInterface $configContainer,
        LoggerInterface $logger
    ) {
        $this->configContainer = $configContainer;
        $this->logger          = $logger;
    }

    public function run(
        ServerRequestInterface $request,
        GuiGatekeeperInterface $gatekeeper
    ): ?ResponseInterface {
        $this->logger->debug(
            'Called for action: {' . Core::get_request('action') . '}',
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );

        // Post first
        $object_type = (string)($_POST['type'] ?? filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS));
        $object_id   = (int) Core::get_get('id');
        if (empty($object_type)) {
            $object_type = $source_object_type = (string)filter_input(
                INPUT_GET,
                'object_type',
                FILTER_SANITIZE_SPECIAL_CHARS
            );
        } else {
            $source_object_type = $object_type;
            $object_type        = implode('_', explode('_', $object_type, -1));
        }

        if (!InterfaceImplementationChecker::is_library_item($object_type) && !in_array($object_type, ['share', 'tag', 'tag_hidden'])) {
            $this->logger->warning(
                sprintf('Type `%d` is not based on an item library.', $object_type),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            return null;
        }

        $className = ObjectTypeToClassNameMapper::map($object_type);
        $this->logger->warning(
            $className,
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );
        $this->logger->warning(
            (string) $object_id,
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );
        /** @var library_item $libitem */
        $libitem = new $className($object_id);
        if (method_exists($libitem, 'format')) {
            $libitem->format();
        }

        $level = AccessLevelEnum::CONTENT_MANAGER;
        if (Core::get_global('user') instanceof User && $libitem->get_user_owner() == Core::get_global('user')->id) {
            $level = AccessLevelEnum::USER;
        }
        if (Core::get_request('action') == 'show_edit_playlist') {
            $level = AccessLevelEnum::USER;
        }

        // Make sure they got them rights
        if (!Access::check(AccessTypeEnum::INTERFACE, $level) || $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true) {
            return null;
        }

        return $this->handle($request, $gatekeeper, $source_object_type, $libitem, $object_id);
    }

    abstract protected function handle(
        ServerRequestInterface $request,
        GuiGatekeeperInterface $gatekeeper,
        string $object_type,
        library_item $libitem,
        int $object_id
    ): ?ResponseInterface;
}
