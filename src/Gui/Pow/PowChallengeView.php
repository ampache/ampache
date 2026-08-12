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

namespace Ampache\Gui\Pow;

use Ampache\Config\AmpConfig;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Pow\PowChallenge;
use Override;

/**
 * The standalone page shown in front of an endpoint that is a plain link rather than a form.
 *
 * It solves the challenge and then replays the request it interrupted, so the visitor only sees a
 * short "checking" message where the download would have started.
 */
final class PowChallengeView extends AbstractView
{
    public function __construct(
        private readonly PowChallenge $challenge,
        private readonly string $targetUrl,
        private readonly string $webPath,
        private readonly string $referer = '',
    ) {}

    public function getDocumentLanguage(): string
    {
        return str_replace('_', '-', (string) AmpConfig::get('lang', 'en_US'));
    }

    /**
     * Where to send the visitor once the download is under way, so the interstitial is not left
     * sitting there with nothing to do.
     *
     * The `Referer` is client supplied and ends up in `location.replace()`, so anything that is not
     * the same origin as the request being replayed falls back to the home page rather than becoming
     * an open redirect.
     */
    public function getReturnUrl(): string
    {
        $fallback = $this->webPath . '/index.php';
        $referer  = parse_url($this->referer);
        $target   = parse_url($this->targetUrl);

        if ($this->referer === '' || !is_array($referer) || !is_array($target)) {
            return $fallback;
        }

        foreach (['scheme', 'host', 'port'] as $part) {
            if (($referer[$part] ?? null) !== ($target[$part] ?? null)) {
                return $fallback;
            }
        }

        // Coming back to the protected link itself would just start the download again.
        if (($referer['path'] ?? '') === ($target['path'] ?? '')) {
            return $fallback;
        }

        return $this->referer;
    }

    public function getSiteCharset(): string
    {
        return (string) AmpConfig::get('site_charset', 'UTF-8');
    }

    /**
     * The parameters of the interrupted request, flattened to name/value pairs ready to be printed
     * as hidden fields. Any answer a previous attempt left behind is dropped so a retry cannot
     * accumulate stale parameters.
     *
     * @return list<array{name: string, value: string}>
     */
    public function getTargetFields(): array
    {
        parse_str((string) parse_url($this->targetUrl, PHP_URL_QUERY), $query);
        unset($query['pow_id'], $query['pow_exp'], $query['pow_diff'], $query['pow_sig'], $query['pow_nonce']);

        $fields = [];
        foreach ($this->flatten($query) as $name => $value) {
            $fields[] = ['name' => $name, 'value' => $value];
        }

        return $fields;
    }

    /**
     * Where to go back to once the challenge is solved.
     *
     * A GET form replaces the query string of its action, so the path and the parameters are handed
     * over separately and the parameters are re-emitted as fields. That is also what keeps list
     * parameters such as `id[]` intact.
     */
    public function getTargetPath(): string
    {
        return parse_url($this->targetUrl, PHP_URL_PATH) ?: '/';
    }

    public function getTitle(): string
    {
        return AmpConfig::get('site_title') . ' - ' . T_('Checking your browser');
    }

    public function getWebPath(): string
    {
        return $this->webPath;
    }

    public function renderWidget(): string
    {
        return (new PowWidgetView($this->challenge, $this->webPath))->render();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('pow/challenge.phtml');
    }

    /**
     * @param array<array-key, mixed> $values
     * @return array<string, string>
     */
    private function flatten(array $values, string $prefix = ''): array
    {
        $flat = [];
        foreach ($values as $key => $value) {
            $name = ($prefix === '')
                ? (string) $key
                : sprintf('%s[%s]', $prefix, $key);

            if (is_array($value)) {
                $flat += $this->flatten($value, $name);
            } else {
                $flat[$name] = (string) $value;
            }
        }

        return $flat;
    }
}
