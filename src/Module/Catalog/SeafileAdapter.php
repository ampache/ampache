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

namespace Ampache\Module\Catalog;

use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Exception;
use GuzzleHttp\Exception\ClientException;
use Seafile\Client\Http\Client;
use Seafile\Client\Resource\Directory;
use Seafile\Client\Resource\File;
use Seafile\Client\Resource\Library;
use Seafile\Client\Type\DirectoryItem;
use Seafile\Client\Type\Library as LibraryType;

class SeafileAdapter
{
    /** @var array{Libraries: Library, Directories: Directory, Files: File, Client: Client}|null  */
    private ?array $client = null;

    /** @var array<string, DirectoryItem[]> */
    private array $directory_cache = [];

    private ?LibraryType $library = null;

    /**
     * SeafileAdapter constructor.
     */
    public function __construct(
        private readonly ?string $server,
        private readonly ?string $library_name,
        private readonly ?int $call_delay,
        private readonly ?string $api_key,
    ) {}

    /**
     * request API key from Seafile Server based on username and password
     * @throws Exception
     */
    public static function request_api_key(string $server_uri, string $username, string $password): string
    {
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query(['username' => $username, 'password' => $password])
            ]
        ];

        $result = file_get_contents($server_uri . '/api2/auth-token/', false, stream_context_create($options));

        if (!$result) {
            throw new Exception(T_("Could not authenticate with Seafile"));
        }

        $token = json_decode($result);

        return $token->token;
    }

    // download a file, optionally limited to just enough to be able to read its metadata tags(currently 2MB)

    public function download(DirectoryItem $file, bool $partial = false): string
    {
        $dir  = (string) $file->dir;
        $url  = ($this->client && $this->library) ? $this->throttle_check(fn() => $this->client['Files']->getDownloadUrl($this->library, $file, $dir)) : '';
        $opts = $partial ? ['curl' => [CURLOPT_RANGE => '0-2097152']] : ['delay' => 0];

        // grab a full 2 meg in case meta has image in it or something
        $response = ($this->client) ? $this->throttle_check(fn() => $this->client['Client']->request('GET', $url, $opts)) : null;

        $tempfilename = Core::get_tmp_dir() . DIRECTORY_SEPARATOR . $file->name;

        $tempfile = fopen($tempfilename, 'wb');

        if ($tempfile && $response) {
            fwrite($tempfile, (string) $response->getBody());
            fclose($tempfile);
        }

        return $tempfilename;
    }

    /**
     * run a function for all files in the Seafile library.
     * the function receives a DirectoryItem and should return 1 if the file was added, 0 otherwise
     * (https://github.com/rene-s/Seafile-PHP-SDK/blob/master/src/Type/DirectoryItem.php)
     * Returns number added, or -1 on failure
     */
    public function for_all_files($func, string $path = '/'): int
    {
        if ($this->client != null) {
            $directoryItems = $this->get_cached_directory($path);

            $count = 0;

            if ($directoryItems !== null && $directoryItems !== []) {
                foreach ($directoryItems as $item) {
                    if ($item->type == 'dir') {
                        $count += $this->for_all_files($func, $path . $item->name . '/');
                    } elseif ($item->type == 'file') {
                        $count += $func($item);
                    }
                }
            }

            return $count;
        }

        return -1;
    }

    // given a database-stored "virtual" path, return the path & filename

    /**
     * @return array{
     *     path: string,
     *     filename: string
     * }
     */
    public function from_virtual_path(string $file_path): array
    {
        $split = explode('|', $file_path);

        return [
            'path' => $split[1],
            'filename' => $split[2],
        ];
    }

    public function get_file(string $path, string $name): ?DirectoryItem
    {
        $directory = $this->get_cached_directory($path);

        if ($directory) {
            foreach ($directory as $file) {
                if ($file->name === $name) {
                    return $file;
                }
            }
        }

        return null;
    }

    /**
     * get_format_string
     */
    public function get_format_string(): string
    {
        return 'Seafile server "' . $this->server . '", library "' . $this->library_name . '"';
    }

    // create API client object & find library

    /**
     * prepare
     */
    public function prepare(): bool
    {
        if ($this->client !== null) {
            return true;
        }

        if (!$this->ready()) {
            $this->client = null;

            return false;
        }

        $client = new Client(
            [
                'base_uri' => $this->server,
                'debug' => false,
                'delay' => $this->call_delay,
                'headers' => ['Authorization' => 'Token ' . $this->api_key]
            ]
        );

        $this->client = [
            'Libraries' => new Library($client),
            'Directories' => new Directory($client),
            'Files' => new File($client),
            'Client' => $client,
        ];

        // Get Library
        /** @var LibraryType[] $libraries */
        $libraries = $this->throttle_check(fn() => $this->client['Libraries']->getAll());

        $matches = array_values(array_filter($libraries, fn($library) => $library->name == $this->library_name));

        if ($matches === []) {
            AmpError::add(
                'general',
                sprintf(
                    T_('Could not find the Seafile library called "%s", no media was updated'),
                    $this->library_name
                )
            );

            return false;
        }

        $this->library = $matches[0];

        return true;
    }

    // do we have all the info we need?

    /**
     * ready
     */
    public function ready(): bool
    {
        return (
            $this->server != null
            && $this->api_key != null
            && $this->library_name != null
            && $this->call_delay != null
        );
    }

    // given a given path & filename, return the "virtual" path string which will be stored in the database

    public function to_virtual_path(DirectoryItem $file): string
    {
        return ($this->library->name ?? '') . '|' . $file->dir . '|' . $file->name;
    }

    /**
     * @return DirectoryItem[]|null
     */
    private function get_cached_directory(string $path): ?array
    {
        if (array_key_exists($path, $this->directory_cache)) {
            $directory = $this->directory_cache[$path];

            if ($directory !== []) {
                return $directory;
            }

            return null;
        }

        if (!$this->client || !$this->library) {
            return null;
        }

        try {
            /** @var DirectoryItem[] $directory */
            $directory                    = $this->throttle_check(fn() => $this->client['Directories']->getAll($this->library, $path));
            $this->directory_cache[$path] = $directory;

            return $directory;
        } catch (ClientException $clientException) {
            if ($clientException->getResponse()->getStatusCode() == 404) {
                unset($this->directory_cache[$path]);

                return null;
            }

            throw $clientException;
        }
    }

    // run a function that hits the Seafile API, but catch throttling errors and retry

    private function throttle_check(callable $func): mixed
    {
        while (true) {
            try {
                return $func();
            } catch (ClientException $error) {
                if ($error->getResponse()->getStatusCode() !== 429) {
                    throw $error;
                }

                $resp = $error->getResponse()->getBody();

                $error = json_decode((string) $resp)->detail;

                preg_match('/(\d+) sec/', (string) $error, $matches);

                $secs = isset($matches[1]) ? (int) $matches[1] : 0;

                debug_event('SeafileAdapter', sprintf('Throttled by Seafile, waiting %d seconds.', $secs), 5);
                sleep($secs + 1);
            }
        }
    }
}
