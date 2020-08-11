<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
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

use Seafile\Client\Http\Client;
use Seafile\Client\Resource\Library;
use Seafile\Client\Resource\Directory;
use Seafile\Client\Resource\File;
use GuzzleHttp\Exception\ClientException;

/**
 * Class SeafileAdapter
 */
class SeafileAdapter
{
    // request API key from Seafile Server based on username and password
    /**
     * @param string $server_uri
     * @param string $username
     * @param string $password
     * @return mixed
     * @throws Exception
     */
    public static function request_api_key($server_uri, $username, $password)
    {
        $options = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query(array('username' => $username, 'password' => $password))
            )
        );

        $result  = file_get_contents($server_uri . '/api2/auth-token/', false, stream_context_create($options));

        if (!$result) {
            throw new Exception(T_("Could not authenticate with Seafile"));
        }

        $token = json_decode($result);

        return $token->token;
    }

    /////////////////////////
    // instance
    /////////////////////////

    private $server;
    private $api_key;
    private $library_name;
    private $call_delay;

    private $client;
    private $library;

    private $directory_cache;

    /**
     * SeafileAdapter constructor.
     * @param $server_uri
     * @param $library_name
     * @param $call_delay
     * @param $api_key
     */
    public function __construct($server_uri, $library_name, $call_delay, $api_key)
    {
        $this->server          = $server_uri;
        $this->library_name    = $library_name;
        $this->api_key         = $api_key;
        $this->call_delay      = $call_delay;
        $this->directory_cache = array();
    }

    // do we have all the info we need?

    /**
     * @return boolean
     */
    public function ready()
    {
        return $this->server != null &&
            $this->api_key != null &&
            $this->library_name != null &&
            $this->call_delay != null;
    }

    // create API client object & find library

    /**
     * @return boolean
     */
    public function prepare()
    {
        if ($this->client !== null) {
            return true;
        }

        if (!$this->ready()) {
            $this->client = null;

            return false;
        }

        $client = new Client([
            'base_uri' => $this->server,
            'debug' => false,
            'delay' => $this->call_delay,
            'headers' => [
                'Authorization' => 'Token ' . $this->api_key
            ]
        ]);

        $this->client = array(
            'Libraries' => new Library($client),
            'Directories' => new Directory($client),
            'Files' => new File($client),
            'Client' => $client
        );

        // Get Library
        $libraries = $this->throttle_check(function () {
            return $this->client['Libraries']->getAll();
        });

        $matches = array_values(array_filter($libraries, function ($library) {
            return $library->name == $this->library_name;
        }));

        if (count($matches) == 0) {
            AmpError::add('general', sprintf(T_('Could not find the Seafile library called "%s", no media was updated'), $this->library_name));

            return false;
        }

        $this->library = $matches[0];

        return true;
    }

    // run a function that hits the Seafile API, but catch throttling errors and retry

    /**
     * @param $func
     * @return mixed
     */
    private function throttle_check($func)
    {
        while (true) {
            try {
                return $func();
            } catch (ClientException $error) {
                if ($e->getResponse()->getStatusCode() !== 429) {
                    throw $e;
                }

                $resp = $e->getResponse()->getBody();

                $error = json_decode($resp)->detail;

                preg_match('(\d+) sec', $error, $matches);

                $secs = (int) $matches[1][0];

                debug_event('SeafileAdapter', sprintf('Throttled by Seafile, waiting %d seconds.', $secs), 5);
                sleep($secs + 1);
            }
        }
    }

    // given a given path & filename, return the "virtual" path string which will be stored in the database

    /**
     * @param $file
     * @return string
     */
    public function to_virtual_path($file)
    {
        return $this->library->name . '|' . $file->dir . '|' . $file->name;
    }

    // given a database-stored "virtual" path, return the path & filename

    /**
     * @param string $file_path
     * @return array
     */
    public function from_virtual_path($file_path)
    {
        $split = explode('|', $file_path);

        return array('path' => $split[1], 'filename' => $split[2]);
    }

    /**
     * @param $path
     * @return mixed|null
     */
    private function get_cached_directory($path)
    {
        if (array_key_exists($path, $this->directory_cache)) {
            $directory = $this->directory_cache[$path];

            if ($directory) {
                return $directory;
            } else {
                return null;
            }
        } else {
            try {
                $directory = $this->throttle_check(function () use ($path) {
                    return $this->client['Directories']->getAll($this->library, $path);
                });
                $this->directory_cache[$path] = $directory;

                return $directory;
            } catch (ClientException $error) {
                if ($e->getResponse()->getStatusCode() == 404) {
                    $this->directory_cache[$path] = false;

                    return null;
                } else {
                    throw $e;
                }
            }
        }
    }

    // run a function for all files in the seafile library.
    // the function receives a DirectoryItem and should return 1 if the file was added, 0 otherwise
    // (https://github.com/rene-s/Seafile-PHP-SDK/blob/master/src/Type/DirectoryItem.php)
    // Returns number added, or -1 on failure
    /**
     * @param $func
     * @param string $path
     * @return integer
     */
    public function for_all_files($func, $path = '/')
    {
        if ($this->client != null) {
            $directoryItems = $this->get_cached_directory($path);

            $count = 0;

            if ($directoryItems !== null && count($directoryItems) > 0) {
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

    /**
     * @param $path
     * @param string $name
     * @return mixed|null
     */
    public function get_file($path, $name)
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

    // download a file, optionally limited to just enough to be able to read its metadata tags(currently 2MB)

    /**
     * @param $file
     * @param boolean $partial
     * @return string
     */
    public function download($file, $partial = false)
    {
        $url = $this->throttle_check(function () use ($file) {
            return $this->client['Files']->getDownloadUrl($this->library, $file, $file->dir);
        });

        if ($partial) {
            $opts = ['curl' => [ CURLOPT_RANGE => '0-2097152' ]];
        } else {
            $opts = [ 'delay' => 0 ];
        }
        // grab a full 2 meg in case meta has image in it or something
        $response = $this->throttle_check(function () use ($url, $opts) {
            return $this->client['Client']->request('GET', $url, $opts);
        });

        $tempfilename = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $file->name;

        $tempfile = fopen($tempfilename, 'wb');

        fwrite($tempfile, $response->getBody());

        fclose($tempfile);

        return $tempfilename;
    }

    /**
     * @return string
     */
    public function get_format_string()
    {
        return 'Seafile server "' . $this->server . '", library "' . $this->library_name . '"';
    }
}
