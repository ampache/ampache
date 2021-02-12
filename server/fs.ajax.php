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

 // jsTree file system browser

define('AJAX_INCLUDE', '1');

$a_root = realpath(__DIR__ . "/../");
require_once $a_root . '/lib/init.php';
$rootdir = Upload::get_root();
if (empty($rootdir)) {
    return false;
}
$rootdir .= DIRECTORY_SEPARATOR;

/**
 * Class fs
 */
class fs
{
    protected $base = null;

    /**
     * @param $path
     * @return false|string
     * @throws Exception
     */
    protected function real($path)
    {
        $temp = realpath($path);
        if (!$temp) {
            throw new Exception('Path does not exist: ' . $path);
        }
        if ($this->base && strlen($this->base)) {
            if (strpos($temp, $this->base) !== 0) {
                throw new Exception('Path is not inside base (' . $this->base . '): ' . $temp);
            }
        }

        return $temp;
    }

    /**
     * @param string $fs_id
     * @return false|string
     * @throws Exception
     */
    protected function path($fs_id)
    {
        $fs_id = str_replace('/', DIRECTORY_SEPARATOR, $fs_id);
        $fs_id = trim($fs_id, DIRECTORY_SEPARATOR);
        $fs_id = $this->real($this->base . DIRECTORY_SEPARATOR . $fs_id);

        return $fs_id;
    }

    /**
     * @param string $path
     * @return string
     * @throws Exception
     */
    protected function id($path)
    {
        $path = $this->real($path);
        $path = substr($path, strlen($this->base));
        $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
        $path = trim($path, '/');

        return strlen($path) ? $path : '/';
    }

    /**
     * fs constructor.
     * @param $base
     * @throws Exception
     */
    public function __construct($base)
    {
        $this->base = $this->real($base);
        if (!$this->base) {
            throw new Exception('Base directory does not exist');
        }
    }

    /**
     * @param $fs_id
     * @param boolean $with_root
     * @return array
     * @throws Exception
     */
    public function lst($fs_id, $with_root = false)
    {
        $dir = (string) $this->path($fs_id);
        $lst = @scandir($dir);
        if (!$lst) {
            throw new Exception('Could not list path: ' . $dir);
        }
        $res = array();
        foreach ($lst as $item) {
            if ($item == '.' || $item == '..' || $item === null) {
                continue;
            }
            $tmp = preg_match('([^ a-zа-я-_0-9.]+)ui', $item);
            if ($tmp === false || $tmp === 1) {
                continue;
            }
            if (is_dir($dir . DIRECTORY_SEPARATOR . $item)) {
                $res[] = array('text' => $item, 'children' => true, 'id' => $this->id($dir . DIRECTORY_SEPARATOR . $item), 'icon' => 'folder');
            }
        }
        if ($with_root && $this->id($dir) === '/') {
            $res = array(array('text' => basename($this->base), 'children' => $res, 'id' => '/', 'icon' => 'folder', 'state' => array('opened' => true, 'disabled' => true)));
        }

        return $res;
    }

    /**
     * @param $fs_id
     * @return array
     * @throws Exception
     */
    public function data($fs_id)
    {
        if (strpos($fs_id, ":")) {
            $fs_id = array_map(array($this, 'id'), explode(':', $fs_id));

            return array('type' => 'multiple', 'content' => 'Multiple selected: ' . implode(' ', $fs_id));
        }
        $dir = $this->path($fs_id);
        if (is_dir($dir)) {
            return array('type' => 'folder', 'content' => $fs_id);
        }
        if (is_file($dir)) {
            $ext = strpos($dir, '.') !== false ? substr($dir, strrpos($dir, '.') + 1) : '';
            $dat = array('type' => $ext, 'content' => '');
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
     * @param $fs_id
     * @param string $name
     * @param boolean $mkdir
     * @return array
     * @throws Exception
     */
    public function create($fs_id, $name, $mkdir = false)
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

        return array('id' => $this->id($dir . DIRECTORY_SEPARATOR . $name));
    }

    /**
     * @param $fs_id
     * @param string $name
     * @return array
     * @throws Exception
     */
    public function rename($fs_id, $name)
    {
        $dir = $this->path($fs_id);
        if ($dir === $this->base) {
            throw new Exception('Cannot rename root');
        }
        if (preg_match('([^ a-zа-я-_0-9.]+)ui', $name) || !strlen($name)) {
            throw new Exception('Invalid name: ' . $name);
        }
        $new = explode(DIRECTORY_SEPARATOR, $dir);
        array_pop($new);
        array_push($new, $name);
        $new = implode(DIRECTORY_SEPARATOR, $new);
        if (is_file($new) || is_dir($new)) {
            throw new Exception('Path already exists: ' . $new);
        }
        rename($dir, $new);

        return array('id' => $this->id($new));
    }

    /**
     * @param $fs_id
     * @return array
     * @throws Exception
     */
    public function remove($fs_id)
    {
        $dir = $this->path($fs_id);
        if ($dir === $this->base) {
            throw new Exception('Cannot remove root');
        }
        if (is_dir($dir)) {
            foreach (array_diff(scandir($dir), array(".", "..")) as $f) {
                $this->remove($this->id($dir . DIRECTORY_SEPARATOR . $f));
            }
            rmdir($dir);
        }
        if (is_file($dir)) {
            unlink($dir);
        }

        return array('status' => 'OK');
    }

    /**
     * @param $fs_id
     * @param $par
     * @return array
     * @throws Exception
     */
    public function move($fs_id, $par)
    {
        $dir = $this->path($fs_id);
        $par = $this->path($par);
        $new = explode(DIRECTORY_SEPARATOR, $dir);
        $new = array_pop($new);
        $new = $par . DIRECTORY_SEPARATOR . $new;
        rename($dir, $new);

        return array('id' => $this->id($new));
    }

    /**
     * @param $fs_id
     * @param $par
     * @return array
     * @throws Exception
     */
    public function copy($fs_id, $par)
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
            foreach (array_diff(scandir($dir), array(".", "..")) as $f) {
                $this->copy($this->id($dir . DIRECTORY_SEPARATOR . $f), $this->id($new));
            }
        }
        if (is_file($dir)) {
            copy($dir, $new);
        }

        return array('id' => $this->id($new));
    }
}

if (filter_has_var(INPUT_GET, 'operation')) {
    $fs = new fs($rootdir);
    try {
        $rslt = null;
        switch (Core::get_get('operation')) {
            case 'get_node':
                $node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : '/';
                $rslt = $fs->lst($node, (isset($_GET['id']) && $_GET['id'] === '#'));
                break;
            case "get_content":
                $node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : '/';
                $rslt = $fs->data($node);
                break;
            case 'create_node':
                $node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : '/';
                $rslt = $fs->create($node, isset($_GET['text']) ? $_GET['text'] : '', (!isset($_GET['type']) || filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES) !== 'file'));
                break;
            case 'rename_node':
                $node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : '/';
                $rslt = $fs->rename($node, isset($_GET['text']) ? $_GET['text'] : '');
                break;
            case 'delete_node':
                $node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : '/';
                $rslt = $fs->remove($node);
                break;
            case 'move_node':
                $node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : '/';
                $parn = isset($_GET['parent']) && $_GET['parent'] !== '#' ? $_GET['parent'] : '/';
                $rslt = $fs->move($node, $parn);
                break;
            case 'copy_node':
                $node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : '/';
                $parn = isset($_GET['parent']) && $_GET['parent'] !== '#' ? $_GET['parent'] : '/';
                $rslt = $fs->copy($node, $parn);
                break;
            default:
                throw new Exception('Unsupported operation: ' . Core::get_get('operation'));
        }
        header('Content-Type: application/json; charset=utf8');
        echo json_encode($rslt);
    } catch (Exception $error) {
        header(Core::get_server('SERVER_PROTOCOL') . ' 500 Server Error');
        header('Status:  500 Server Error');
        echo $error->getMessage();
    }
    die();
}
