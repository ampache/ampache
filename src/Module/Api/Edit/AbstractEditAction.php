<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\Api\Edit;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\database_object;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
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
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        // Post first
        $object_type = $_POST['type'] ?? filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS);
        $object_id   = (int) Core::get_get('id');
        if (empty($object_type)) {
            $object_type = $source_object_type = filter_input(
                INPUT_GET,
                'object_type',
                FILTER_SANITIZE_SPECIAL_CHARS
            );
        } else {
            $source_object_type = $object_type;
            $object_type        = implode('_', explode('_', $object_type, -1));
        }

        if (!InterfaceImplementationChecker::is_library_item($object_type) && !in_array($object_type, array('share', 'tag', 'tag_hidden'))) {
            $this->logger->warning(
                sprintf('Type `%d` is not based on an item library.', $object_type),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            return null;
        }

        $className = ObjectTypeToClassNameMapper::map($object_type);
        $this->logger->warning(
            $className,
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );
        $this->logger->warning(
            $object_id,
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );
        $libitem = new $className($object_id);
        if (method_exists($libitem, 'format')) {
            $libitem->format();
        }

        $level = '50';
        if ($libitem->get_user_owner() == Core::get_global('user')->id) {
            $level = '25';
        }
        if (Core::get_request('action') == 'show_edit_playlist') {
            $level = '25';
        }

        // Make sure they got them rights
        if (!Access::check('interface', (int) $level) || $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true) {
            echo (string) xoutput_from_array(array('rfc3514' => '0x1'));

            return null;
        }

        return $this->handle($request, $gatekeeper, $source_object_type, $libitem, $object_id);
    }

    abstract protected function handle(
        ServerRequestInterface $request,
        GuiGatekeeperInterface $gatekeeper,
        string $object_type,
        database_object $libitem,
        int $object_id
    ): ?ResponseInterface;
}
