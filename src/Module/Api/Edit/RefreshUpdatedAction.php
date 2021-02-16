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

namespace Ampache\Module\Api\Edit;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Gui\GuiFactoryInterface;
use Ampache\Gui\TalFactoryInterface;
use Ampache\Repository\Model\database_object;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

final class RefreshUpdatedAction extends AbstractEditAction
{
    public const REQUEST_KEY = 'refresh_updated';

    private ResponseFactoryInterface $responseFactory;

    private StreamFactoryInterface $streamFactory;

    private TalFactoryInterface $talFactory;

    private GuiFactoryInterface $guiFactory;

    private UiInterface $ui;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        ConfigContainerInterface $configContainer,
        LoggerInterface $logger,
        TalFactoryInterface $talFactory,
        GuiFactoryInterface $guiFactory,
        UiInterface $ui
    ) {
        parent::__construct($configContainer, $logger);
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->talFactory      = $talFactory;
        $this->guiFactory      = $guiFactory;
        $this->ui              = $ui;
    }

    protected function handle(
        ServerRequestInterface $request,
        GuiGatekeeperInterface $gatekeeper,
        string $object_type,
        database_object $libitem,
        int $object_id
    ): ?ResponseInterface {
        /**
         * @todo Every editable itemtype will need some sort of special handling here
         */
        if ($object_type === 'song_row') {
            $results = preg_replace(
                '/<\/?html(.|\s)*?>/',
                '',
                $this->talFactory->createTalView()
                    ->setContext('BROWSE_ARGUMENT', '')
                    ->setContext('USER_IS_REGISTERED', true)
                    ->setContext('SONG', $this->guiFactory->createSongViewAdapter($gatekeeper, $libitem))
                    ->setContext('CONFIG', $this->guiFactory->createConfigViewAdapter())
                    ->setContext('IS_TABLE_VIEW', true)
                    ->setTemplate('song_row.xhtml')
                    ->render()
            );
        } else {
            ob_start();

            $this->ui->show(
                'show_' . $object_type . '.inc.php',
                [
                    'libitem' => $libitem,
                    'object_type' => $object_type,
                    'object_id' => $object_id,
                ]
            );

            $results = ob_get_contents();

            ob_end_clean();
        }

        return $this->responseFactory->createResponse()
            ->withBody(
                $this->streamFactory->createStream($results)
            );
    }
}
