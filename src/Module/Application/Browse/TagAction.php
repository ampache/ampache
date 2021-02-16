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

namespace Ampache\Module\Application\Browse;

use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Tag;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class TagAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'tag';

    private ModelFactoryInterface $modelFactory;

    private UiInterface $ui;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        UiInterface $ui
    ) {
        $this->modelFactory = $modelFactory;
        $this->ui           = $ui;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        session_start();

        $browse = $this->modelFactory->createBrowse();
        $browse->set_type(static::REQUEST_KEY);
        $browse->set_simple_browse(true);

        $this->ui->showHeader();

        // Browser is able to save page on current session. Only applied to main menus.
        $browse->set_update_session(true);

        // FIXME: This whole thing is ugly, even though it works.
        $browse->set_sort('count', 'ASC');
        // This one's a doozy
        $browse_type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 'artist';
        $browse->set_simple_browse(false);
        $browse->save_objects(Tag::get_tags($browse_type, 0, 'name')); // Should add a pager?
        $object_ids = $browse->get_saved();
        $keys       = array_keys($object_ids);
        Tag::build_cache($keys);

        $this->ui->showBoxTop(T_('Genres'), 'box box_tag_cloud');

        $browse2 = $this->modelFactory->createBrowse();
        $browse2->set_type($browse_type);
        $browse2->store();

        require_once Ui::find_template('show_tagcloud.inc.php');

        $this->ui->showBoxBottom();

        $type = $browse2->get_type();

        require_once Ui::find_template('browse_content.inc.php');

        $browse->store();

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
