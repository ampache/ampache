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
// | module.tag.apetag.php                                                |
// | module for analyzing APE tags                                        |
// | dependencies: NONE                                                   |
// +----------------------------------------------------------------------+
//
// $Id: module.tag.apetag.php,v 1.5 2006/11/16 14:05:21 ah Exp $



class getid3_apetag extends getid3_handler
{
    /*
    ID3v1_TAG_SIZE     = 128;
    APETAG_HEADER_SIZE = 32;
    LYRICS3_TAG_SIZE   = 10;
    */

    public $option_override_end_offset = 0;



    public function Analyze() {

        $getid3 = $this->getid3;

        if ($this->option_override_end_offset == 0) {

            fseek($getid3->fp, 0 - 170, SEEK_END);                                                              // 170 = ID3v1_TAG_SIZE + APETAG_HEADER_SIZE + LYRICS3_TAG_SIZE
            $apetag_footer_id3v1 = fread($getid3->fp, 170);                                                     // 170 = ID3v1_TAG_SIZE + APETAG_HEADER_SIZE + LYRICS3_TAG_SIZE

            // APE tag found before ID3v1
            if (substr($apetag_footer_id3v1, strlen($apetag_footer_id3v1) - 160, 8) == 'APETAGEX') {            // 160 = ID3v1_TAG_SIZE + APETAG_HEADER_SIZE
                $getid3->info['ape']['tag_offset_end'] = filesize($getid3->filename) - 128;                     // 128 = ID3v1_TAG_SIZE
            }

            // APE tag found, no ID3v1
            elseif (substr($apetag_footer_id3v1, strlen($apetag_footer_id3v1) - 32, 8) == 'APETAGEX') {         // 32 = APETAG_HEADER_SIZE
                $getid3->info['ape']['tag_offset_end'] = filesize($getid3->filename);
            }

        }
        else {

            fseek($getid3->fp, $this->option_override_end_offset - 32, SEEK_SET);                               // 32 = APETAG_HEADER_SIZE
            if (fread($getid3->fp, 8) == 'APETAGEX') {
                $getid3->info['ape']['tag_offset_end'] = $this->option_override_end_offset;
            }

        }

        // APE tag not found
        if (!@$getid3->info['ape']['tag_offset_end']) {
            return false;
        }

        // Shortcut
        $info_ape = &$getid3->info['ape'];

        // Read and parse footer
        fseek($getid3->fp, $info_ape['tag_offset_end'] - 32, SEEK_SET);                                         // 32 = APETAG_HEADER_SIZE
        $apetag_footer_data = fread($getid3->fp, 32);
        if (!($this->ParseAPEHeaderFooter($apetag_footer_data, $info_ape['footer']))) {
            throw new getid3_exception('Error parsing APE footer at offset '.$info_ape['tag_offset_end']);
        }

        if (isset($info_ape['footer']['flags']['header']) && $info_ape['footer']['flags']['header']) {
            fseek($getid3->fp, $info_ape['tag_offset_end'] - $info_ape['footer']['raw']['tagsize'] - 32, SEEK_SET);
            $info_ape['tag_offset_start'] = ftell($getid3->fp);
            $apetag_data = fread($getid3->fp, $info_ape['footer']['raw']['tagsize'] + 32);
        }
        else {
            $info_ape['tag_offset_start'] = $info_ape['tag_offset_end'] - $info_ape['footer']['raw']['tagsize'];
            fseek($getid3->fp, $info_ape['tag_offset_start'], SEEK_SET);
            $apetag_data = fread($getid3->fp, $info_ape['footer']['raw']['tagsize']);
        }
        $getid3->info['avdataend'] = $info_ape['tag_offset_start'];

        if (isset($getid3->info['id3v1']['tag_offset_start']) && ($getid3->info['id3v1']['tag_offset_start'] < $info_ape['tag_offset_end'])) {
            $getid3->warning('ID3v1 tag information ignored since it appears to be a false synch in APEtag data');
            unset($getid3->info['id3v1']);
        }

        $offset = 0;
        if (isset($info_ape['footer']['flags']['header']) && $info_ape['footer']['flags']['header']) {
            if (!$this->ParseAPEHeaderFooter(substr($apetag_data, 0, 32), $info_ape['header'])) {
                throw new getid3_exception('Error parsing APE header at offset '.$info_ape['tag_offset_start']);
            }
            $offset = 32;
        }

        // Shortcut
        $getid3->info['replay_gain'] = array ();
        $info_replaygain = &$getid3->info['replay_gain'];

        for ($i = 0; $i < $info_ape['footer']['raw']['tag_items']; $i++) {
            $value_size = getid3_lib::LittleEndian2Int(substr($apetag_data, $offset,     4));
            $item_flags = getid3_lib::LittleEndian2Int(substr($apetag_data, $offset + 4, 4));
            $offset += 8;

            if (strstr(substr($apetag_data, $offset), "\x00") === false) {
                throw new getid3_exception('Cannot find null-byte (0x00) seperator between ItemKey #'.$i.' and value. ItemKey starts ' . $offset . ' bytes into the APE tag, at file offset '.($info_ape['tag_offset_start'] + $offset));
            }

            $item_key_length = strpos($apetag_data, "\x00", $offset) - $offset;
            $item_key        = strtolower(substr($apetag_data, $offset, $item_key_length));

            // Shortcut
            $info_ape['items'][$item_key] = array ();
            $info_ape_items_current = &$info_ape['items'][$item_key];

            $offset += $item_key_length + 1; // skip 0x00 terminator
            $info_ape_items_current['data'] = substr($apetag_data, $offset, $value_size);
            $offset += $value_size;


            $info_ape_items_current['flags'] = $this->ParseAPEtagFlags($item_flags);

            switch ($info_ape_items_current['flags']['item_contents_raw']) {
                case 0: // UTF-8
                case 3: // Locator (URL, filename, etc), UTF-8 encoded
                    $info_ape_items_current['data'] = explode("\x00", trim($info_ape_items_current['data']));
                    break;

                default: // binary data
                    break;
            }

            switch (strtolower($item_key)) {
                case 'replaygain_track_gain':
                    $info_replaygain['track']['adjustment'] = (float)str_replace(',', '.', $info_ape_items_current['data'][0]); // float casting will see "0,95" as zero!
                    $info_replaygain['track']['originator'] = 'unspecified';
                    break;

                case 'replaygain_track_peak':
                    $info_replaygain['track']['peak']       = (float)str_replace(',', '.', $info_ape_items_current['data'][0]); // float casting will see "0,95" as zero!
                    $info_replaygain['track']['originator'] = 'unspecified';
                    if ($info_replaygain['track']['peak'] <= 0) {
                        $getid3->warning('ReplayGain Track peak from APEtag appears invalid: '.$info_replaygain['track']['peak'].' (original value = "'.$info_ape_items_current['data'][0].'")');
                    }
                    break;

                case 'replaygain_album_gain':
                    $info_replaygain['album']['adjustment'] = (float)str_replace(',', '.', $info_ape_items_current['data'][0]); // float casting will see "0,95" as zero!
                    $info_replaygain['album']['originator'] = 'unspecified';
                    break;

                case 'replaygain_album_peak':
                    $info_replaygain['album']['peak']       = (float)str_replace(',', '.', $info_ape_items_current['data'][0]); // float casting will see "0,95" as zero!
                    $info_replaygain['album']['originator'] = 'unspecified';
                    if ($info_replaygain['album']['peak'] <= 0) {
                        $getid3->warning('ReplayGain Album peak from APEtag appears invalid: '.$info_replaygain['album']['peak'].' (original value = "'.$info_ape_items_current['data'][0].'")');
                    }
                    break;

                case 'mp3gain_undo':
                    list($mp3gain_undo_left, $mp3gain_undo_right, $mp3gain_undo_wrap) = explode(',', $info_ape_items_current['data'][0]);
                    $info_replaygain['mp3gain']['undo_left']  = intval($mp3gain_undo_left);
                    $info_replaygain['mp3gain']['undo_right'] = intval($mp3gain_undo_right);
                    $info_replaygain['mp3gain']['undo_wrap']  = (($mp3gain_undo_wrap == 'Y') ? true : false);
                    break;

                case 'mp3gain_minmax':
                    list($mp3gain_globalgain_min, $mp3gain_globalgain_max) = explode(',', $info_ape_items_current['data'][0]);
                    $info_replaygain['mp3gain']['globalgain_track_min'] = intval($mp3gain_globalgain_min);
                    $info_replaygain['mp3gain']['globalgain_track_max'] = intval($mp3gain_globalgain_max);
                    break;

                case 'mp3gain_album_minmax':
                    list($mp3gain_globalgain_album_min, $mp3gain_globalgain_album_max) = explode(',', $info_ape_items_current['data'][0]);
                    $info_replaygain['mp3gain']['globalgain_album_min'] = intval($mp3gain_globalgain_album_min);
                    $info_replaygain['mp3gain']['globalgain_album_max'] = intval($mp3gain_globalgain_album_max);
                    break;

                case 'tracknumber':
                    foreach ($info_ape_items_current['data'] as $comment) {
                        $info_ape['comments']['track'][] = $comment;
                    }
                    break;

                default:
                    foreach ($info_ape_items_current['data'] as $comment) {
                        $info_ape['comments'][strtolower($item_key)][] = $comment;
                    }
                    break;
            }

        }
        if (empty($info_replaygain)) {
            unset($getid3->info['replay_gain']);
        }

        return true;
    }



    protected function ParseAPEheaderFooter($data, &$target) {

        // http://www.uni-jena.de/~pfk/mpp/sv8/apeheader.html

        if (substr($data, 0, 8) != 'APETAGEX') {
            return false;
        }

        // shortcut
        $target['raw'] = array ();
        $target_raw = &$target['raw'];

        $target_raw['footer_tag']   = 'APETAGEX';

        getid3_lib::ReadSequence("LittleEndian2Int", $target_raw, $data, 8,
            array (
                'version'      => 4,
                'tagsize'      => 4,
                'tag_items'    => 4,
                'global_flags' => 4
            )
        );
        $target_raw['reserved'] = substr($data, 24, 8);

        $target['tag_version'] = $target_raw['version'] / 1000;
        if ($target['tag_version'] >= 2) {

            $target['flags'] = $this->ParseAPEtagFlags($target_raw['global_flags']);
        }

        return true;
    }



    protected function ParseAPEtagFlags($raw_flag_int) {

        // "Note: APE Tags 1.0 do not use any of the APE Tag flags.
        // All are set to zero on creation and ignored on reading."
        // http://www.uni-jena.de/~pfk/mpp/sv8/apetagflags.html

        $target['header']            = (bool) ($raw_flag_int & 0x80000000);
        $target['footer']            = (bool) ($raw_flag_int & 0x40000000);
        $target['this_is_header']    = (bool) ($raw_flag_int & 0x20000000);
        $target['item_contents_raw'] =        ($raw_flag_int & 0x00000006) >> 1;
        $target['read_only']         = (bool) ($raw_flag_int & 0x00000001);

        $target['item_contents']     = getid3_apetag::APEcontentTypeFlagLookup($target['item_contents_raw']);

        return $target;
    }



    public static function APEcontentTypeFlagLookup($content_type_id) {

        static $lookup = array (
            0 => 'utf-8',
            1 => 'binary',
            2 => 'external',
            3 => 'reserved'
        );
        return (isset($lookup[$content_type_id]) ? $lookup[$content_type_id] : 'invalid');
    }



    public static function APEtagItemIsUTF8Lookup($item_key) {

        static $lookup = array (
            'title',
            'subtitle',
            'artist',
            'album',
            'debut album',
            'publisher',
            'conductor',
            'track',
            'composer',
            'comment',
            'copyright',
            'publicationright',
            'file',
            'year',
            'record date',
            'record location',
            'genre',
            'media',
            'related',
            'isrc',
            'abstract',
            'language',
            'bibliography'
        );
        return in_array(strtolower($item_key), $lookup);
    }

}

?>