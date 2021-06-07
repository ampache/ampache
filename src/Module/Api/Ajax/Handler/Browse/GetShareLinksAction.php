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
 */

declare(strict_types=0);

namespace Ampache\Module\Api\Ajax\Handler\Browse;

use Ampache\Module\Share\ShareUiLinkRendererInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class GetShareLinksAction extends AbstractBrowseAction
{
    private ShareUiLinkRendererInterface $shareUiLinkRenderer;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        ShareUiLinkRendererInterface $shareUiLinkRenderer
    ) {
        parent::__construct($modelFactory);
        $this->shareUiLinkRenderer = $shareUiLinkRenderer;
    }

    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        $browse = $this->getBrowse();

        $object_type = Core::get_request('object_type');
        $object_id   = (int) filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT);

        if (InterfaceImplementationChecker::is_library_item($object_type) && $object_id > 0) {
            echo $this->shareUiLinkRenderer->render($object_type, $object_id);

            return [];
        }

        $browse->store();

        return [];
    }
}
