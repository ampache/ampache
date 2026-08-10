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

namespace Ampache\Module\Util\OAuth;

use Ampache\Module\System\Core;
use Ampache\Module\Util\OAuth\Exception\OAuthException;
use Stringable;

/**
 * Class OAuthRequest
 */
class OAuthRequest implements Stringable
{
    public static string $POST_INPUT = 'php://input';
    public static string $version    = '1.0';

    // for debug purposes
    public string $base_string  = '';
    protected ?string $http_url = null;

    /** @var array<string, string|string[]> $parameters */
    protected array $parameters = [];

    /**
     * OAuthRequest constructor.
     */
    public function __construct(
        protected $http_method,
        $http_url,
        $parameters = null,
    ) {
        $parameters = $parameters ?: [];
        $parameters = array_merge(OAuthUtil::parse_parameters(parse_url(
            (string) $http_url,
            PHP_URL_QUERY
        )), $parameters);
        $this->parameters = $parameters;

        $this->http_url = $http_url;
    }

    /**
     * pretty much a helper function to set up the request
     * @param array<string, int|string>|null $parameters
     */
    public static function from_consumer_and_token(string $http_url, string $http_method, OAuthConsumer $consumer, ?OAuthToken $token = null, ?array $parameters = null): OAuthRequest
    {
        $parameters = $parameters ?: [];
        $defaults   = [
            "oauth_version" => OAuthRequest::$version,
            "oauth_nonce" => OAuthRequest::_generate_nonce(),
            "oauth_timestamp" => OAuthRequest::_generate_timestamp(),
            "oauth_consumer_key" => $consumer->key,
        ];
        if ($token instanceof OAuthToken) {
            $defaults['oauth_token'] = $token->key;
        }

        $parameters = array_merge($defaults, $parameters);

        return new OAuthRequest($http_method, $http_url, $parameters);
    }

    /**
     * from_request
     * attempt to build up a request from what was passed to the server
     */
    public static function from_request(?string $http_method = null, ?string $http_url = null, ?array $parameters = null): OAuthRequest
    {
        $scheme      = (!isset($_SERVER['HTTPS']) || Core::get_server('HTTPS') !== "on") ? 'http' : 'https';
        $http_url    = $http_url ?: $scheme . '://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
        $http_method = $http_method ?: $_SERVER['REQUEST_METHOD'];

        // We weren't handed any parameters, so let's find the ones relevant to
        // this request.
        // If you run XML-RPC or similar you should use this to provide your own
        // parsed parameter-list
        if (!$parameters) {
            // Find request headers
            $request_headers = OAuthUtil::get_headers();

            // Parse the query-string to find GET parameters
            $parameters = OAuthUtil::parse_parameters($_SERVER['QUERY_STRING']);

            // It's a POST request of the proper content-type, so parse POST
            // parameters and add those overriding any duplicates from GET
            if ($http_method == "POST" && isset($request_headers['Content-Type']) && strstr((string) $request_headers['Content-Type'], 'application/x-www-form-urlencoded')) {
                $post_data  = OAuthUtil::parse_parameters(file_get_contents(self::$POST_INPUT));
                $parameters = array_merge($parameters, $post_data);
            }

            // We have a Authorization-header with OAuth data. Parse the header
            // and add those overriding any duplicates from GET or POST
            if (isset($request_headers['Authorization']) && str_starts_with((string) $request_headers['Authorization'], 'OAuth ')) {
                $header_parameters = OAuthUtil::split_header($request_headers['Authorization']);
                $parameters        = array_merge($parameters, $header_parameters);
            }
        }

        return new OAuthRequest($http_method, $http_url, $parameters);
    }

    /**
     * util function: current nonce
     */
    private static function _generate_nonce(): string
    {
        $mtime = microtime();
        $rand  = bin2hex(random_bytes(20));

        return md5($mtime . $rand); // md5s look nicer than numbers
    }

    /**
     * util function: current timestamp
     */
    private static function _generate_timestamp(): int
    {
        return time();
    }

    public function build_signature($signature_method, $consumer, $token): string
    {
        return $signature_method->build_signature($this, $consumer, $token);
    }

    /**
     * just uppercase the http method
     */
    public function get_normalized_http_method(): string
    {
        return strtoupper((string) $this->http_method);
    }

    /**
     * parses the url and rebuilds it to be
     * scheme://host/path
     */
    public function get_normalized_http_url(): string
    {
        $parts = parse_url((string) $this->http_url);

        $scheme = $parts['scheme'] ?? 'http';
        $port   = $parts['port'] ?? (($scheme == 'https') ? '443' : '80');
        $host   = (isset($parts['host'])) ? strtolower($parts['host']) : '';
        $path   = $parts['path'] ?? '';

        if (($scheme == 'https' && $port != '443') || ($scheme == 'http' && $port != '80')) {
            $host = sprintf('%s:%s', $host, $port);
        }

        return sprintf('%s://%s%s', $scheme, $host, $path);
    }

    /**
     * @return string|string[]|null
     */
    public function get_parameter(string $name): string|array|null
    {
        return $this->parameters[$name] ?? null;
    }

    /**
     * @return array<string, string|string[]>
     */
    public function get_parameters(): array
    {
        return $this->parameters;
    }

    /**
     * The request parameters, sorted and concatenated into a normalized string.
     */
    public function get_signable_parameters(): string
    {
        // Grab all parameters
        $params = $this->parameters;

        // Remove oauth_signature if present
        // Ref: Spec: 9.1.1 ("The oauth_signature parameter MUST be excluded.")
        if (isset($params['oauth_signature'])) {
            unset($params['oauth_signature']);
        }

        return OAuthUtil::build_http_query($params);
    }

    /**
     * Returns the base string of this request
     *
     * The base string defined as the method, the url
     * and the parameters (normalized), each urlencoded
     * and the concatenated with &.
     */
    public function get_signature_base_string(): string
    {
        $parts = [
            $this->get_normalized_http_method(),
            $this->get_normalized_http_url(),
            $this->get_signable_parameters(),
        ];

        $parts = OAuthUtil::urlencode_rfc3986($parts);

        return (is_array($parts))
            ? implode('&', $parts)
            : $parts;
    }

    public function set_parameter(string $name, string $value, bool $allow_duplicates = true): void
    {
        if ($allow_duplicates && isset($this->parameters[$name])) {
            // We have already added parameter(s) with this name, so add to the list
            if (is_scalar($this->parameters[$name])) {
                // This is the first duplicate, so transform scalar (string)
                // into an array so we can add the duplicates
                $this->parameters[$name] = [$this->parameters[$name]];
            }

            $this->parameters[$name][] = $value;
        } else {
            $this->parameters[$name] = $value;
        }
    }

    public function sign_request($signature_method, $consumer, $token): void
    {
        $this->set_parameter("oauth_signature_method", $signature_method->get_name(), false);
        $signature = $this->build_signature($signature_method, $consumer, $token);
        $this->set_parameter("oauth_signature", $signature, false);
    }

    /**
     * builds the Authorization: header
     * @throws Exception\OAuthException
     */
    public function to_header($realm = null): string
    {
        $first = true;
        if ($realm) {
            $out   = 'Authorization: OAuth realm="' . OAuthUtil::urlencode_rfc3986($realm) . '"';
            $first = false;
        } else {
            $out = 'Authorization: OAuth';
        }

        foreach ($this->parameters as $key => $value) {
            if (!str_starts_with($key, "oauth")) {
                continue;
            }

            if (is_array($value)) {
                throw new OAuthException('Arrays not supported in headers');
            }

            $out .= ($first) ? ' ' : ', ';
            $out .= OAuthUtil::urlencode_rfc3986($key) . '="' . OAuthUtil::urlencode_rfc3986($value) . '"';
            $first = false;
        }

        return $out;
    }

    /**
     * builds the data one would send in a POST request
     */
    public function to_postdata(): string
    {
        return OAuthUtil::build_http_query($this->parameters);
    }

    /**
     * builds a url usable for a GET request
     */
    public function to_url(): string
    {
        $post_data = $this->to_postdata();
        $out       = $this->get_normalized_http_url();
        if ($post_data !== '' && $post_data !== '0') {
            $out .= '?' . $post_data;
        }

        return $out;
    }

    public function unset_parameter(string $name): void
    {
        unset($this->parameters[$name]);
    }

    /**
     * __toString
     */
    public function __toString(): string
    {
        return $this->to_url();
    }
}
