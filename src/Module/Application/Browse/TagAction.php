<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\Application\Browse;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Gui\Browse\BrowseContentView;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\VideoRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class TagAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'tag';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private RequestParserInterface $requestParser,
        private BrowseFactoryInterface $browseFactory,
        private UiInterface $ui,
        private AjaxUriRetrieverInterface $ajaxUriRetriever,
        private VideoRepositoryInterface $videoRepository,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $this->ui->showHeader();
        // FIXME: This whole thing is ugly, even though it works.
        $countOrder   = $this->requestParser->getFromRequest('sort') ?: 'name';
        $request_type = $this->requestParser->getFromRequest('type');
        $browse_type  = (Browse::is_valid_type($request_type))
            ? $request_type
            : ($this->configContainer->get(ConfigurationKeyEnum::ALBUM_GROUP) ? 'album' : 'album_disk');
        if ($request_type !== $browse_type) {
            $_REQUEST['type'] = $browse_type;
        }

        $object_ids = ($browse_type === 'album_disk')
            ? Tag::get_tags('album', 0, $countOrder)
            : Tag::get_tags($browse_type, 0, $countOrder);

        $keys = array_keys($object_ids);
        Tag::build_cache($keys);

        $this->ui->showBoxTop(T_('Genres'), 'box box_tag_cloud');

        $browse = $this->browseFactory->create();
        $browse->set_type($browse_type);
        // the tagcloud and the genre form it renders are required into this scope, so their services are named here
        $ui               = $this->ui;
        $ajaxUriRetriever = $this->ajaxUriRetriever;
        $videoRepository  = $this->videoRepository;
        if ($request_type === 'tag_hidden') {
            require_once Ui::find_template('show_tagcloud_hidden.inc.php');

            $this->ui->showBoxBottom();
        } else {
            require_once Ui::find_template('show_tagcloud.inc.php');

            $this->ui->showBoxBottom();
            echo (new BrowseContentView($browse->get_content_div()))->render();
        }

        $browse->store();

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
