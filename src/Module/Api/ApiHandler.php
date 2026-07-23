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

namespace Ampache\Module\Api;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Authentication\Gatekeeper;
use Ampache\Module\Api\Exception\ApiException;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Api8\Handshake8Method;
use Ampache\Module\Api\Method\LostPasswordMethod;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Method\PingMethod;
use Ampache\Module\Api\Method\RegisterMethod;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\NetworkCheckerInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\System\Session;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final class ApiHandler implements ApiHandlerInterface
{
    private static int $default = 6;
    private ConfigContainerInterface $configContainer;

    /** @var string[] */
    private array $deprecated = [
        'tag_albums',
        'tag_artists',
        'tag',
        'tag_songs',
        'tags',
    ];

    /** @var string[] */
    private array $deprecated8 = [
        'get_indexes',
        'playlist_add_song',
        'user_update',
    ];

    private ContainerInterface $dic;
    private LoggerInterface $logger;
    private NetworkCheckerInterface $networkChecker;
    private StreamFactoryInterface $streamFactory;
    private UserRepositoryInterface $userRepository;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        LoggerInterface $logger,
        ConfigContainerInterface $configContainer,
        NetworkCheckerInterface $networkChecker,
        UserRepositoryInterface $userRepository,
        ContainerInterface $dic,
    ) {
        $this->streamFactory   = $streamFactory;
        $this->logger          = $logger;
        $this->configContainer = $configContainer;
        $this->networkChecker  = $networkChecker;
        $this->dic             = $dic;
        $this->userRepository  = $userRepository;
    }

    /**
     * Fold the dashed REST spelling of a resource or action onto its canonical snake_case name
     *
     * This is the single rule that lets every REST path use dashes (`album-disks/{id}/songs`) without
     * each name needing its own alias in the maps above.
     */
    private static function dashesToUnderscores(string $value): string
    {
        return str_replace('-', '_', $value);
    }

    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ApiOutputInterface $output,
    ): ?ResponseInterface {
        $gatekeeper = new Gatekeeper(
            $this->userRepository,
            $request,
            $this->logger
        );
        // block html and visual output
        define('API', true);

        $input  = $request->getQueryParams();
        $action = $input['action'];
        if ($action == 'bad_request') {
            $this->logger->warning(
                'Bad API request, check your HTTP Method is correct for your action',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            return $response
                ->withStatus(403)
                ->withBody(
                    $this->streamFactory->createStream(
                        $output->error(
                            Api::DEFAULT_VERSION,
                            ErrorCodeEnum::ACCESS_CONTROL_NOT_ENABLED,
                            'Access Denied',
                            $action,
                            'system'
                        )
                    )
                );
        }
        $is_handshake = $action == Handshake8Method::ACTION;
        $is_ping      = $action == PingMethod::ACTION;
        $is_register  = $action == RegisterMethod::ACTION;
        $is_forgotten = $action == LostPasswordMethod::ACTION;
        $is_public    = ($is_handshake || $is_ping || $is_register || $is_forgotten);
        $header_auth  = false;
        if (!isset($input['auth'])) {
            $header_auth   = true;
            $input['auth'] = $gatekeeper->getAuth();
        }

        $api_format = $input['api_format'];
        $version    = (isset($input['version']))
            ? $input['version']
            : Api::DEFAULT_VERSION;

        $user = (!empty($input['auth']))
            ? $gatekeeper->getUser()
            : null;
        $userId      = $user->id ?? -1;
        $api_version = (int) Preference::get_by_user($userId, 'api_force_version');
        if (!in_array($api_version, Api::API_VERSIONS)) {
            $api_session = Session::get_api_version($input['auth']);
            $api_version = ($is_public || (isset($input['version']) && $header_auth))
                ? (int) substr((string) $version, 0, 1)
                : $api_session;

            // If you call api3 from json you don't get anything so change back to the latest version
            if ($api_format == 'json' && $api_version == 3) {
                $api_version = self::$default;
            }

            // roll up the version if you haven't enabled the older versions
            if ($api_version < 3) {
                $api_version = 3;
            }
            if ($api_version == 3 && !Preference::get_by_user($userId, 'api_enable_3')) {
                $api_version = 4;
            }
            if ($api_version == 4 && !Preference::get_by_user($userId, 'api_enable_4')) {
                $api_version = 5;
            }
            if ($api_version == 5 && !Preference::get_by_user($userId, 'api_enable_5')) {
                $api_version = 6;
            }
            if ($api_version == 6 && !Preference::get_by_user($userId, 'api_enable_6')) {
                $api_version = 8;
            }

            if (
                $api_version == 7
                || ($api_version == 8 && !Preference::get_by_user($userId, 'api_enable_8'))
                || $api_version > 8
            ) {
                $this->logger->warning(
                    'No API version available; check your options!',
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );

                return $response
                    ->withStatus(403)
                    ->withBody(
                        $this->streamFactory->createStream(
                            $output->error(
                                Api::DEFAULT_VERSION,
                                ErrorCodeEnum::ACCESS_CONTROL_NOT_ENABLED,
                                'Access Denied',
                                $action,
                                'system'
                            )
                        )
                    );
            }
        }

        /*
         * Create a simplified session for header authenticated sessions
         * If you are sending a handshake, then return a valid auth session.
         * If you are doing anything else, you hide the session behind an MD5 hash of the username
         */
        if (
            $header_auth
            && $user instanceof User
        ) {
            $data             = [];
            $data['username'] = $user->username;
            $data['value']    = $api_version;
            if ($is_handshake || $is_ping) {
                // for a handshake there needs to be a valid auth response (ping when sent needs one)
                if (
                    $input['auth'] !== md5((string) $user->username)
                    && !Session::read($input['auth'])
                ) {
                    $data['type']  = 'api';
                    $input['auth'] = Session::create($data);
                }
            } else {
                $data['type']   = 'header';
                $data['apikey'] = md5((string) $user->username);
                // Session might not exist or has expired
                if (!Session::read($data['apikey'])) {
                    Session::destroy($data['apikey']);
                    Session::create($data);
                }

                // Continue with the new session string to hide your header token
                $input['auth'] = $data['apikey'];
            }

            if (in_array($api_version, Api::API_VERSIONS)) {
                Session::write($input['auth'], $api_version, $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::PERPETUAL_API_SESSION));
            }
        }

        // If it's not a handshake then we can allow it to take up lots of time
        if (!$is_handshake) {
            set_time_limit(0);
        }

        // If we don't even have access control on then we can't use this!
        if (!$this->configContainer->get('access_control')) {
            ob_end_clean();

            $this->logger->warning(
                'Error Attempted to use the API with Access Control turned off',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            switch ($api_version) {
                case 3:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error(
                                $api_version,
                                501,
                                'Access Control not Enabled'
                            )
                        )
                    );
                case 4:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error(
                                $api_version,
                                501,
                                'Access Control not Enabled'
                            )
                        )
                    );
                case 5:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error(
                                $api_version,
                                ErrorCodeEnum::ACCESS_CONTROL_NOT_ENABLED,
                                'Access Denied',
                                $action,
                                'system'
                            )
                        )
                    );
                case 6:
                    return $response
                        ->withStatus(403)
                        ->withBody(
                            $this->streamFactory->createStream(
                                $output->error(
                                    $api_version,
                                    ErrorCodeEnum::ACCESS_CONTROL_NOT_ENABLED,
                                    'Access Denied',
                                    $action,
                                    'system'
                                )
                            )
                        );
                case 8:
                default:
                    return $response
                        ->withStatus(403)
                        ->withBody(
                            $this->streamFactory->createStream(
                                $output->error(
                                    $api_version,
                                    ErrorCodeEnum::ACCESS_CONTROL_NOT_ENABLED,
                                    'Access Denied',
                                    $action,
                                    'system'
                                )
                            )
                        );
            }
        }

        /**
         * Verify the existence of the Session they passed in we do allow them to
         * login via this interface so we do have an exception for action=login
         */
        if (
            !$is_public
            && (
                !$user instanceof User // User is required for non-public methods
                || (
                    !$header_auth
                    && $input['auth'] === md5((string) $user->username)
                ) // require header auth for simplified session
                || $gatekeeper->sessionExists($input['auth']) === false // no valid session
            )
        ) {
            $this->logger->warning(
                sprintf('Invalid Session attempt to API [%s]', $action),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            ob_end_clean();

            switch ($api_version) {
                case 3:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error(
                                $api_version,
                                401,
                                'Session Expired'
                            )
                        )
                    );
                case 4:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error(
                                $api_version,
                                401,
                                'Session Expired'
                            )
                        )
                    );
                case 5:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error(
                                $api_version,
                                ErrorCodeEnum::INVALID_HANDSHAKE,
                                'Session Expired',
                                $action,
                                'account'
                            )
                        )
                    );
                case 6:
                    return $response
                        ->withStatus(401)
                        ->withBody(
                            $this->streamFactory->createStream(
                                $output->error(
                                    $api_version,
                                    ErrorCodeEnum::INVALID_HANDSHAKE,
                                    'Session Expired',
                                    $action,
                                    'account'
                                )
                            )
                        );
                case 8:
                default:
                    return $response
                        ->withStatus(401)
                        ->withBody(
                            $this->streamFactory->createStream(
                                $output->error(
                                    $api_version,
                                    ErrorCodeEnum::INVALID_HANDSHAKE,
                                    'Session Expired',
                                    $action,
                                    'account'
                                )
                            )
                        );
            }
        }

        if (!$this->networkChecker->check(AccessTypeEnum::API, $userId, AccessLevelEnum::GUEST)) {
            $this->logger->warning(
                sprintf('Unauthorized access attempt to API [%s]', filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            ob_end_clean();

            switch ($api_version) {
                case 3:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error(
                                $api_version,
                                403,
                                'Unauthorized access attempt to API - ACL Error'
                            )
                        )
                    );
                case 4:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error(
                                $api_version,
                                403,
                                'Unauthorized access attempt to API - ACL Error'
                            )
                        )
                    );
                case 5:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error(
                                $api_version,
                                ErrorCodeEnum::FAILED_ACCESS_CHECK,
                                'Unauthorized access attempt to API - ACL Error',
                                $action,
                                'account'
                            )
                        )
                    );
                case 6:
                    return $response
                        ->withStatus(403)
                        ->withBody(
                            $this->streamFactory->createStream(
                                $output->error(
                                    $api_version,
                                    ErrorCodeEnum::FAILED_ACCESS_CHECK,
                                    'Unauthorized access attempt to API - ACL Error',
                                    $action,
                                    'account'
                                )
                            )
                        );
                case 8:
                default:
                    return $response
                        ->withStatus(403)
                        ->withBody(
                            $this->streamFactory->createStream(
                                $output->error(
                                    $api_version,
                                    ErrorCodeEnum::FAILED_ACCESS_CHECK,
                                    'Unauthorized access attempt to API - ACL Error',
                                    $action,
                                    'account'
                                )
                            )
                        );
            }
        }

        if (!$is_public) {
            /**
             * @todo get rid of implicit user registration and pass the user explicitly
             */
            Session::createGlobalUser($user);

            // make sure the prefs are set too
            Preference::init();
        }

        // Make sure beautiful url is disabled as it is not supported by most Ampache clients
        AmpConfig::set('stream_beautiful_url', false, true);

        // Retrieve the api method handler from the list of known methods
        switch ($api_version) {
            case 3:
                $handlerClassName = Api3::METHOD_LIST[$action] ?? null;
                if ($handlerClassName === null || $api_format == 'json') {
                    ob_end_clean();

                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error(
                                $api_version,
                                405,
                                'Invalid Request'
                            )
                        )
                    );
                }
                break;
            case 4:
                $handlerClassName = Api4::METHOD_LIST[$action] ?? null;
                if ($handlerClassName === null) {
                    ob_end_clean();

                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error(
                                $api_version,
                                405,
                                'Invalid Request'
                            )
                        )
                    );
                }
                break;
            case 5:
                if (in_array($action, $this->deprecated)) {
                    ob_end_clean();

                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error(
                                $api_version,
                                ErrorCodeEnum::DEPRECATED,
                                'Deprecated',
                                $action,
                                'removed'
                            )
                        )
                    );
                }
                $handlerClassName = Api5::METHOD_LIST[$action] ?? null;
                if ($handlerClassName === null) {
                    ob_end_clean();

                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error(
                                $api_version,
                                ErrorCodeEnum::MISSING,
                                'Invalid Request',
                                $action,
                                'system'
                            )
                        )
                    );
                }
                break;
            case 6:
                if (in_array($action, $this->deprecated)) {
                    ob_end_clean();

                    return $response
                        ->withStatus(410)
                        ->withBody(
                            $this->streamFactory->createStream(
                                $output->error(
                                    $api_version,
                                    ErrorCodeEnum::DEPRECATED,
                                    'Deprecated',
                                    $action,
                                    'removed'
                                )
                            )
                        );
                }
                $handlerClassName = Api6::METHOD_LIST[$action] ?? null;
                if ($handlerClassName === null) {
                    ob_end_clean();

                    return $response
                        ->withStatus(400)
                        ->withBody(
                            $this->streamFactory->createStream(
                                $output->error(
                                    $api_version,
                                    ErrorCodeEnum::MISSING,
                                    'Invalid Request',
                                    $action,
                                    'system'
                                )
                            )
                        );
                }
                break;
            case 8:
            default:
                if (in_array($action, $this->deprecated8)) {
                    ob_end_clean();

                    return $response
                        ->withStatus(410)
                        ->withBody(
                            $this->streamFactory->createStream(
                                $output->error(
                                    $api_version,
                                    ErrorCodeEnum::DEPRECATED,
                                    'Deprecated',
                                    $action,
                                    'removed'
                                )
                            )
                        );
                }
                $handlerClassName = Api::METHOD_LIST[$action] ?? null;
                if ($handlerClassName === null) {
                    ob_end_clean();

                    return $response
                        ->withStatus(400)
                        ->withBody(
                            $this->streamFactory->createStream(
                                $output->error(
                                    $api_version,
                                    ErrorCodeEnum::MISSING,
                                    'Invalid Request',
                                    $action,
                                    'system'
                                )
                            )
                        );
                }
        }

        return $this->_executeHandler(
            $gatekeeper,
            $api_version,
            $is_public,
            $action,
            $handlerClassName,
            $input,
            $user,
            $response,
            $output
        );
    }

    /**
     * REST action handling
     */
    public function normalizeAction(
        string $action,
        ?string $type,
        bool $hasFilter,
    ): string {
        // REST paths spell multi-word names with a dash (`podcast-episodes`, `now-playing`,
        // `album-disks`); RPC actions are snake_case. Converting first means only genuine renames
        // need an entry below -- a new dashed path never needs an alias adding here.
        $action = self::dashesToUnderscores($action);

        $action = match ($action) {
            'albums_songs' => 'album_songs',
            'artists_albums' => 'artist_albums',
            'artists_songs' => 'artist_songs',
            'fetch_info' => 'update_artist_info',
            'fetch_metadata' => 'get_external_metadata',
            'follow' => 'toggle_follow',
            'genres_albums' => 'genre_albums',
            'genres_artists' => 'genre_artists',
            'genres_songs' => 'genre_songs',
            'art' => 'get_art',
            'groups' => 'search_group',
            'labels_artists' => 'label_artists',
            'licenses_songs' => 'license_songs',
            'me' => 'user',
            'playlists_add' => 'playlist_add',
            'playlists_delete' => 'playlist_delete',
            'playlists_edit' => 'playlist_edit',
            'playlists_generate' => 'playlist_generate',
            'playlists_hash', 'hash' => 'playlist_hash',
            'playlists_songs' => 'playlist_songs',
            'similar', 'get_similar_artists', 'similar_artists', 'get_similar_songs', 'similar_songs' => 'get_similar',
            'smartlists_delete' => 'smartlist_delete',
            'smartlists_songs' => 'smartlist_songs',
            'songs_delete' => 'song_delete',
            'update_tags' => 'update_from_tags',
            'users_playlists' => 'user_playlists',
            'users_smartlists' => 'user_smartlists',
            default => $action,
        };

        if ($hasFilter) {
            $action = match ($action) {
                // `album-disks/{album_disk_id}` addresses one disk; `album-disks` alone is the
                // album-scoped listing, so the singular form only applies when an id is present
                'album_disks' => 'album_disk',
                'albums' => 'album',
                'artists' => 'artist',
                'disks' => 'disk',
                'bookmarks' => 'bookmark',
                'catalogs' => 'catalog',
                'genres' => 'genre',
                'labels' => 'label',
                'live_streams' => 'live_stream',
                'playlists' => 'playlist',
                'podcast_episodes' => 'podcast_episode',
                'podcasts' => 'podcast',
                'preferences' => 'user_preference',
                'searches' => 'search',
                'shares' => 'share',
                'smartlists' => 'smartlist',
                'songs' => 'song',
                'system_preferences' => 'system_preference',
                'users' => 'user',
                'videos' => 'video',
                default => $action
            };
        }

        if ($type !== null && $type !== '') {
            if ($type === 'catalog') {
                if ($action === 'create' || ($action === 'add' && !$hasFilter)) {
                    $action = 'catalog_create';
                }

                // `catalogs/{catalog_id}/(add|clean|update|verify)` are undocumented aliases of
                // `catalogs/{catalog_id}/action`; the matching task is derived from the path by
                // the REST applications. (`add` without a filter keeps its `catalog_create` meaning)
                if (
                    $hasFilter
                    && ($action === 'add' || $action === 'clean' || $action === 'update' || $action === 'verify')
                ) {
                    $action = 'catalog_action';
                }
            }

            if ($type === 'song' && $action === 'lyrics') {
                $action = 'get_lyrics';
            }
            if ($type === 'podcast' && $action === 'podcast_episode') {
                $action = 'podcast_episodes';
            }
            if ($action === 'preferences' && ($type === 'user' || $type === 'system')) {
                $action = $type . '_' . $action;
            }
            if ($action === 'folder' && $type === 'catalog') {
                $action = 'catalog_folder';
            }
            if ($action === 'remove' && $type === 'playlist') {
                $action = 'playlist_remove';
            }

            if (($action === 'create' || $action === 'share') && ($type === 'album' || $type === 'artist' || $type === 'playlist' || $type === 'smartlist' || $type === 'podcast' || $type === 'podcast_episode' || $type === 'song' || $type === 'video')) {
                $action = 'share_create';
            }
            if ($action === 'deleted' && ($type === 'podcast_episode' || $type === 'song' || $type === 'video')) {
                $action = $action . '_' . $type . 's';
            }

            if (
                $action === 'song' && ($type === 'playlist' || $type === 'smartlist' || $type === 'album' || $type === 'album_disk' || $type === 'artist' || $type === 'genre' || $type === 'license' || $type === 'get_similar')
                || $action === 'album' && ($type === 'artist' || $type === 'genre')
                || $action === 'artist' && ($type === 'genre' || $type === 'get_similar' || $type === 'label')
                || $action === 'disk' && $type === 'album'
            ) {
                $action = $type . '_' . $action . 's';
            }

            if ($type === 'user' && ($action === 'playlist' || $action === 'smartlist')) {
                $action = $type . '_' . $action . 's';
            }
            if (
                ($type === 'playlist' && ($action === 'create' || $action === 'delete' || $action === 'add' || $action === 'add_song' || $action === 'remove_song'))
                || ($type === 'smartlist' && $action === 'delete')
                || ($type === 'bookmark' && $action === 'create')
                || ($type === 'podcast' && $action === 'update')
                || ($type === 'song' && $action === 'tags')
            ) {
                $action = $type . '_' . $action;
            }
        }

        return $action;
    }

    /**
     * REST type handling
     */
    public function normalizeType(string $type): string
    {
        // see normalizeAction(): the dashed REST spelling is folded first, so only the plural forms
        // need an entry here
        return match (self::dashesToUnderscores($type)) {
            'album_artists' => 'album_artist',
            'album_disks' => 'album_disk',
            'albums' => 'album',
            'artists' => 'artist',
            'bookmarks' => 'bookmark',
            'catalogs' => 'catalog',
            'genres', 'tags' => 'genre',
            'labels' => 'label',
            'live_streams' => 'live_stream',
            'playlists' => 'playlist',
            'podcast_episodes' => 'podcast_episode',
            'podcasts' => 'podcast',
            'searches' => 'search',
            'shares' => 'share',
            'smartlists' => 'smartlist',
            'song_artists' => 'song_artist',
            'songs' => 'song',
            'users' => 'user',
            'videos' => 'video',
            default => self::dashesToUnderscores($type),
        };
    }

    /**
     * Run the default API handler with exception handling
     *
     * @param 3|4|5|6|8 $api_version resolved and validated against Api::API_VERSIONS by the caller
     */
    private function _executeHandler(
        Gatekeeper $gatekeeper,
        int $api_version,
        bool $is_public,
        string $action,
        string $handlerClassName,
        array $input,
        ?User $user,
        ResponseInterface $response,
        ApiOutputInterface $output,
    ): ?ResponseInterface {
        try {
            /**
             * Legacy (static) api methods only receive the input, so the resolved version is put
             * there for them. `ApiMessage::resolveVersion()` reads it back out. Methods
             * implementing MethodInterface are handed the version as an argument instead.
             *
             * @todo cleanup once every method implements MethodInterface
             */
            $input['api_version'] = $api_version;

            /**
             * This condition allows the `new` approach and the legacy one to co-exist.
             * After implementing the MethodInterface in all api methods, the condition will be removed
             *
             * @todo cleanup
             */
            $this->logger->notice(
                sprintf('API function [%s]', $handlerClassName),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            if (
                ($user instanceof User || $is_public)
                && $this->dic->has($handlerClassName)
                && $this->dic->get($handlerClassName) instanceof MethodInterface
            ) {
                /** @var MethodInterface $handler */
                $handler = $this->dic->get($handlerClassName);

                /**
                 * The public actions (handshake, ping, register, lost_password) resolve no user, but
                 * MethodInterface always hands one over. They are given the same anonymous user the
                 * version lookup above falls back to; none of them read it.
                 */
                $response = $handler->handle(
                    $gatekeeper,
                    $response,
                    $output,
                    $input,
                    $user ?? new User(-1),
                    $api_version
                );

                $gatekeeper->extendSession($input['auth']);

                return $response;
            }
            $params = [$input];

            /** @var callable $callback */
            $callback = [$handlerClassName, $action];

            if (!$is_public) {
                $params[] = $user;
            }

            call_user_func_array(
                $callback,
                $params
            );

            $gatekeeper->extendSession($input['auth']);

            return null;
        } catch (ApiException $error) {
            switch ($api_version) {
                case 3:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error(
                                $api_version,
                                $error->getCode(),
                                $error->getMessage()
                            )
                        )
                    );
                case 4:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error(
                                $api_version,
                                $error->getCode(),
                                $error->getMessage()
                            )
                        )
                    );
                case 5:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error(
                                $api_version,
                                $error->getCode(),
                                $error->getMessage(),
                                $action,
                                $error->getType()
                            )
                        )
                    );
                case 6:
                    // versions up to 6 always answered 200 and put the failure in the body; only
                    // version 8 and later report the failure in the http status as well
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error(
                                $api_version,
                                $error->getCode(),
                                $error->getMessage(),
                                $action,
                                $error->getType()
                            )
                        )
                    );
                case 8:
                default:
                    return $response
                        ->withStatus(Api::getHttpCode($error->getCode()))
                        ->withBody(
                            $this->streamFactory->createStream(
                                $output->error(
                                    $api_version,
                                    $error->getCode(),
                                    $error->getMessage(),
                                    $action,
                                    $error->getType()
                                )
                            )
                        );
            }
        } catch (Throwable $error) {
            $this->logger->error(
                $error->getMessage(),
                [
                    LegacyLogger::CONTEXT_TYPE => self::class,
                    'method' => $action
                ]
            );

            switch ($api_version) {
                case 3:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error(
                                $api_version,
                                405,
                                'Invalid Request'
                            )
                        )
                    );
                case 4:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error(
                                $api_version,
                                405,
                                'Invalid Request'
                            )
                        )
                    );
                case 5:
                    return $response->withBody(
                        $this->streamFactory->createStream(
                            $output->error(
                                $api_version,
                                ErrorCodeEnum::GENERIC_ERROR,
                                'Generic error',
                                $action,
                                'system'
                            )
                        )
                    );
                case 6:
                    return $response
                        ->withStatus(500)
                        ->withBody(
                            $this->streamFactory->createStream(
                                $output->error(
                                    $api_version,
                                    ErrorCodeEnum::GENERIC_ERROR,
                                    'Generic error',
                                    $action,
                                    'system'
                                )
                            )
                        );
                case 8:
                default:
                    return $response
                        ->withStatus(500)
                        ->withBody(
                            $this->streamFactory->createStream(
                                $output->error(
                                    $api_version,
                                    ErrorCodeEnum::GENERIC_ERROR,
                                    'Generic error',
                                    $action,
                                    'system'
                                )
                            )
                        );
            }
        }
    }
}
