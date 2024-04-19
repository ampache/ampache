<?php

declare(strict_types=0);

/**
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

namespace Ampache\Application\Api\Ajax\Handler;

use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Share\ShareUiLinkRendererInterface;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\Playlist;
use Ampache\Module\Util\Ui;
use Ampache\Repository\LiveStreamRepositoryInterface;

final class BrowseAjaxHandler implements AjaxHandlerInterface
{
    private RequestParserInterface $requestParser;

    private ModelFactoryInterface $modelFactory;

    private LiveStreamRepositoryInterface $liveStreamRepository;

    private ShareUiLinkRendererInterface $shareUiLinkRenderer;

    public function __construct(
        RequestParserInterface $requestParser,
        ModelFactoryInterface $modelFactory,
        LiveStreamRepositoryInterface $liveStreamRepository,
        ShareUiLinkRendererInterface $shareUiLinkRenderer
    ) {
        $this->requestParser        = $requestParser;
        $this->modelFactory         = $modelFactory;
        $this->liveStreamRepository = $liveStreamRepository;
        $this->shareUiLinkRenderer  = $shareUiLinkRenderer;
    }

    public function handle(): void
    {
        if (!Core::is_session_started()) {
            session_start();
        }

        if (!defined('AJAX_INCLUDE')) {
            return;
        }

        debug_event('browse.ajax', 'Called for action: {' . Core::get_request('action') . '}', 5);
        $browse_id = $_REQUEST['browse_id'] ?? null;
        $browse    = $this->modelFactory->createBrowse($browse_id);

        if (array_key_exists('show_header', $_REQUEST) && $_REQUEST['show_header']) {
            $browse->set_show_header($_REQUEST['show_header'] == 'true');
        }

        $argument = false;
        if (array_key_exists('argument', $_REQUEST)) {
            $argument = scrub_in((string) $_REQUEST['argument']);
        }
        // hide some of the useless columns in a browse
        if (array_key_exists('hide', $_REQUEST)) {
            $argument = array('hide' => explode(',', scrub_in((string)$_REQUEST['hide'])));
        }

        $results = array();
        $action  = $this->requestParser->getFromRequest('action');

        // Switch on the actions
        switch ($action) {
            case 'browse':
                // data set by the fileter box (browse_filters.inc.php)
                if (isset($_REQUEST['key'])) {
                    // user typed a "start with" word
                    if (isset($_REQUEST['multi_alpha_filter'])) {
                        $browse->set_filter($_REQUEST['key'], $_REQUEST['multi_alpha_filter']);
                    }
                    // Checkbox unplayed
                    if (isset($_REQUEST['value'])) {
                        $value = (int)($_REQUEST['value'] ?? 0);
                        if ($_REQUEST['key'] == 'unplayed' && $browse->get_filter('unplayed')) {
                            $value = 0;
                        }
                        $browse->set_filter($_REQUEST['key'], $value);
                    }
                }
                // filter box Catalog select
                if (isset($_REQUEST['catalog'])) {
                    $browse->set_catalog($_SESSION['catalog']);
                }

                if (array_key_exists('sort', $_REQUEST) && !empty($_REQUEST['sort'])) {
                    // Set the new sort value
                    $browse->set_sort($_REQUEST['sort']);
                }

                if (array_key_exists('catalog_key', $_REQUEST) && $_REQUEST['catalog_key']) {
                    $browse->set_filter('catalog', $_REQUEST['catalog_key']);
                    $_SESSION['catalog'] = $_REQUEST['catalog_key'];
                } else {
                    $browse->set_filter('catalog', null);
                    $_SESSION['catalog'] = null;
                }
                $browse->set_catalog($_SESSION['catalog']);

                ob_start();
                $browse->show_objects(array(), $argument);
                $results[$browse->get_content_div()] = ob_get_clean();
                break;
            case 'set_sort':
                if (array_key_exists('sort', $_REQUEST) && !empty($_REQUEST['sort'])) {
                    $browse->set_sort($_REQUEST['sort']);
                }

                if (!$browse->is_use_pages()) {
                    $browse->set_start(0);
                }

                ob_start();
                $browse->show_objects(array(), $argument);
                $results[$browse->get_content_div()] = ob_get_clean();
                break;
            case 'toggle_tag':
                $type = $_SESSION['tagcloud_type'] ?? 'song';
                $browse->set_type($type);
                break;
            case 'delete_object':
                switch ($_REQUEST['type']) {
                    case 'playlist':
                        // Check the perms we need to on this
                        $playlist = new Playlist((int) Core::get_request('id'));
                        if (!$playlist->has_access()) {
                            return;
                        }

                        // Delete it!
                        $playlist->delete();
                        $key = 'playlist_row_' . $playlist->id;
                        break;
                    case 'smartplaylist':
                        $playlist = $this->modelFactory->createSearch((int) Core::get_request('id'));
                        if (!$playlist->has_access()) {
                            return;
                        }
                        $playlist->delete();
                        $key = 'smartplaylist_row_' . $playlist->id;
                        break;
                    case 'live_stream':
                        if (empty(Core::get_global('user')) || !Core::get_global('user')->has_access(75)) {
                            return;
                        }
                        $liveStreamId = (int) Core::get_request('id');
                        $liveStream   = $this->liveStreamRepository->findById($liveStreamId);
                        if ($liveStream !== null) {
                            $this->liveStreamRepository->delete($liveStream);
                        }
                        $key = 'live_stream_' . $liveStreamId;
                        break;
                    default:
                        return;
                } // end switch on type

                $results[$key] = '';

                break;
            case 'page':
                $browse->set_start((int)($_REQUEST['start'] ?? 0));
                ob_start();
                $browse->show_objects(array(), $argument);
                $results[$browse->get_content_div()] = ob_get_clean();
                break;
            case 'show_art':
                ob_start();
                $browse->show_objects(array(), $argument);
                $results[$browse->get_content_div()] = ob_get_clean();
                break;
            case 'get_filters':
                ob_start();
                require_once Ui::find_template('browse_filters.inc.php');
                $results['browse_filters'] = ob_get_clean();
                break;
            case 'hide_filters':
                ob_start();
                echo '';
                $results['browse_filters'] = ob_get_clean();
                break;
            case 'options':
                $option = $_REQUEST['option'] ?? '';
                $value  = $_REQUEST['value'] ?? '';
                switch ($option) {
                    case 'use_pages':
                        $value = ($value == 'true');
                        $browse->set_use_pages($value);
                        if ($value) {
                            $browse->set_start(0);
                        }
                        break;
                    case 'use_alpha':
                        $value = ($value == 'true');
                        $browse->set_use_alpha($value);
                        $browse->set_start(0);
                        if ($value) {
                            $browse->set_filter('regex_match', '^A');
                        } else {
                            $browse->set_filter('regex_not_match', '');
                        }
                        break;
                    case 'grid_view':
                        /**
                         * The `grid view` is implemented inverted, so apply an inverted logic.
                         * This ensures the `grid view` checkbox behaves as expected
                         */
                        $value = ($value == 'false');
                        $browse->set_grid_view($value);
                        break;
                    case 'limit':
                        $value = (int)$value;
                        if ($value > 0) {
                            $browse->set_offset($value);
                        }
                        break;
                    case 'custom':
                        $value = (int)$value;
                        $limit = $browse->get_offset();
                        if ($limit > 0 && $value > 0) {
                            $total = $browse->get_total();
                            $pages = ceil($total / $limit);

                            if ($value <= $pages) {
                                $offset = (int)(($value - 1) * $limit);
                                $browse->set_start($offset);
                            }
                        }
                        break;
                }

                ob_start();
                $browse->show_objects(array(), $argument);
                $results[$browse->get_content_div()] = ob_get_clean();
                break;
            case 'get_share_links':
                $object_type = Core::get_request('object_type');
                $object_id   = (int)filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT);

                if (InterfaceImplementationChecker::is_library_item($object_type) && $object_id > 0) {
                    echo $this->shareUiLinkRenderer->render($object_type, $object_id);

                    return;
                }
                break;
            default:
                $results['rfc3514'] = '0x1';
                break;
        } // switch on action;

        $browse->store();

        // We always do this
        echo (string) xoutput_from_array($results);
    }
}
