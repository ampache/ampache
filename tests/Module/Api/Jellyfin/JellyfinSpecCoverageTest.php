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

use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Checks that every route `JellyfinApiApplication` serves corresponds to a real path+verb in the vendored
 * Jellyfin spec.
 *
 * Unlike docs/openapi.json (generated from this codebase's own docblocks), docs/jellyfin-openapi-stable.json
 * describes the real Jellyfin server and is not generated here, so only one direction is meaningful: a route
 * this surface serves with no match in the spec is either an invented/mistyped endpoint or a deliberate,
 * client-driven deviation that belongs in KNOWN_DEVIATIONS. The reverse — every spec path implemented — is
 * never asserted: Ampache deliberately serves only the audio-relevant slice (no podcasts, video, live TV,
 * plugins, etc.), so most of the spec's 294 paths are expected to have no route at all.
 */
class JellyfinSpecCoverageTest extends TestCase
{
    /**
     * Routes this surface serves that the spec does not document, with the reason. Each is asserted to
     * still be undocumented so the list cannot silently rot — remove an entry once the spec catches up.
     *
     * @var array<string, string>
     */
    private const array KNOWN_DEVIATIONS = [
        'GET /MusicGenres' => 'undocumented in spec 12.0.0, but real clients (Symfonium) call it and retry forever without it',
        'HEAD #^/Items/(?<itemId>[^/]+)/File$#i' => 'spec documents only GET for this path; HEAD is served too for range-probing clients',
        'GET /web' => 'not a real API operation - redirects a client opening the bundled web client (we do not serve one) to our own QuickConnect approval page',
    ];
    private const string SPEC = __DIR__ . '/../../../../docs/jellyfin-openapi-stable.json';

    /**
     * A route with no match in the spec and no KNOWN_DEVIATIONS entry is an invented or mistyped endpoint.
     */
    public function testEveryRouteExistsInTheSpecOrIsAKnownDeviation(): void
    {
        $unexplained = array_values(array_filter(
            $this->allUnmatchedRoutes(),
            static fn(string $route): bool => !array_key_exists($route, self::KNOWN_DEVIATIONS),
        ));

        sort($unexplained);

        self::assertSame(
            [],
            $unexplained,
            "JellyfinApiApplication serves a route with no matching path+verb in docs/jellyfin-openapi-stable.json:\n  - "
            . implode("\n  - ", $unexplained),
        );
    }

    /**
     * A KNOWN_DEVIATIONS entry the spec now documents is stale and must be removed, not left to rot.
     */
    public function testKnownDeviationsAreStillUndocumented(): void
    {
        $stillUnmatched = $this->allUnmatchedRoutes();

        foreach (array_keys(self::KNOWN_DEVIATIONS) as $route) {
            self::assertContains(
                $route,
                $stillUnmatched,
                sprintf('%s now matches the spec - remove it from KNOWN_DEVIATIONS', $route),
            );
        }
    }

    /**
     * Every unmatched route, whether or not it is a known deviation.
     *
     * @return list<string>
     */
    private function allUnmatchedRoutes(): array
    {
        $spec       = $this->spec();
        $candidates = $this->concreteSpecOperations($spec);
        $unmatched  = [];

        foreach ($this->routes() as $key => $handler) {
            [$method, $path] = explode(' ', $key, 2);
            if (!isset($spec['paths'][$path][strtolower($method)])) {
                $unmatched[] = $key;
            }
        }

        foreach ($this->patternRoutes() as [$method, $regex, $handler]) {
            $matched = false;
            foreach ($candidates as [$candidateMethod, $concretePath]) {
                if ($candidateMethod === $method && preg_match($regex, $concretePath) === 1) {
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                $unmatched[] = sprintf('%s %s', $method, $regex);
            }
        }

        return $unmatched;
    }

    /**
     * A stand-in path per spec path+verb, with every `{param}` replaced by a digit string that satisfies
     * both `[^/]+`- and `[0-9]+`-shaped capture groups, so a route regex can be tested against it directly
     * instead of reverse-parsing the regex into a path template.
     *
     * @param array<string, mixed> $spec
     * @return list<array{0: string, 1: string}>
     */
    private function concreteSpecOperations(array $spec): array
    {
        $candidates = [];
        foreach ($spec['paths'] as $path => $operations) {
            $concrete = (string) preg_replace('#\{[^/}]+\}#', '42', (string) $path);
            foreach (array_keys($operations) as $method) {
                $candidates[] = [strtoupper((string) $method), $concrete];
            }
        }

        return $candidates;
    }

    /**
     * @return list<array{0: string, 1: string, 2: class-string}>
     */
    private function patternRoutes(): array
    {
        $reflection = new ReflectionClass(JellyfinApiApplication::class);

        return $reflection->getReflectionConstant('PATTERN_ROUTES')->getValue();
    }

    /**
     * @return array<string, class-string>
     */
    private function routes(): array
    {
        $reflection = new ReflectionClass(JellyfinApiApplication::class);

        return $reflection->getReflectionConstant('ROUTES')->getValue();
    }

    /**
     * @return array<string, mixed>
     */
    private function spec(): array
    {
        $spec = json_decode((string) file_get_contents(self::SPEC), true);

        self::assertIsArray($spec, 'docs/jellyfin-openapi-stable.json is not valid JSON');

        return $spec;
    }
}
