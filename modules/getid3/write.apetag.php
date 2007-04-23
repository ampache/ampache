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
// | write.apetag.php                                                     |
// | writing module for ape tags                                          |
// | dependencies: module.tag.apetag.php                                  |
// | dependencies: module.tag.id3v1.php                                   |
// | dependencies: module.tag.lyrics3.php                                 |
// +----------------------------------------------------------------------+
//
// $Id: write.apetag.php,v 1.9 2006/11/20 16:13:31 ah Exp $


class getid3_write_apetag extends getid3_handler_write
{
    
    public $comments;
    

    public function read() {
        
        $engine = new getid3;
        $engine->filename = $this->filename;
        $engine->fp = fopen($this->filename, 'rb');
        $engine->include_module('tag.apetag');
        
        $tag = new getid3_apetag($engine);
        $tag->Analyze();

        if (!isset($engine->info['ape']['comments'])) {
            return;
        }
        
        $this->comments = $engine->info['ape']['comments'];
        
        // convert single element arrays to string
        foreach ($this->comments as $key => $value) {
            if (sizeof($value) == 1) {
                $this->comments[$key] = $value[0];
            }
        }
            
        return true;
    }


    public function write() {
        
        // remove existing apetag
        $this->remove();
        
        $engine = new getid3;
        $engine->filename = $this->filename;
        $engine->fp = fopen($this->filename, 'rb');
        $engine->include_module('tag.id3v1');
        $engine->include_module('tag.lyrics3');
        
        $tag = new getid3_id3v1($engine);
        $tag->Analyze();
        
        $tag = new getid3_lyrics3($engine);
        $tag->Analyze();

        $apetag = $this->generate_tag();
            
        if (!$fp = @fopen($this->filename, 'a+b')) {
            throw new getid3_exception('Could not open a+b: ' . $this->filename);
        }

        // init: audio ends at eof
        $post_audio_offset = filesize($this->filename);
        
        // lyrics3 tag present
        if (@$engine->info['lyrics3']['tag_offset_start']) {
            
            // audio ends before lyrics3 tag
            $post_audio_offset = @$engine->info['lyrics3']['tag_offset_start'];
        }

        // id3v1 tag present
        elseif (@$engine->info['id3v1']['tag_offset_start']) {
            
            // audio ends before id3v1 tag
            $post_audio_offset = $engine->info['id3v1']['tag_offset_start'];
        }

        // seek to end of audio data
        fseek($fp, $post_audio_offset, SEEK_SET);

        // save data after audio data
        $post_audio_data = '';
        if (filesize($this->filename) > $post_audio_offset) {
            $post_audio_data = fread($fp, filesize($this->filename) - $post_audio_offset);
        }

        // truncate file before start of new apetag
        fseek($fp, $post_audio_offset, SEEK_SET);
        ftruncate($fp, ftell($fp));
        
        // write new apetag
        fwrite($fp, $apetag, strlen($apetag));
        
        // rewrite data after audio 
        if (!empty($post_audio_data)) {
            fwrite($fp, $post_audio_data, strlen($post_audio_data));
        }
        
        fclose($fp);
        clearstatcache();
        
        return true;
    }


    public function remove() {
        
        $engine = new getid3;
        $engine->filename = $this->filename;
        $engine->fp = fopen($this->filename, 'rb');
        $engine->include_module('tag.apetag');

        $tag = new getid3_apetag($engine);
        $tag->Analyze();

        if (isset($engine->info['ape']['tag_offset_start']) && isset($engine->info['ape']['tag_offset_end'])) {
            
            if (!$fp = @fopen($this->filename, 'a+b')) {
                throw new getid3_exception('Could not open a+b: ' . $this->filename);
            }

            // get data after apetag
            if (filesize($this->filename) > $engine->info['ape']['tag_offset_end']) {
                fseek($fp, $engine->info['ape']['tag_offset_end'], SEEK_SET);
                $data_after_ape = fread($fp, filesize($this->filename) - $engine->info['ape']['tag_offset_end']);
            }

            // truncate file before start of apetag 
            ftruncate($fp, $engine->info['ape']['tag_offset_start']);

            // rewrite data after apetag
            if (isset($data_after_ape)) {
                fseek($fp, $engine->info['ape']['tag_offset_start'], SEEK_SET);
                fwrite($fp, $data_after_ape, strlen($data_after_ape));
            }
            
            fclose($fp);
            clearstatcache();
        }
        
        // success when removing non-existant tag 
        return true;
    }


    protected function generate_tag() {
        
        // NOTE: All data passed to this function must be UTF-8 format

        $items = array();
        if (!is_array($this->comments)) {
            throw new getid3_exception('Cannot write empty tag, use remove() instead.');
        }
        
        foreach ($this->comments as $key => $values) {
            
            // http://www.personal.uni-jena.de/~pfk/mpp/sv8/apekey.html
            // A case-insensitive vobiscomment field name that may consist of ASCII 0x20 through 0x7E. 
            // ASCII 0x41 through 0x5A  inclusive (A-Z) is to be considered equivalent to ASCII 0x61 through 0x7A inclusive (a-z).
            if (preg_match("/[^\x20-\x7E]/", $key)) {
                throw new getid3_exception('Field name "' . $key . '" contains invalid character(s).');
            }
            
            $key = strtolower($key);
            
            // convert single value comment to array
            if (!is_array($values)) {
                $values = array ($values);
            }

            $value_array = array ();
            foreach ($values as $value) {
                $value_array[] = str_replace("\x00", '', $value);
            }
            $value_string = implode("\x00", $value_array);

            // length of the assigned value in bytes
            $tag_item  = getid3_lib::LittleEndian2String(strlen($value_string), 4);
            
            $tag_item .= "\x00\x00\x00\x00" . $key . "\x00" . $value_string;

            $items[] = $tag_item;
        }

        return $this->generate_header_footer($items, true) . implode('', $items) . $this->generate_header_footer($items, false);
    }
    

    protected function generate_header_footer(&$items, $is_header=false) {
        
        $comments_length = 0;
        foreach ($items as $item_data) {
            $comments_length += strlen($item_data);
        }

        $header  = 'APETAGEX';
        $header .= getid3_lib::LittleEndian2String(2000, 4);
        $header .= getid3_lib::LittleEndian2String(32 + $comments_length, 4);
        $header .= getid3_lib::LittleEndian2String(count($items), 4);
        $header .= $this->generate_flags(true, true, $is_header, 0, false);
        $header .= str_repeat("\x00", 8);

        return $header;
    }
    

    protected function generate_flags($header=true, $footer=true, $is_header=false, $encoding_id=0, $read_only=false) {
        
        $flags = array_fill(0, 4, 0);
        
        // Tag contains a header
        if ($header) {
            $flags[0] |= 0x80; 
        }
        
        // Tag contains no footer
        if (!$footer) {
            $flags[0] |= 0x40; 
        }
        
        // This is the header, not the footer
        if ($is_header) {
            $flags[0] |= 0x20; 
        }

        // 0: Item contains text information coded in UTF-8
        // 1: Item contains binary information °)
        // 2: Item is a locator of external stored information °°)
        // 3: reserved
        $flags[3] |= ($encoding_id << 1);

        // Tag or Item is Read Only
        if ($read_only) {
            $flags[3] |= 0x01; 
        }

        return chr($flags[3]).chr($flags[2]).chr($flags[1]).chr($flags[0]);
    }
    
}

?>