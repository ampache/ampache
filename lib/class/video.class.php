<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

class Video extends database_object implements media
{
    public $id;
    public $title;
    public $enabled;
    public $file;
    public $size;
    public $video_codec;
    public $audio_codec;
    public $resolution_x;
    public $resolution_y;
    public $time;
    public $mime;
    public $release_date;
    public $catalog;

    public $type;
    public $tags;
    public $f_title;
    public $f_full_title;
    public $f_time;
    public $f_time_h;
    public $link;
    public $f_link;
    public $f_codec;
    public $f_resolution;
    public $f_tags;
    public $f_length;
    public $f_file;
    public $f_release_date;

    /**
     * Constructor
     * This pulls the information from the database and returns
     * a constructed object
     */
    public function __construct($id)
    {
        // Load the data from the database
        $info = $this->get_info($id, 'video');
        foreach ($info as $key=>$value) {
            $this->$key = $value;
        }

        $data = pathinfo($this->file);
        $this->type = strtolower($data['extension']);

        return true;

    } // Constructor

    public static function create_from_id($video_id)
    {
        $dtypes = self::get_derived_types();
        foreach ($dtypes as $dtype) {
            $sql = "SELECT `id` FROM `" . strtolower($dtype) . "` WHERE `id` = ?";
            $db_results = Dba::read($sql, array($video_id));
            if ($results = Dba::fetch_assoc($db_results)) {
                if ($results['id']) {
                    return new $dtype($video_id);
                }
            }
        }
        return new Video($video_id);
    }

    /**
     * build_cache
     * Build a cache based on the array of ids passed, saves lots of little queries
     */
    public static function build_cache($ids=array())
    {
        if (!is_array($ids) OR !count($ids)) { return false; }

        $idlist = '(' . implode(',',$ids) . ')';

        $sql = "SELECT * FROM `video` WHERE `video`.`id` IN $idlist";
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            parent::add_to_cache('video',$row['id'],$row);
        }

    } // build_cache

    /**
     * format
     * This formats a video object so that it is human readable
     */
    public function format()
    {
        $this->f_title = scrub_out($this->title);
        $this->f_full_title = $this->f_title;
        $this->link = AmpConfig::get('web_path') . "/video.php?action=show_video&video_id=" . $this->id;
        if (strtolower(get_class($this)) != 'video') {
            $this->link .= '&type=' . get_class($this);
        }
        $this->f_link = "<a href=\"" . $this->link . "\" title=\"" . scrub_out($this->f_title) . "\"> " . scrub_out($this->f_title) . "</a>";
        $this->f_codec = $this->video_codec . ' / ' . $this->audio_codec;
        $this->f_resolution = $this->resolution_x . 'x' . $this->resolution_y;

        // Format the Time
        $min = floor($this->time/60);
        $sec = sprintf("%02d", ($this->time%60));
        $this->f_time = $min . ":" . $sec;
        $hour = sprintf("%02d", floor($min/60));
        $min_h = sprintf("%02d", ($min%60));
        $this->f_time_h = $hour . ":" . $min_h . ":" . $sec;

        // Get the top tags
        $this->tags = Tag::get_top_tags('video', $this->id);
        $this->f_tags = Tag::get_display($this->tags, true, 'video');

        $this->f_length = floor($this->time/60) . ' ' .  T_('minutes');
        $this->f_file = $this->f_title . '.' . $this->type;
        if ($this->release_date) {
            $this->f_release_date = date('Y-m-d', $this->release_date);
        }

    } // format

    /**
     * gc
     *
     * Cleans up the inherited object tables
     */
    public static function gc()
    {
        Movie::gc();
        TVShow_Episode::gc();
        TVShow_Season::gc();
        TVShow::gc();
        Personal_Video::gc();
        Clip::gc();
    }

    public function get_stream_types()
    {
        return array('native');

    } // native_stream

    /**
     * play_url
     * This returns a "PLAY" url for the video in question here, this currently feels a little
     * like a hack, might need to adjust it in the future
     */
    public static function play_url($oid, $additional_params='',$sid='',$force_http='')
    {
        $video = new Video($oid);

        if (!$video->id) { return false; }

        $uid = $GLOBALS['user']->id ? scrub_out($GLOBALS['user']->id) : '-1';
        $oid = intval($video->id);

        $url = Stream::get_base_url() . "type=video&uid=" . $uid . "&oid=" . $oid;

        return Stream_URL::format($url . $additional_params);

    } // play_url

    /**
     * get_transcode_settings
     *
     * FIXME: Video transcoding is not implemented
     */
    public function get_transcode_settings($target = null)
    {
        return false;
    }

    private static function get_derived_types()
    {
        return array('TVShow_Episode', 'Movie', 'Clip', 'Personal_Video');
    }

    public static function validate_type($type)
    {
        $dtypes = self::get_derived_types();
        foreach ($dtypes as $dtype) {
            if (strtolower($type) == strtolower($dtype))
                return $type;
        }

        return 'Video';
    }

    /**
     * type_to_mime
     *
     * Returns the mime type for the specified file extension/type
     */
    public static function type_to_mime($type)
    {
        // FIXME: This should really be done the other way around.
        // Store the mime type in the database, and provide a function
        // to make it a human-friendly type.
        switch ($type) {
            case 'avi':
                return 'video/avi';
            case 'ogg':
                return 'application/ogg';
            case 'wmv':
                return 'audio/x-ms-wmv';
            case 'mp4':
            case 'm4v':
                return 'video/mp4';
            case 'mkv':
                return 'video/x-matroska';
            default:
                return 'video/mpeg';
        }
    }

    public static function insert($data, $gtypes = array(), $options = array())
    {
        $rezx           = intval($data['resolution_x']);
        $rezy           = intval($data['resolution_y']);
        $release_date   = intval($data['release_date']);
        $tags           = $data['genre'];

        $sql = "INSERT INTO `video` (`file`,`catalog`,`title`,`video_codec`,`audio_codec`,`resolution_x`,`resolution_y`,`size`,`time`,`mime`,`release_date`,`addition_time`) " .
            " VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = array($data['file'], $data['catalog'], $data['title'], $data['video_codec'], $data['audio_codec'], $rezx, $rezy, $data['size'], $data['time'], $data['mime'], $release_date, time());
        Dba::write($sql, $params);
        $vid = Dba::insert_id();

        if (is_array($tags)) {
            foreach ($tags as $tag) {
                $tag = trim($tag);
                if (!empty($tag)) {
                    Tag::add('video', $vid, $tag, false);
                }
            }
        }

        if ($data['art'] && $options['gather_art']) {
            $art = new Art($vid, 'video');
            $art->insert_url($data['art']);
        }

        $data['id'] = $vid;
        self::insert_video_type($data, $gtypes, $options);
    }

    private static function insert_video_type($data, $gtypes, $options = array())
    {
        if (count($gtypes) > 0) {
            $gtype = $gtypes[0];
            switch ($gtype) {
                case 'tvshow':
                    return TVShow_Episode::insert($data, $options);
                case 'movie':
                    return Movie::insert($data, $options);
                case 'clip':
                    return Clip::insert($data, $options);
                case 'personal_video':
                    return Personal_Video::insert($data, $options);
                default:
                    // Do nothing, video entry already created and no additional data for now
                    break;
            }
        }

        return $data['id'];
    }

} // end Video class
