<?php
// +----------------------------------------------------------------------+
// | PHP version 5                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2009 James Heinrich, Allan Hansen                 |
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
// | write.lyrics3.php                                                    |
// | writing module for lyrics3 2.00 tags                                 |
// | dependencies: module.tag.lyrics3.php.                                |
// | dependencies: module.tag.id3v1.php                                   |
// +----------------------------------------------------------------------+
//
// $Id: write.lyrics3.php,v 1.5 2006/11/20 16:13:39 ah Exp $



class getid3_write_lyrics3 extends getid3_handler_write
{
    public $synched;
    public $random_inhibited;

    public $lyrics;
    public $comment;
    public $author;
    public $title;
    public $artist;
    public $album;
    public $images;


    public function read() {

        $engine = new getid3;
        $engine->filename = $this->filename;
        $engine->fp = fopen($this->filename, 'rb');
        $engine->include_module('tag.lyrics3');

        $tag = new getid3_lyrics3($engine);
        $tag->Analyze();

        if (!isset($engine->info['lyrics3']['tag_offset_start'])) {
            return;
        }

        $this->lyrics  = @$engine->info['lyrics3']['raw']['LYR'];
        $this->comment = @$engine->info['lyrics3']['raw']['INF'];
        $this->author  = @$engine->info['lyrics3']['raw']['AUT'];
        $this->title   = @$engine->info['lyrics3']['raw']['ETT'];
        $this->artist  = @$engine->info['lyrics3']['raw']['EAR'];
        $this->album   = @$engine->info['lyrics3']['raw']['EAL'];
        $this->images  = @$engine->info['lyrics3']['raw']['IMG'];

        return true;
    }


    public function write() {

        // remove existing apetag
        $this->remove();

        $engine = new getid3;
        $engine->filename = $this->filename;
        $engine->fp = fopen($this->filename, 'rb');
        $engine->include_module('tag.id3v1');

        $tag = new getid3_id3v1($engine);
        $tag->Analyze();

        $apetag = $this->generate_tag();

        if (!$fp = @fopen($this->filename, 'a+b')) {
            throw new getid3_exception('Could not open a+b: ' . $this->filename);
        }

        // init: audio ends at eof
        $post_audio_offset = filesize($this->filename);

        // id3v1 tag present
        if (@$engine->info['id3v1']['tag_offset_start']) {

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


    protected function generate_tag() {

        // define fields
        static $fields = array (
            'lyrics'  => 'LYR',
            'comment' => 'INF',
            'author'  => 'AUT',
            'title'   => 'ETT',
            'artist'  => 'EAR',
            'album'   => 'EAL',
            'images'  => 'IMG'
        );

        // loop thru fields and add to frames
        $frames = '';
        foreach ($fields as $field => $frame_name) {

            // field set?
            if ($this->$field) {
                $frames .= $frame_name . str_pad(strlen($this->$field), 5, '0', STR_PAD_LEFT) . $this->$field;
            }
        }

        if (!$frames) {
            throw new getid3_exception('Cannot write empty tag, use remove() instead.');
        }

        // header
        $result  = 'LYRICSBEGIN';

        // indicator frame
        $result .= 'IND00003' . ($this->lyrics ? '1' : '0') . ($this->synched ? '1' : '0') . ($this->random_inibited ? '1' : '0');

        // other frames
        $result .= $frames;

        // footer
        $result .= str_pad(strlen($result), 6, '0', STR_PAD_LEFT);
        $result .= 'LYRICS200';

        return $result;
    }


    public function remove() {

        $engine = new getid3;
        $engine->filename = $this->filename;
        $engine->fp = fopen($this->filename, 'rb');
        $engine->include_module('tag.lyrics3');

        $tag = new getid3_lyrics3($engine);
        $tag->Analyze();

        if (isset($engine->info['lyrics3']['tag_offset_start']) && isset($engine->info['lyrics3']['tag_offset_end'])) {

            if (!$fp = @fopen($this->filename, 'a+b')) {
                throw new getid3_exception('Could not open a+b: ' . $this->filename);
            }

            // get data after tag
            fseek($fp, $engine->info['lyrics3']['tag_offset_end'], SEEK_SET);
            $data_after_lyrics3 = '';
            if (filesize($this->filename) > $engine->info['lyrics3']['tag_offset_end']) {
                $data_after_lyrics3 = fread($fp, filesize($this->filename) - $engine->info['lyrics3']['tag_offset_end']);
            }

            // truncate file before start of tag and seek to end
            ftruncate($fp, $engine->info['lyrics3']['tag_offset_start']);

            // rewrite data after tag
            if (!empty($data_after_lyrics3)) {
                fseek($fp, $engine->info['lyrics3']['tag_offset_start'], SEEK_SET);
                fwrite($fp, $data_after_lyrics3, strlen($data_after_lyrics3));
            }

            fclose($fp);
            clearstatcache();
        }

        // success when removing non-existant tag
        return true;
    }
}

?>