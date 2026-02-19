<?php

declare(strict_types=0);

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

namespace Ampache\Module\Util;

use Exception;

class FileSystem
{
    protected ?string $base = null;

    /**
     * @throws Exception
     */
    protected function real(string $path): string
    {
        $temp = realpath($path);
        if (!$temp) {
            throw new Exception('Path does not exist: ' . $path);
        }
        if (!empty($this->base)) {
            if (!str_starts_with($temp, $this->base)) {
                throw new Exception('Path is not inside base (' . $this->base . '): ' . $temp);
            }
        }

        return $temp;
    }

    /**
     * path
     * @throws Exception
     */
    protected function path(string $fs_id): string
    {
        $fs_id = str_replace('/', DIRECTORY_SEPARATOR, $fs_id);
        $fs_id = trim($fs_id, DIRECTORY_SEPARATOR);

        return $this->real($this->base . DIRECTORY_SEPARATOR . $fs_id);
    }

    /**
     * id
     * @throws Exception
     */
    protected function id(string $path): string
    {
        $path = $this->real($path);
        $path = substr($path, strlen((string)$this->base));
        $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
        $path = trim($path, '/');

        return strlen($path)
            ? $path
            : '/';
    }

    /**
     * fs constructor.
     * @throws Exception
     */
    public function __construct(string $base)
    {
        $this->base = $this->real($base);
        if (!$this->base) {
            throw new Exception('Base directory does not exist');
        }
    }

    /**
     * @param string $fs_id
     * @param bool $with_root
     * @return array<int, array{
     *     title: string,
     *     children?: array<array{title: string, key: string, lazy: bool}>,
     *     key: string,
     *     expanded?: bool,
     *     lazy: bool,
     * }>|array<array{title: string, key: string, lazy: bool}>
     * @throws Exception
     */
    public function lst(string $fs_id, bool $with_root = false): array
    {
        $dir = (string)$this->path($fs_id);
        $lst = @scandir($dir);
        if (!$lst) {
            throw new Exception('Could not list path: ' . $dir);
        }
        $res = [];
        foreach ($lst as $item) {
            if ($item == '.' || $item == '..' || $item === null) {
                continue;
            }
            $tmp = preg_match('([^ a-zа-я-_0-9.()\[\]]+)ui', $item);
            if ($tmp === false || $tmp === 1) {
                continue;
            }
            $fullPath = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($fullPath)) {
                $res[] = [
                    'title' => $item,
                    'key' => $this->id($fullPath),
                    'lazy' => true
                ];
            }
        }
        usort($res, function ($a, $b) {
            return strcasecmp($a['title'], $b['title']);
        });
        if (
            $with_root &&
            $this->id($dir) === '/'
        ) {
            $res = [
                [
                    'title' => basename((string)$this->base),
                    'children' => $res,
                    'key' => '/',
                    'expanded' => true,
                    'lazy' => true
                ]
            ];
        }

        return $res;
    }

    /**
     * @return array{type: string, content: string}
     * @throws Exception
     */
    public function data(string $fs_id): array
    {
        if (strpos($fs_id, ":")) {
            $fs_id = array_map([$this, 'id'], explode(':', $fs_id));

            return [
                'type' => 'multiple',
                'content' => 'Multiple selected: ' . implode(' ', $fs_id)
            ];
        }
        $dir = $this->path($fs_id);
        if (is_dir($dir)) {
            return [
                'type' => 'folder',
                'content' => $fs_id
            ];
        }
        if (is_file($dir)) {
            $ext = (strpos($dir, '.') !== false)
                ? substr($dir, strrpos($dir, '.') + 1)
                : '';
            $dat = ['type' => $ext];
            switch ($ext) {
                /*case 'txt':
                case 'text':
                case 'md':
                case 'js':
                case 'json':
                case 'css':
                case 'html':
                case 'htm':
                case 'xml':
                case 'c':
                case 'cpp':
                case 'h':
                case 'sql':
                case 'log':
                case 'py':
                case 'rb':
                case 'htaccess':
                case 'php':
                    $dat['content'] = file_get_contents($dir);
                    break;
                case 'jpg':
                case 'jpeg':
                case 'gif':
                case 'png':
                case 'bmp':
                    $dat['content'] = 'data:'.finfo_file(finfo_open(FILEINFO_MIME_TYPE), $dir).';base64, '.base64_encode(file_get_contents($dir));
                    break;*/
                default:
                    $dat['content'] = 'File not recognized: ' . $this->id($dir);
                    break;
            }

            return $dat;
        }
        throw new Exception('Not a valid selection: ' . $dir);
    }

    /**
     * @param string $fs_id
     * @param string $name
     * @param bool $mkdir
     * @return array{id: string}
     * @throws Exception
     */
    public function create(string $fs_id, string $name, bool $mkdir = false): array
    {
        $dir = $this->path($fs_id);
        debug_event('fs.ajax', 'create ' . $fs_id . ' ' . $name, 5);
        if (preg_match('([^ a-zа-я-_0-9.]+)ui', $name) || !strlen($name)) {
            throw new Exception('Invalid name: ' . $name);
        }
        if ($mkdir) {
            mkdir($dir . DIRECTORY_SEPARATOR . $name);
        } else {
            file_put_contents($dir . DIRECTORY_SEPARATOR . $name, '');
        }

        return ['id' => $this->id($dir . DIRECTORY_SEPARATOR . $name)];
    }

    /**
     * @param string $fs_id
     * @param string $name
     * @return array{id: string}
     * @throws Exception
     */
    public function rename(string $fs_id, string $name): array
    {
        $dir = $this->path($fs_id);
        if ($this->base && $dir === $this->base) {
            throw new Exception('Cannot rename root');
        }
        if (preg_match('([^ a-zа-я-_0-9.]+)ui', $name) || !strlen($name)) {
            throw new Exception('Invalid name: ' . $name);
        }
        $new = explode(DIRECTORY_SEPARATOR, $dir);
        array_pop($new);
        $new[] = $name;
        $new   = implode(DIRECTORY_SEPARATOR, $new);
        if (is_file($new) || is_dir($new)) {
            throw new Exception('Path already exists: ' . $new);
        }
        rename($dir, $new);

        return ['id' => $this->id($new)];
    }

    /**
     * @param string $fs_id
     * @return array{status: string}
     * @throws Exception
     */
    public function remove(string $fs_id): array
    {
        $dir = $this->path($fs_id);
        if ($dir === $this->base) {
            throw new Exception('Cannot remove root');
        }
        if (is_dir($dir)) {
            foreach (array_diff(scandir($dir), [".", ".."]) as $file) {
                $this->remove($this->id($dir . DIRECTORY_SEPARATOR . $file));
            }
            rmdir($dir);
        }
        if (is_file($dir)) {
            unlink($dir);
        }

        return ['status' => 'OK'];
    }

    /**
     * @param string $fs_id
     * @param string $par
     * @return array{id: string}
     * @throws Exception
     */
    public function move(string $fs_id, string $par): array
    {
        $dir = $this->path($fs_id);
        $par = $this->path($par);
        $new = explode(DIRECTORY_SEPARATOR, $dir);
        $new = array_pop($new);
        $new = $par . DIRECTORY_SEPARATOR . $new;
        rename($dir, $new);

        return ['id' => $this->id($new)];
    }

    /**
     * @param string $fs_id
     * @param string $par
     * @return array{id: string}
     * @throws Exception
     */
    public function copy(string $fs_id, string $par): array
    {
        $dir = $this->path($fs_id);
        $par = $this->path($par);
        $new = explode(DIRECTORY_SEPARATOR, $dir);
        $new = array_pop($new);
        $new = $par . DIRECTORY_SEPARATOR . $new;
        if (is_file($new) || is_dir($new)) {
            throw new Exception('Path already exists: ' . $new);
        }

        if (is_dir($dir)) {
            mkdir($new);
            foreach (array_diff(scandir($dir), [".", ".."]) as $file) {
                $this->copy($this->id($dir . DIRECTORY_SEPARATOR . $file), $this->id($new));
            }
        }
        if (is_file($dir)) {
            copy($dir, $new);
        }

        return ['id' => $this->id($new)];
    }
}
