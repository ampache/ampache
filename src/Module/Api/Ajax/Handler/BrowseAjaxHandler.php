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

namespace Ampache\Module\Api\Ajax\Handler;

use Ampache\Config\AmpConfig;
use Ampache\Gui\Browse\BrowseFiltersView;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Database\Query\Query;
use Ampache\Module\Share\ShareUiLinkRendererInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\CatalogRepositoryInterface;
use Ampache\Repository\LiveStreamRepositoryInterface;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;

final readonly class BrowseAjaxHandler implements AjaxHandlerInterface
{
    public function __construct(
        private RequestParserInterface $requestParser,
        private ModelFactoryInterface $modelFactory,
        private BrowseFactoryInterface $browseFactory,
        private LiveStreamRepositoryInterface $liveStreamRepository,
        private ShareUiLinkRendererInterface $shareUiLinkRenderer,
        private CatalogRepositoryInterface $catalogRepository,
    ) {}

    public function handle(User $user): void
    {
        if (!defined('AJAX_INCLUDE')) {
            return;
        }

        $browse_id = (isset($_REQUEST['browse_id']))
            ? (int) $_REQUEST['browse_id']
            : null;
        $browse = $this->browseFactory->create($browse_id);

        debug_event('browse.ajax', 'Called for action: {' . Core::get_request('action') . '} id {' . $browse_id . '}', 5);
        if (array_key_exists('show_header', $_REQUEST) && $_REQUEST['show_header']) {
            $browse->set_show_header($_REQUEST['show_header'] == 'true');
        }

        $argument = false;
        if (array_key_exists('argument', $_REQUEST)) {
            $argument = scrub_in((string) $_REQUEST['argument']);
        }

        // hide some of the useless columns in a browse
        if (array_key_exists('hide', $_REQUEST)) {
            $argument = ['hide' => explode(',', scrub_in((string) $_REQUEST['hide']))];
        }

        // the filter box builds its own browse links, so it needs the argument this browse was rendered with
        $argument_param = Browse::get_argument_param($argument);

        $results = [];
        $action  = $this->requestParser->getFromRequest('action');

        // Switch on the actions
        switch ($action) {
            case 'browse':
                // Set the new sort value
                if (array_key_exists('sort', $_REQUEST) && !empty($_REQUEST['sort'])) {
                    $browse->set_sort($_REQUEST['sort']);
                }

                $filter = false;
                // data set by the filter box (browse_filters.inc.php)
                if (isset($_REQUEST['key'])) {
                    // user typed a word to match the name against
                    if (isset($_REQUEST['multi_alpha_filter'])) {
                        // the mode is remembered on the browse, so an empty box doesn't reset it on the next render
                        $browse->set_match_mode((string) ($_REQUEST['multi_alpha_filter_match'] ?? ''));
                        $match = (in_array($_REQUEST['multi_alpha_filter_match'] ?? '', Query::MATCH_MODES, true))
                            ? $browse->get_match_mode()
                            : (string) $_REQUEST['key'];

                        // only one of these may be set: every filter emits sql, so two would AND together
                        foreach (Query::MATCH_MODES as $unwanted) {
                            if ($unwanted !== $match) {
                                $browse->clear_filter($unwanted);
                            }
                        }

                        $value = (string) $_REQUEST['multi_alpha_filter'];
                        if ($value === '') {
                            $browse->clear_filter($match);
                        } else {
                            $browse->set_filter($match, $value);
                        }

                        $filter = true;
                    }

                    // Checkbox unplayed
                    if (isset($_REQUEST['value'])) {
                        $value = (int) $_REQUEST['value'];
                        if ($_REQUEST['key'] == 'unplayed' && $browse->get_filter('unplayed')) {
                            $value = 0;
                        }

                        $browse->set_filter($_REQUEST['key'], $value);
                        $filter = true;
                    }

                    // user filtered by genre or mood; the cloud names its value after the key it sends
                    $cloudValue = $_REQUEST['tag'] ?? $_REQUEST['mood'] ?? null;
                    if ($cloudValue !== null) {
                        $browse->set_filter($_REQUEST['key'], $cloudValue);
                        $filter = true;
                    }
                }

                // filter box Catalog select
                if (isset($_REQUEST['catalog'])) {
                    $browse->set_catalog($_SESSION['catalog'] ?? null);
                }

                if (array_key_exists('catalog_key', $_REQUEST) && $_REQUEST['catalog_key']) {
                    $_SESSION['catalog'] = (int) $_REQUEST['catalog_key'];
                    $browse->set_filter('catalog', $_REQUEST['catalog_key']);
                    $filter = true;
                } else {
                    $_SESSION['catalog'] = null;
                    if ((int) $browse->get_filter('catalog') !== 0) {
                        $browse->set_filter('catalog', null);
                        $filter = true;
                    }
                }

                $browse->set_catalog($_SESSION['catalog']);

                // a filter yields new objects; without one, null keeps the browse's saved list
                // (an empty array would overwrite it, emptying a browse that was handed its ids)
                $object_ids = ($filter)
                    ? $browse->get_objects()
                    : null;

                ob_start();
                $browse->show_objects($object_ids, $argument, true);
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
                $browse->show_objects(null, $argument, true);
                $results[$browse->get_content_div()] = ob_get_clean();
                break;
            case 'delete_object':
                if (check_http_referer() === false) {
                    return;
                }

                switch ($_REQUEST['type'] ?? '') {
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
                        $playlist = $this->modelFactory->createSmartlist((int) Core::get_request('id'));
                        if (!$playlist->has_access()) {
                            return;
                        }

                        $playlist->delete();
                        $key = 'smartplaylist_row_' . $playlist->id;
                        break;
                    case 'live_stream':
                        if (!$user->has_access(AccessLevelEnum::MANAGER)) {
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
                }

                $results[$key] = '';

                break;
            case 'page':
                $browse->set_start((int) ($_REQUEST['start'] ?? 0));
                ob_start();
                $browse->show_objects(null, $argument, true);
                $results[$browse->get_content_div()] = ob_get_clean();
                break;
            case 'show_art':
                ob_start();
                $browse->show_objects(null, $argument, true);
                $results[$browse->get_content_div()] = ob_get_clean();
                break;
            case 'get_filters':
                // the box shows a catalog picker, and picking one narrows the browse from here on
                $selectedCatalogId = (int) ($_SESSION['catalog'] ?? 0);
                if ($selectedCatalogId > 0) {
                    $browse->set_catalog($selectedCatalogId);
                }

                $results['browse_filters'] = (new BrowseFiltersView(
                    $browse,
                    Browse::get_allowed_filters($browse->get_type()),
                    $this->catalogRepository->getNamesByIds(User::get_user_catalogs($user->getId())),
                    $selectedCatalogId,
                    $argument_param
                ))->render();
                break;
            case 'hide_filters':
                ob_start();
                echo '';
                $results['browse_filters'] = ob_get_clean();
                break;
            case 'options':
                $filter = false;
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
                        if (!$value) {
                            $browse->set_filter('regex_not_match', '');
                            $filter = true;
                        }

                        break;
                    case 'grid_view':
                        /**
                         * The `grid view` is implemented inverted, so apply an inverted logic.
                         * This ensures the `grid view` checkbox behaves as expected
                         */
                        $object_type = $_REQUEST['object_type'] ?? '';
                        if (in_array($object_type, ['song', 'album', 'album_disk', 'artist', 'live_stream', 'playlist', 'smartplaylist', 'video', 'podcast', 'podcast_episode'])) {
                            $browse->set_type($object_type);
                        }

                        $value = ($value == 'true');
                        $browse->set_grid_view($value);
                        break;
                    case 'use_select':
                        $browse->set_use_select($value == 'true');
                        break;
                    case 'limit':
                        $value = (int) $value;
                        if ($value > 0) {
                            $browse->set_offset($value);
                        }

                        break;
                    case 'custom':
                        $value = (int) $value;
                        $limit = $browse->get_offset();
                        if ($limit > 0 && $value > 0) {
                            $total = $browse->get_total();
                            $pages = ceil($total / $limit);

                            if ($value <= $pages) {
                                $offset = ($value - 1) * $limit;
                                $browse->set_start($offset);
                            }
                        }

                        break;
                }

                // a filter yields new objects; without one, null keeps the browse's saved list
                // (an empty array would overwrite it, emptying a browse that was handed its ids)
                $object_ids = ($filter)
                    ? $browse->get_objects()
                    : null;

                ob_start();
                $browse->show_objects($object_ids, $argument, true);
                $results[$browse->get_content_div()] = ob_get_clean();
                break;
            case 'get_share_links':
                $object_type = LibraryItemEnum::tryFrom(Core::get_request('object_type')) ?? null;
                $object_id   = (int) filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT);

                if ($object_type !== null && $object_id > 0) {
                    header('Content-Type: text/html; charset=' . AmpConfig::get('site_charset', 'UTF-8'));
                    header_remove('Content-Disposition');
                    echo $this->shareUiLinkRenderer->render($object_type, $object_id);

                    return;
                }
        } // switch on action;

        $browse->store();

        // We always do this
        echo xoutput_from_array($results);
    }
}
