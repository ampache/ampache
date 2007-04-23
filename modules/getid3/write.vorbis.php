<?php
// +----------------------------------------------------------------------+
// | PHP version 5                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006 James Heinrich, Allan Hansen                 |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2 of the GPL license,         |
// | that is bundled with this package in the file license.txt and is     |
// | available through the world-wide-web at the following url:           |
// | http://www.gnu.org/copyleft/gpl.html                                 |
// +----------------------------------------------------------------------+
// | getID3() - http://getid3.sourceforge.net or http://www.getid3.org    |
// +----------------------------------------------------------------------+
// | Authors: James Heinrich <infoØgetid3*org>                            |
// |          Allan Hansen <ahØartemis*dk>                                |
// +----------------------------------------------------------------------+
// | write.vorbis.php                                                     |
// | writing module for vorbis tags                                       |
// | dependencies: vorbiscomment binary.                                  |
// +----------------------------------------------------------------------+
//
// $Id: write.vorbis.php,v 1.6 2006/12/03 20:02:25 ah Exp $


class getid3_write_vorbis extends getid3_handler_write
{
    
    public $comments = array ();
    
    
    public function __construct($filename) {
        
        if (ini_get('safe_mode')) {
            throw new getid3_exception('PHP running in Safe Mode (backtick operator not available). Cannot call vorbiscomment binary.');
        }
        
        static $initialized;
        if (!$initialized) {
            
            // check existance and version of vorbiscomment
            if (!ereg('^Vorbiscomment ([0-9]+\.[0-9]+\.[0-9]+)', `vorbiscomment --version 2>&1`, $r)) {
                throw new getid3_exception('Fatal: vorbiscomment binary not available.');
            }
            if (strnatcmp($r[1], '1.0.0') == -1) {
                throw new getid3_exception('Fatal: vorbiscomment version 1.0.0 or newer is required, available version: ' . $r[1] . '.');
            }

            $initialized = true;
        }

        parent::__construct($filename);
    }
    
    
    public function read() {
        
        // read info with vorbiscomment
        if (!$info = trim(`vorbiscomment -l "$this->filename"`)) {
            return;
        }
        
        // process info
        foreach (explode("\n", $info) as $line) {
            
            $pos    = strpos($line, '=');
            
            $key    = strtolower(substr($line, 0, $pos));
            $value  = substr($line, $pos+1);

            $this->comments[$key][] = $value;
        }

        // convert single element arrays to string
        foreach ($this->comments as $key => $value) {
            if (sizeof($value) == 1) {
                $this->comments[$key] = $value[0];
            }
        }
        
        return true;
    }
    
    
    public function write() {
        
        // create temp file with new comments
        $temp_filename = tempnam('*', 'getID3');
        if (!$fp = @fopen($temp_filename, 'wb')) {
            throw new getid3_exception('Could not write temporary file.');
        }
        fwrite($fp, $this->generate_tag());
        fclose($fp);
        
        // write comments
        $this->save_permissions();
        if ($error = `vorbiscomment -w --raw -c "$temp_filename" "$this->filename" 2>&1`) {
            throw new getid3_exception('Fatal: vorbiscomment returned error: ' . $error);          
        }
        $this->restore_permissions();
        
        // success
        @unlink($temp_filename);
        return true;
    }


    protected function generate_tag() {
        
        if (!$this->comments) {
            throw new getid3_exception('Cannot write empty tag, use remove() instead.');
        }

        $result = '';
            
        foreach ($this->comments as $key => $values) {
            
            // A case-insensitive vobiscomment field name that may consist of ASCII 0x20 through 0x7D, 0x3D ('=') excluded. 
            // ASCII 0x41 through 0x5A  inclusive (A-Z) is to be considered equivalent to ASCII 0x61 through 0x7A inclusive (a-z).
            if (preg_match("/[^\x20-\x7D]|\x3D/", $key)) {
                throw new getid3_exception('Field name "' . $key . '" contains invalid character(s).');
            }
            
            $key = strtolower($key);
            
            if (!is_array($values)) {
                $values = array ($values);
            }
            
            foreach ($values as $value) {
                if (strstr($value, "\n") || strstr($value, "\r")) {
                    throw new getid3_exception('Multi-line comments not supported (value contains \n or \r)');
                }
                $result .= $key . '=' . $value . "\n";
            }

        }
        
        return $result;
    }    
        
    
    public function remove() {
        
        // create temp file with new comments
        $temp_filename = tempnam('*', 'getID3');
        if (!$fp = @fopen($temp_filename, 'wb')) {
            throw new getid3_exception('Could not write temporary file.');
        }
        fwrite($fp, '');
        fclose($fp);
        
        // write comments
        $this->save_permissions();
        if ($error = `vorbiscomment -w --raw -c "$temp_filename" "$this->filename" 2>&1`) {
            throw new getid3_exception('Fatal: vorbiscomment returned error: ' . $error);          
        }
        $this->restore_permissions();
        
        // success when removing non-existant tag 
        @unlink($temp_filename);
        return true;
    }

}

?>
