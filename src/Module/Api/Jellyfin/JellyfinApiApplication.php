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

namespace Ampache\Module\Api\Jellyfin;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\ApiApplicationInterface;
use Ampache\Module\Api\Jellyfin\Method\Artist\ArtistsMethod;
use Ampache\Module\Api\Jellyfin\Method\Auth\AuthenticateByNameMethod;
use Ampache\Module\Api\Jellyfin\Method\Auth\AuthenticateWithQuickConnectMethod;
use Ampache\Module\Api\Jellyfin\Method\Auth\QuickConnectAuthorizeMethod;
use Ampache\Module\Api\Jellyfin\Method\Auth\QuickConnectConnectMethod;
use Ampache\Module\Api\Jellyfin\Method\Auth\QuickConnectEnabledMethod;
use Ampache\Module\Api\Jellyfin\Method\Auth\QuickConnectInitiateMethod;
use Ampache\Module\Api\Jellyfin\Method\Genre\MusicGenresMethod;
use Ampache\Module\Api\Jellyfin\Method\Image\ImageMethod;
use Ampache\Module\Api\Jellyfin\Method\Items\InstantMixMethod;
use Ampache\Module\Api\Jellyfin\Method\Items\ItemDeleteMethod;
use Ampache\Module\Api\Jellyfin\Method\Items\ItemMethod;
use Ampache\Module\Api\Jellyfin\Method\Items\ItemRefreshMethod;
use Ampache\Module\Api\Jellyfin\Method\Items\ItemsMethod;
use Ampache\Module\Api\Jellyfin\Method\JellyfinMethodInterface;
use Ampache\Module\Api\Jellyfin\Method\Library\LibraryRefreshMethod;
use Ampache\Module\Api\Jellyfin\Method\Library\VirtualFoldersMethod;
use Ampache\Module\Api\Jellyfin\Method\Playback\AudioStreamMethod;
use Ampache\Module\Api\Jellyfin\Method\Playback\PlaybackInfoMethod;
use Ampache\Module\Api\Jellyfin\Method\Playlist\CreatePlaylistMethod;
use Ampache\Module\Api\Jellyfin\Method\Playlist\PlaylistItemMoveMethod;
use Ampache\Module\Api\Jellyfin\Method\Playlist\PlaylistItemsMethod;
use Ampache\Module\Api\Jellyfin\Method\Playlist\PlaylistMethod;
use Ampache\Module\Api\Jellyfin\Method\Session\LogoutMethod;
use Ampache\Module\Api\Jellyfin\Method\Session\PlayingMethod;
use Ampache\Module\Api\Jellyfin\Method\Session\PlayingPingMethod;
use Ampache\Module\Api\Jellyfin\Method\Session\PlayingProgressMethod;
use Ampache\Module\Api\Jellyfin\Method\Session\PlayingStoppedMethod;
use Ampache\Module\Api\Jellyfin\Method\Session\SessionCapabilitiesMethod;
use Ampache\Module\Api\Jellyfin\Method\Similar\SimilarMethod;
use Ampache\Module\Api\Jellyfin\Method\Song\LyricsMethod;
use Ampache\Module\Api\Jellyfin\Method\System\SystemInfoMethod;
use Ampache\Module\Api\Jellyfin\Method\System\SystemInfoPublicMethod;
use Ampache\Module\Api\Jellyfin\Method\System\SystemPingMethod;
use Ampache\Module\Api\Jellyfin\Method\User\UserMethod;
use Ampache\Module\Api\Jellyfin\Method\UserData\FavoriteMethod;
use Ampache\Module\Api\Jellyfin\Method\UserData\PlayedMethod;
use Ampache\Module\Api\Jellyfin\Method\UserData\RatingMethod;
use Ampache\Module\Api\Jellyfin\Method\UserView\UserViewsMethod;
use Ampache\Module\Api\Jellyfin\Method\Web\WebRedirectMethod;
use Ampache\Module\System\Session;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Slim\ResponseEmitter;

/**
 * Front controller for the Jellyfin-compatible surface — an explicit method+path route map, not reflective
 * dispatch. `.htaccess` rewrites every sub-path into a `jf_path` query parameter this reads to route.
 */
final class JellyfinApiApplication implements ApiApplicationInterface
{
    /**
     * Spec 12.0.0 dropped these legacy `/Users/{userId}/...` paths in favour of the modern ones on the
     * right, but real clients still call them (confirmed against Finamp: it calls `/Users/{id}/Views`, not
     * `/UserViews`, even on a fresh install) — see the plan's §C.2. `{userId}` itself isn't validated
     * against anything: this surface only ever answers for the authenticated caller.
     *
     * @var array<string, string>
     */
    private const array LEGACY_USER_PATH_SUFFIXES = [
        'Views' => '/UserViews',
        'Items' => '/Items',
    ];

    /**
     * Parameterized routes, tried when no exact ROUTES entry matches. The named capture becomes a PSR-7
     * request attribute the handler reads back (e.g. `$request->getAttribute('itemId')`).
     *
     * @var list<array{0: string, 1: string, 2: class-string<JellyfinMethodInterface>}>
     */
    private const array PATTERN_ROUTES = [
        ['GET', '#^/Items/(?<itemId>[^/]+)/PlaybackInfo$#i', PlaybackInfoMethod::class],
        ['POST', '#^/Items/(?<itemId>[^/]+)/PlaybackInfo$#i', PlaybackInfoMethod::class],
        ['GET', '#^/Items/(?<itemId>[^/]+)/InstantMix$#i', InstantMixMethod::class],
        ['POST', '#^/Items/(?<itemId>[^/]+)/Refresh$#i', ItemRefreshMethod::class],
        ['GET', '#^/Items/(?<itemId>[^/]+)$#i', ItemMethod::class],
        ['DELETE', '#^/Items/(?<itemId>[^/]+)$#i', ItemDeleteMethod::class],
        ['GET', '#^/Audio/(?<itemId>[^/]+)/(?:stream|universal)$#i', AudioStreamMethod::class],
        ['HEAD', '#^/Audio/(?<itemId>[^/]+)/(?:stream|universal)$#i', AudioStreamMethod::class],
        ['GET', '#^/Items/(?<itemId>[^/]+)/File$#i', AudioStreamMethod::class],
        ['HEAD', '#^/Items/(?<itemId>[^/]+)/File$#i', AudioStreamMethod::class],
        ['GET', '#^/Items/(?<itemId>[^/]+)/Images/[^/]+(?:/[0-9]+)?$#i', ImageMethod::class],
        ['HEAD', '#^/Items/(?<itemId>[^/]+)/Images/[^/]+(?:/[0-9]+)?$#i', ImageMethod::class],
        ['POST', '#^/UserFavoriteItems/(?<itemId>[^/]+)$#i', FavoriteMethod::class],
        ['DELETE', '#^/UserFavoriteItems/(?<itemId>[^/]+)$#i', FavoriteMethod::class],
        ['POST', '#^/UserPlayedItems/(?<itemId>[^/]+)$#i', PlayedMethod::class],
        ['DELETE', '#^/UserPlayedItems/(?<itemId>[^/]+)$#i', PlayedMethod::class],
        ['POST', '#^/UserItems/(?<itemId>[^/]+)/Rating$#i', RatingMethod::class],
        ['DELETE', '#^/UserItems/(?<itemId>[^/]+)/Rating$#i', RatingMethod::class],
        ['POST', '#^/Playlists/(?<playlistId>[^/]+)/Items/(?<itemId>[^/]+)/Move/(?<newIndex>[0-9]+)$#i', PlaylistItemMoveMethod::class],
        ['GET', '#^/Playlists/(?<playlistId>[^/]+)/Items$#i', PlaylistItemsMethod::class],
        ['POST', '#^/Playlists/(?<playlistId>[^/]+)/Items$#i', PlaylistItemsMethod::class],
        ['DELETE', '#^/Playlists/(?<playlistId>[^/]+)/Items$#i', PlaylistItemsMethod::class],
        ['GET', '#^/Playlists/(?<playlistId>[^/]+)$#i', PlaylistMethod::class],
        ['POST', '#^/Playlists/(?<playlistId>[^/]+)$#i', PlaylistMethod::class],
        ['GET', '#^/Audio/(?<itemId>[^/]+)/Lyrics$#i', LyricsMethod::class],
        ['GET', '#^/Items/(?<itemId>[^/]+)/Similar$#i', SimilarMethod::class],
        ['GET', '#^/Artists/(?<itemId>[^/]+)/Similar$#i', SimilarMethod::class],
        ['GET', '#^/Albums/(?<itemId>[^/]+)/Similar$#i', SimilarMethod::class],
        ['GET', '#^/web/.*$#i', WebRedirectMethod::class],
    ];

    /** @var array<string, class-string<JellyfinMethodInterface>> */
    private const array ROUTES = [
        'GET /System/Ping' => SystemPingMethod::class,
        'POST /System/Ping' => SystemPingMethod::class,
        'GET /System/Info/Public' => SystemInfoPublicMethod::class,
        'GET /System/Info' => SystemInfoMethod::class,
        'POST /Users/AuthenticateByName' => AuthenticateByNameMethod::class,
        'GET /QuickConnect/Enabled' => QuickConnectEnabledMethod::class,
        'POST /QuickConnect/Initiate' => QuickConnectInitiateMethod::class,
        'GET /QuickConnect/Connect' => QuickConnectConnectMethod::class,
        'POST /QuickConnect/Authorize' => QuickConnectAuthorizeMethod::class,
        'POST /Users/AuthenticateWithQuickConnect' => AuthenticateWithQuickConnectMethod::class,
        'GET /UserViews' => UserViewsMethod::class,
        'GET /Items' => ItemsMethod::class,
        'GET /Users/Me' => UserMethod::class,
        'POST /Sessions/Logout' => LogoutMethod::class,
        'POST /Sessions/Capabilities' => SessionCapabilitiesMethod::class,
        'POST /Sessions/Capabilities/Full' => SessionCapabilitiesMethod::class,
        'POST /Sessions/Playing' => PlayingMethod::class,
        'POST /Sessions/Playing/Ping' => PlayingPingMethod::class,
        'POST /Sessions/Playing/Progress' => PlayingProgressMethod::class,
        'POST /Sessions/Playing/Stopped' => PlayingStoppedMethod::class,
        'GET /MusicGenres' => MusicGenresMethod::class,
        'GET /Genres' => MusicGenresMethod::class,
        'POST /Playlists' => CreatePlaylistMethod::class,
        'GET /Artists' => ArtistsMethod::class,
        'GET /Artists/AlbumArtists' => ArtistsMethod::class,
        'GET /Library/VirtualFolders' => VirtualFoldersMethod::class,
        'GET /web' => WebRedirectMethod::class,
        'POST /Library/Refresh' => LibraryRefreshMethod::class,
    ];

    public function __construct(
        private readonly JellyfinRequestAuthenticatorInterface $authenticator,
        private readonly ConfigContainerInterface $configContainer,
        private readonly ContainerInterface $dic,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly ServerRequestCreatorInterface $serverRequestCreator,
        private readonly ResponseEmitter $sapiEmitter,
        private readonly StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * Real Jellyfin routing (ASP.NET Core) is case-insensitive; ours isn't by default, so this is the
     * fallback — confirmed needed against real Symfonium traffic (`GET /system/ping`, lowercase).
     *
     * @return class-string<JellyfinMethodInterface>|null
     */
    private static function matchRouteCaseInsensitively(string $routeKey): ?string
    {
        $lowerRouteKey = strtolower($routeKey);
        foreach (self::ROUTES as $candidateKey => $handlerClass) {
            if (strtolower($candidateKey) === $lowerRouteKey) {
                return $handlerClass;
            }
        }

        return null;
    }

    public function run(): void
    {
        if (!$this->configContainer->getBool(ConfigurationKeyEnum::JELLYFIN_BACKEND_ENABLE)) {
            $this->emit(JellyfinResponse::serviceUnavailable('Jellyfin backend is disabled'));

            return;
        }

        $request  = $this->serverRequestCreator->fromGlobals();
        $method   = strtoupper($request->getMethod());
        $path     = '/' . ltrim((string) ($request->getQueryParams()['jf_path'] ?? ''), '/');
        $path     = rtrim($path, '/');
        if ($path === '') {
            $path = '/';
        }
        $path = $this->normalizeLegacyPath($method, $path);

        $routeKey     = $method . ' ' . $path;
        $handlerClass = self::ROUTES[$routeKey] ?? self::matchRouteCaseInsensitively($routeKey);

        if ($handlerClass === null) {
            [$handlerClass, $request] = $this->matchPatternRoute($method, $path, $request);
        }

        if ($handlerClass === null || !$this->dic->has($handlerClass)) {
            $this->emit(JellyfinResponse::notFound());

            return;
        }

        $handler = $this->dic->get($handlerClass);
        if (!$handler instanceof JellyfinMethodInterface) {
            $this->emit(JellyfinResponse::notFound());

            return;
        }

        $user = $this->authenticator->authenticate($request);
        // some model writes (e.g. Playlist::update()'s canWrite()) read this legacy global, not $user
        Session::createGlobalUser($user);

        $this->emit($handler->handle($request, $user));
    }

    private function emit(JellyfinResponse $response): void
    {
        if ($response->isAlreadySent()) {
            return;
        }

        $psrResponse = $this->responseFactory
            ->createResponse($response->status)
            ->withHeader('Content-Type', $response->contentType . '; charset=utf-8');

        foreach ($response->headers as $name => $value) {
            $psrResponse = $psrResponse->withHeader($name, $value);
        }

        if ($response->body !== null) {
            $psrResponse = $psrResponse->withBody(
                $this->streamFactory->createStream((string) json_encode($response->body)),
            );
        }

        $this->sapiEmitter->emit($psrResponse);
    }

    /**
     * @return array{0: class-string<JellyfinMethodInterface>|null, 1: ServerRequestInterface}
     */
    private function matchPatternRoute(string $method, string $path, ServerRequestInterface $request): array
    {
        foreach (self::PATTERN_ROUTES as [$routeMethod, $pattern, $handlerClass]) {
            if ($routeMethod !== $method) {
                continue;
            }
            if (preg_match($pattern, $path, $matches) === 1) {
                foreach ($matches as $name => $value) {
                    if (is_string($name)) {
                        $request = $request->withAttribute($name, $value);
                    }
                }

                return [$handlerClass, $request];
            }
        }

        return [null, $request];
    }

    private function normalizeLegacyPath(string $method, string $path): string
    {
        if (preg_match('#^/Users/[^/]+/(.+)$#i', $path, $matches) === 1) {
            $suffix      = $matches[1];
            $lowerSuffix = strtolower($suffix);
            foreach (self::LEGACY_USER_PATH_SUFFIXES as $key => $target) {
                if (strtolower($key) === $lowerSuffix) {
                    return $target;
                }
            }
            // /Users/{userId}/Items/{itemId}/Rating -> /UserItems/{itemId}/Rating, checked before the
            // generic Items/ prefix rule below so it isn't rewritten to a bare /Items/{itemId}/Rating instead
            if (preg_match('#^items/([^/]+)/rating$#i', $suffix, $ratingMatches) === 1) {
                return '/UserItems/' . $ratingMatches[1] . '/Rating';
            }
            if (str_starts_with($lowerSuffix, 'favoriteitems/')) {
                return '/UserFavoriteItems/' . substr($suffix, strlen('FavoriteItems/'));
            }
            if (str_starts_with($lowerSuffix, 'playeditems/')) {
                return '/UserPlayedItems/' . substr($suffix, strlen('PlayedItems/'));
            }
            // /Users/{userId}/Items/{itemId}[/...] -> /Items/{itemId}[/...], same as the bare Items alias
            if (str_starts_with($lowerSuffix, 'items/')) {
                return '/Items/' . substr($suffix, strlen('Items/'));
            }

            return $path;
        }

        // GET-only: POST /Users/AuthenticateByName etc. are exact ROUTES entries and must reach those instead
        if ($method === 'GET' && preg_match('#^/Users/[^/]+$#i', $path) === 1) {
            return '/Users/Me';
        }

        return $path;
    }
}
