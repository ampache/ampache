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
use Ampache\Gui\GuiFactoryInterface;
use Ampache\Gui\TalFactoryInterface;
use Ampache\Repository\Model\database_object;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

final class ShowEditPlaylistAction extends AbstractEditAction
{
    public const REQUEST_KEY = 'show_edit_playlist';

    private ResponseFactoryInterface $responseFactory;

    private StreamFactoryInterface $streamFactory;

    private AjaxUriRetrieverInterface $ajaxUriRetriever;

    private TalFactoryInterface $talFactory;

    private GuiFactoryInterface $guiFactory;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        ConfigContainerInterface $configContainer,
        LoggerInterface $logger,
        AjaxUriRetrieverInterface $ajaxUriRetriever,
        TalFactoryInterface $talFactory,
        GuiFactoryInterface $guiFactory,
        ModelFactoryInterface $modelFactory
    ) {
        parent::__construct($configContainer, $logger);
        $this->responseFactory  = $responseFactory;
        $this->streamFactory    = $streamFactory;
        $this->ajaxUriRetriever = $ajaxUriRetriever;
        $this->talFactory       = $talFactory;
        $this->guiFactory       = $guiFactory;
        $this->modelFactory     = $modelFactory;
    }

    protected function handle(
        ServerRequestInterface $request,
        GuiGatekeeperInterface $gatekeeper,
        string $object_type,
        database_object $libitem,
        int $object_id
    ): ?ResponseInterface {
        /**
         * Actually, object_id is not used - this is a design flaw.
         * This action allows to submit multiple ids but the abstract app
         * uses just one for internal checks. So we have to retrieve the object_ids here again
         * @todo FIXME Replace by some smart solution
         */
        $result = $this->talFactory
            ->createTalView()
            ->setTemplate('playlist/new_dialog.xhtml')
            ->setContext(
                'ADAPTER',
                $this->guiFactory->createNewPlaylistDialogAdapter(
                    $gatekeeper,
                    $object_type,
                    $request->getQueryParams()['id']
                )
            )
            ->render();

        return $this->responseFactory->createResponse()
            ->withBody(
                $this->streamFactory->createStream($result)
            );
    }
}
