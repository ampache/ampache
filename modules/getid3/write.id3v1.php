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
// | write.id3v1.php                                                      |
// | writing module for id3v1 tags                                        |
// | dependencies: module.tag.id3v1.php.                                  |
// +----------------------------------------------------------------------+
//
// $Id: write.id3v1.php,v 1.15 2006/11/20 16:09:33 ah Exp $



class getid3_write_id3v1 extends getid3_handler_write
{
    public $title;
    public $artist;
    public $album;
    public $year;
    public $genre_id;
    public $genre;
    public $comment;
    public $track;


    public function read() {
        
        $engine = new getid3;
        $engine->filename = $this->filename;
        $engine->fp = fopen($this->filename, 'rb');
        $engine->include_module('tag.id3v1');
        
        $tag = new getid3_id3v1($engine);
        $tag->Analyze();
        
        if (!isset($engine->info['id3v1'])) {
            return;
        }
        
        $this->title    = $engine->info['id3v1']['title'];
        $this->artist   = $engine->info['id3v1']['artist'];
        $this->album    = $engine->info['id3v1']['album'];
        $this->year     = $engine->info['id3v1']['year'];
        $this->genre_id = $engine->info['id3v1']['genre_id'];
        $this->genre    = $engine->info['id3v1']['genre'];
        $this->comment  = $engine->info['id3v1']['comment'];
        $this->track    = $engine->info['id3v1']['track'];
        
        return true;
    }
    
    
    public function write() {
        
        if (!$fp = @fopen($this->filename, 'r+b')) {
            throw new getid3_exception('Could not open r+b: ' . $this->filename);
        }
        
        // seek to end minus 128 bytes
        fseek($fp, -128, SEEK_END);
        
        // overwrite existing ID3v1 tag
        if (fread($fp, 3) == 'TAG') {
            fseek($fp, -128, SEEK_END); 
        } 
        
        // append new ID3v1 tag
        else {
            fseek($fp, 0, SEEK_END);    
        }

        fwrite($fp, $this->generate_tag(), 128);
        
        fclose($fp);
        clearstatcache();
        
        return true;
    }


    protected function generate_tag() {
        
        $result  = 'TAG';
        $result .= str_pad(trim(substr($this->title,  0, 30)), 30, "\x00", STR_PAD_RIGHT);
        $result .= str_pad(trim(substr($this->artist, 0, 30)), 30, "\x00", STR_PAD_RIGHT);
        $result .= str_pad(trim(substr($this->album,  0, 30)), 30, "\x00", STR_PAD_RIGHT);
        $result .= str_pad(trim(substr($this->year,   0,  4)),  4, "\x00", STR_PAD_LEFT);
        
        if (!empty($this->track) && ($this->track > 0) && ($this->track <= 255)) {
            
            $result .= str_pad(trim(substr($this->comment, 0, 28)), 28, "\x00", STR_PAD_RIGHT);
            $result .= "\x00";
            $result .= chr($this->track);
        } 
        else {
            $result .= str_pad(trim(substr($comment, 0, 30)), 30, "\x00", STR_PAD_RIGHT);
        }
        
        // both genre and genre_id set
        if ($this->genre && $this->genre_id) {
            if ($this->genre != getid3_id3v1::LookupGenreName($this->genre_id)) {
                throw new getid3_exception('Genre and genre_id does not match. Unset one and the other will be determined automatically.');
            }
        }
        
        // only genre set
        elseif ($this->genre) {
            $this->genre_id = getid3_id3v1::LookupGenreID($this->genre);
        }
        
        // only genre_id set
        else {
            if ($this->genre_id < 0  ||  $this->genre_id > 147) {
                $this->genre_id = 255; // 'unknown' genre
            }
            $this->genre = getid3_id3v1::LookupGenreName($this->genre_id);
        }
        
        $result .= chr(intval($this->genre_id));

        return $result;
    }    
        
    
    public function remove() {
        
        if (!$fp = @fopen($this->filename, 'r+b')) {
            throw new getid3_exception('Could not open r+b: ' . $filename);
        }
        
        fseek($fp, -128, SEEK_END);
        if (fread($fp, 3) == 'TAG') {
            ftruncate($fp, filesize($this->filename) - 128);
            fclose($fp);
            clearstatcache();
        }
        
        // success when removing non-existant tag 
        return true;
    }

}

?>