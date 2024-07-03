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
use Ampache\Module\Util\UiInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

final class ShowEditObjectAction extends AbstractEditAction
{
    public const REQUEST_KEY = 'show_edit_object';

    private ResponseFactoryInterface $responseFactory;

    private StreamFactoryInterface $streamFactory;

    private UiInterface $ui;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        ConfigContainerInterface $configContainer,
        LoggerInterface $logger,
        UiInterface $ui
    ) {
        parent::__construct($configContainer, $logger);
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->ui              = $ui;
    }

    protected function handle(
        ServerRequestInterface $request,
        GuiGatekeeperInterface $gatekeeper,
        string $object_type,
        library_item $libitem,
        int $object_id
    ): ?ResponseInterface {
        ob_start();
        $users     = static::getUserRepository()->getValidArray();
        $users[-1] = T_('System');

        $this->ui->show(
            'show_edit_' . $object_type . '.inc.php',
            [
                'libitem' => $libitem,
                'users' => $users
            ]
        );

        $results = ob_get_contents();

        ob_end_clean();

        return $this->responseFactory->createResponse()
            ->withBody(
                $this->streamFactory->createStream($results)
            );
    }

    /**
     * @deprecated inject dependency
     */
    private static function getUserRepository(): UserRepositoryInterface
    {
        global $dic;

        return $dic->get(UserRepositoryInterface::class);
    }
}
