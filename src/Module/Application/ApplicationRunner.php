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

namespace Ampache\Module\Application;

use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Application\Exception\ObjectNotFoundException;
use Ampache\Module\Authorization\GatekeeperFactoryInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\UiInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\ResponseEmitter;
use Throwable;

final class ApplicationRunner
{
    private ContainerInterface $dic;

    private LoggerInterface $logger;

    private GatekeeperFactoryInterface $gatekeeperFactory;

    private UiInterface $ui;

    public function __construct(
        ContainerInterface $dic,
        LoggerInterface $logger,
        GatekeeperFactoryInterface $gatekeeperFactory,
        UiInterface $ui
    ) {
        $this->dic               = $dic;
        $this->logger            = $logger;
        $this->gatekeeperFactory = $gatekeeperFactory;
        $this->ui                = $ui;
    }

    /**
     * @param ServerRequestInterface $request
     * @param array<string, string> $action_list A dict containing request keys and handler class names
     * @param string $default_action The request key for the default action
     */
    public function run(
        ServerRequestInterface $request,
        array $action_list,
        string $default_action
    ): void {
        $body        = (array)$request->getParsedBody();
        $action_name = htmlspecialchars($body['action'] ?? $request->getQueryParams()['action'] ?? '');

        if (array_key_exists($action_name, $action_list) === false) {
            $action_name = $default_action;
        }

        $handler_name = $action_list[$action_name] ?? '';

        try {
            /** @var ApplicationActionInterface $handler */
            $handler = $this->dic->get($handler_name);
        } catch (ContainerExceptionInterface) {
            $this->logger->critical(
                sprintf('No handler found for action "%s"', $action_name),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            return;
        }

        $this->logger->debug(
            sprintf('Found handler "%s" for action "%s"', $handler_name, $action_name),
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        try {
            $response = $handler->run(
                $request,
                $this->gatekeeperFactory->createGuiGatekeeper()
            );

            /**
             * Emit response if available.
             * This will become the default once the rendering of all actions got converted
             */
            if ($response !== null) {
                $this->dic->get(ResponseEmitter::class)->emit($response);
            }
        } catch (AccessDeniedException $error) {
            $message = $error->getMessage();

            $this->logger->warning(
                $message,
                [
                    LegacyLogger::CONTEXT_TYPE => sprintf(
                        '"%s" for "%s"',
                        __CLASS__,
                        get_class($handler)
                    )
                ]
            );

            $this->ui->accessDenied($message);

            return;
        } catch (ObjectNotFoundException $error) {
            $this->logger->warning(
                'Requested an object that does not exist',
                [
                    LegacyLogger::CONTEXT_TYPE => sprintf(
                        '"%s" for "%s"',
                        __CLASS__,
                        get_class($handler)
                    ),
                    'objectId' => $error->getObjectId()
                ]
            );

            $this->ui->showObjectNotFound();
        } catch (Throwable $error) {
            $this->logger->critical(
                $error->getMessage(),
                [
                    LegacyLogger::CONTEXT_TYPE => sprintf(
                        '%s:%d',
                        $error->getFile(),
                        $error->getLine()
                    )
                ]
            );
            /**
             * @todo Add a nice error page
             */
        }
    }
}
