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
// $Id: write.id3v2.php,v 1.9 2006/12/25 23:44:23 ah Exp $



class getid3_write_id3v2 extends getid3_handler_write
{
    // NOTE: This module ONLY writes tags in UTF-8. All strings must be UTF-8 encoded. 
    
    // For multiple values, specify "array of type" instead of type for all T??? and IPLS params except TXXX.
    /**2.4
    // For multiple values, specify "array of type" instead of type for all T??? params except TXXX.
    */
    

    // Identification frames
    public $content_group_description;             // TIT1            string
    public $title;                                 // TIT2            string
    public $subtitle;                              // TIT3            string
    public $album;                                 // TALB            string
    public $original_album_title;                  // TOAL            string
    public $track;                                 // TRCK            integer or "integer/integer"  e.g. "10/12"
    public $part_of_set;                           // TPOS            integer or "integer/integer"  e.g. "10/12"
    public $isrc;                                  // TSRC            string

    // Involved persons frames
    public $artist;                                // TPE1            string
    public $band;                                  // TPE2            string
    public $conductor;                             // TPE3            string
    public $remixer;                               // TPE4            string
    public $original_artist;                       // TOPE            string
    public $lyricist;                              // TEXT            string
    public $original_lyricist;                     // TOLY            string
    public $composer;                              // TCOM            string
    public $encoded_by;                            // TENC            string

    // Derived and subjective properties frames
    public $beats_per_minute;                      // TBPM            integer
    public $length;                                // TLEN            integer
    public $initial_key;                           // TKEY            string
    public $language;                              // TLAN            string - ISO-639-2
    public $genre;                                 // TCON            string or integer
    public $file_type;                             // TFLT            string
    public $media_type;                            // TMED            string

    // Rights and license frames
    public $copyright;                             // TCOP            string  - must begin with YEAR and a space
    public $date;                                  // TDAT            string  - DDMM
    public $year;                                  // TYER            string  - YYYY
    public $original_release_year;                 // TORY            string  - YYYY
    public $recording_dates;                       // TRDA            string
    public $time;                                  // TIME            string  - HHMM
    public $publisher;                             // TPUB            string
    public $file_owner;                            // TOWN            string
    public $internet_radio_station_name;           // TRSN            string
    public $internet_radio_station_owner;          // TRSO            string
    public $involved_people_list;                  // IPLS            string

    // Other text frames
    public $original_filename;                     // TOFN            string
    public $playlist_delay;                        // TDLY            integer
    public $encoder_settings;                      // TSSE            string

    // User defined text information frame
    public $user_text;                             // TXXX            array of ( unique_description(string) => value(string) )

    // Comments
    public $comment;                               // COMM

    // URL link frames - details
    public $commercial_information;                // WCOM            url(string)
    public $copyright_information;                 // WCOP            url(string)
    public $url_file;                              // WOAF            url(string)
    public $url_artist;                            // WOAR            url(string)
    public $url_source;                            // WOAS            url(string)
    public $url_station;                           // WORS            url(string)
    public $payment;                               // WPAY            url(string)
    public $url_publisher;                         // WPUB            url(string)

    // User defined URL link frame
    public $url_user;                              // WXXX            array of ( unique_description(string) => url(string) )


    // Unique file identifier
    public $unique_file_identifier;                // UFID

    // Music CD identifier
    public $music_cd_identifier;                   // MCDI

    // Event timing codes
    public $event_timing_codes;                    // ETCO

    // MPEG location lookup table
    public $mpeg_location_lookup_table;            // MLLT

    // Synchronised tempo codes
    public $synchronised_tempo_codes;              // SYTC

    // Unsynchronised lyrics/text transcription
    public $unsynchronised_lyrics;                 // USLT

    // Synchronised lyrics/text
    public $synchronised_lyrics;                   // SYLT

    // Relative volume adjustment (1)
    public $relative_volume_adjustment;            // RVAD

    // Equalisation (1)
    public $equalisation;                          // EQUA

    // Reverb
    public $reverb;                                // RVRB

    // Attached picture
    public $attached_picture;                      // APIC

    // General encapsulated object
    public $general_encapsulated_object;           // GEOB

    // Play counter
    public $play_counter;                          // PCNT

    // Popularimeter
    public $popularimeter;                         // POPM            

    // Recommended buffer size
    public $recommended_buffer_size;               // RBUF

    // Audio encryption
    public $audio_encryption;                      // AENC

    // Linked information
    public $linked_information;                    // LINK

    // Position synchronisation frame
    public $position_synchronisation;              // POSS

    //  Terms of use frame
    public $terms_of_use;                          // USER

    // Ownership frame
    public $ownership;                             // OWNE

    // Commercial frame
    public $commercial;                            // COMR

    // Encryption method registration
    public $encryption_method_registration;        // ENCR

    // Group identification registration
    public $group_identification_registration;     // GRID

    // Private frame
    public $private;                               // PRIV


    /**2.4
        // Identification frames
    public $content_group_description;             // TIT1            string
    public $title;                                 // TIT2            string
    public $subtitle;                              // TIT3            string
    public $album;                                 // TALB            string
    public $original_album_title;                  // TOAL            string
    public $track;                                 // TRCK            integer or "integer/integer"  e.g. "10/12"
    public $part_of_set;                           // TPOS            integer or "integer/integer"  e.g. "10/12"
    public $set_subtitle;                          // TSST            string
    public $isrc;                                  // TSRC            string

    // Involved persons frames
    public $artist;                                // TPE1            string
    public $band;                                  // TPE2            string
    public $conductor;                             // TPE3            string
    public $remixer;                               // TPE4            string
    public $original_artist;                       // TOPE            string
    public $lyricist;                              // TEXT            string
    public $original_lyricist;                     // TOLY            string
    public $composer;                              // TCOM            string
    public $musician_credits_list;                 // TMCL            string
    public $involved_people_list;                  // TIPL            string
    public $encoded_by;                            // TENC            string

    // Derived and subjective properties frames
    public $beats_per_minute;                      // TBPM            integer
    public $length;                                // TLEN            integer
    public $initial_key;                           // TKEY            string
    public $language;                              // TLAN            string - ISO-639-2
    public $genre;                                 // TCON            string or integer
    public $file_type;                             // TFLT            string
    public $media_type;                            // TMED            string
    public $mood;                                  // TMOO            string

    // Rights and license frames
    public $copyright;                             // TCOP            string  - must begin with YEAR and a space
                                                   // TPRO            strign  - must begin with YEAR and a space
    public $publisher;                             // TPUB            string
    public $file_owner;                            // TOWN            string
    public $internet_radio_station_name;           // TRSN            string
    public $internet_radio_station_owner;          // TRSO            string

    // Other text frames
    public $original_filename;                     // TOFN            string
    public $playlist_delay;                        // TDLY            integer
    public $encoding_time;                         // TDEN            timestamp(string)  -  yyyy, yyyy-MM, yyyy-MM-dd, yyyy-MM-ddTHH, yyyy-MM-ddTHH:mm, yyyy-MM-ddTHH:mm:ss. All time stamps are UTC.
    public $original_release_time;                 // TDOR            timestamp(string)  -  yyyy, yyyy-MM, yyyy-MM-dd, yyyy-MM-ddTHH, yyyy-MM-ddTHH:mm, yyyy-MM-ddTHH:mm:ss. All time stamps are UTC.
    public $recording_time;                        // TDRC            timestamp(string)  -  yyyy, yyyy-MM, yyyy-MM-dd, yyyy-MM-ddTHH, yyyy-MM-ddTHH:mm, yyyy-MM-ddTHH:mm:ss. All time stamps are UTC.
    public $release_time;                          // TDRL            timestamp(string)  -  yyyy, yyyy-MM, yyyy-MM-dd, yyyy-MM-ddTHH, yyyy-MM-ddTHH:mm, yyyy-MM-ddTHH:mm:ss. All time stamps are UTC.
    public $tagging_time;                          // TDTG            timestamp(string)  -  yyyy, yyyy-MM, yyyy-MM-dd, yyyy-MM-ddTHH, yyyy-MM-ddTHH:mm, yyyy-MM-ddTHH:mm:ss. All time stamps are UTC.
    public $encoder_settings;                      // TSSE            string
    public $album_sort_order;                      // TSOA            string
    public $performer_sort_order;                  // TSOP            string
    public $title_sort_order;                      // TSOT            string

    // User defined text information frame
    public $user_text;                             // TXXX            array of ( unique_description(string) => value(string) )

    // Comments
    public $comment;                               // COMM

    // URL link frames - details
    public $commercial_information;                // WCOM            url(string)
    public $copyright_information;                 // WCOP            url(string)
    public $url_file;                              // WOAF            url(string)
    public $url_artist;                            // WOAR            url(string)
    public $url_source;                            // WOAS            url(string)
    public $url_station;                           // WORS            url(string)
    public $payment;                               // WPAY            url(string)
    public $url_publisher;                         // WPUB            url(string)

    // User defined URL link frame
    public $url_user;                              // WXXX            array of ( unique_description(string) => url(string) )


    // Unique file identifier
    public $unique_file_identifier;                // UFID

    // Music CD identifier
    public $music_cd_identifier;                   // MCDI

    // Event timing codes
    public $event_timing_codes;                    // ETCO

    // MPEG location lookup table
    public $mpeg_location_lookup_table;            // MLLT

    // Synchronised tempo codes
    public $synchronised_tempo_codes;              // SYTC

    // Unsynchronised lyrics/text transcription
    public $unsynchronised_lyrics;                 // USLT

    // Synchronised lyrics/text
    public $synchronised_lyrics;                   // SYLT

    // Relative volume adjustment (2)
    public $relative_volume_adjustment;            // RVA2

    // Equalisation (2)
    public $equalisation;                          // EQU2

    // Reverb
    public $reverb;                                // RVRB

    // Attached picture
    public $attached_picture;                      // APIC

    // General encapsulated object
    public $general_encapsulated_object;           // GEOB

    // Play counter
    public $play_counter;                          // PCNT

    // Popularimeter
    public $popularimeter;                         // POPM            

    // Recommended buffer size
    public $recommended_buffer_size;               // RBUF

    // Audio encryption
    public $audio_encryption;                      // AENC

    // Linked information
    public $linked_information;                    // LINK

    // Position synchronisation frame
    public $position_synchronisation;              // POSS

    //  Terms of use frame
    public $terms_of_use;                          // USER

    // Ownership frame
    public $ownership;                             // OWNE

    // Commercial frame
    public $commercial;                            // COMR

    // Encryption method registration
    public $encryption_method_registration;        // ENCR

    // Group identification registration
    public $group_identification_registration;     // GRID

    // Private frame
    public $private;                               // PRIV

    // Signature frame
    public $signature;                             // SIGN

    // Seek frame
    public $seek;                                  // SEEK

    // Audio seek point index
    public $audio_seek_point_index;                // ASPI
    */
    
    
    // internal logic
    protected $padded_length   = 4096;              // minimum length of ID3v2 tag in bytes
    protected $previous_frames = array ();

    const major_version = 3;
    

    public function read() {

    }


    public function write() {

        $engine = new getid3;
        $engine->filename = $this->filename;
        $engine->fp = fopen($this->filename, 'rb');
        $engine->include_module('tag.id3v2');

        $tag = new getid3_id3v2($engine);
        $tag->Analyze();

        if (!(int)@$engine->info['avdataoffset']) {
            throw new getid3_exception('No audio data found.');
        }

        $this->padded_length = max(@$engine->info['id3v2']['headerlength'], $this->padded_length);

        $tag = $this->generate_tag();

        // insert-overwrite existing tag (padded to length of old tag if neccesary)
        if (@$engine->info['id3v2']['headerlength'] && ($engine->info['id3v2']['headerlength'] == strlen($tag))) {

            if (!$fp = fopen($this->filename, 'r+b')) {
                throw new getid3_exception('Could not open '.$this->filename.' mode "r+b"');
            }
            fwrite($fp, $tag, strlen($tag));
            fclose($fp);
        }

        // rewrite file - no tag present or new tag longer than old tag
        else

            if (!$fp_source = @fopen($this->filename, 'rb')) {
                throw new getid3_exception('Could not open '.$this->filename.' mode "rb"');
            }
            fseek($fp_source, $engine->info['avdataoffset'], SEEK_SET);

            if (!$fp_temp = @fopen($this->filename.'getid3tmp', 'w+b')) {
                throw new getid3_exception('Could not open '.$this->filename.'getid3tmp mode "w+b"');
            }

            fwrite($fp, $tag, strlen($tag));

            while ($buffer = fread($fp_source, 16384)) {
                fwrite($fp_temp, $buffer, strlen($buffer));
            }

            fclose($fp_temp);
            fclose($fp_source);

            $this->save_permissions();
            unlink($this->filename);
            rename($this->filename.'getid3tmp', $this->filename);
            $this->restore_permissions();
        }

        clearstatcache();

        return true;
    }


    public function remove() {

        $engine = new getid3;
        $engine->filename = $this->filename;
        $engine->fp = fopen($this->filename, 'rb');
        $engine->include_module('tag.id3v2');

        $tag = new getid3_id3v2($engine);
        $tag->Analyze();

        if ((int)@$engine->info['avdataoffset']) {

            if (!$fp_source = @fopen($this->filename, 'rb')) {
                throw new getid3_exception('Could not open '.$this->filename.' mode "rb"');
            }
            fseek($fp_source, $engine->info['avdataoffset'], SEEK_SET);

            if (!$fp_temp = @fopen($this->filename.'getid3tmp', 'w+b')) {
                throw new getid3_exception('Could not open '.$this->filename.'getid3tmp mode "w+b"');
            }

            while ($buffer = fread($fp_source, 16384)) {
                fwrite($fp_temp, $buffer, strlen($buffer));
            }

            fclose($fp_temp);
            fclose($fp_source);

            $this->save_permissions();
            unlink($this->filename);
            rename($this->filename.'getid3tmp', $this->filename);
            $this->restore_permissions();

            clearstatcache();
        }

        // success when removing non-existant tag
        return true;
    }


    protected function generate_tag() {

        $result = '';
                        
        $some_array = array (
            'content_group_description'                        => 'TIT1',
            'title'                                            => 'TIT2',
            'subtitle'                                         => 'TIT3',
        );
        
        foreach ($some_array as $key => $frame_name) {
            
            
            if ($frame_data = $this->generate_frame_data($frame_name, $this->$key)) {
            
                $frame_length = $this->BigEndian2String(strlen($frame_data), 4, false);
                $frame_flags  = $this->generate_frame_flags();
            }
                
            $result .= $frame_name.$frame_length.$frame_flags.$frame_data;
        }
        

        // calc padded length of tag
        while ($this->padded_length < (strlen($result) + 10)) {
            $this->padded_length += 1024;
        }

        // pad up to $padded_length bytes if unpadded tag is shorter than $padded_length
        if ($this->padded_length > (strlen($result) + 10)) {
            $result .= @str_repeat("\x00", $this->padded_length - strlen($result) - 10);
        }
        
        $header  = 'ID3';
        $header .= chr(getid3_id3v2_write::major_version);
        $header .= chr(0);
        $header .= $this->generate_tag_flags();
        $header .= getid3_lib::BigEndian2String(strlen($result), 4, true);

        return $header.$result;
    }


    protected function generate_tag_flags($flags) {

        // %abc00000
        $flag  = (@$flags['unsynchronisation'] ? '1' : '0');  // a - Unsynchronisation
        $flag .= (@$flags['extendedheader']    ? '1' : '0');  // b - Extended header
        $flag .= (@$flags['experimental']      ? '1' : '0');  // c - Experimental indicator
        $flag .= '00000';

        /**2.4
        // %abcd0000
        $flag  = (@$flags['unsynchronisation'] ? '1' : '0');  // a - Unsynchronisation
        $flag .= (@$flags['extendedheader']    ? '1' : '0');  // b - Extended header
        $flag .= (@$flags['experimental']      ? '1' : '0');  // c - Experimental indicator
        $flag .= (@$flags['footer']            ? '1' : '0');  // d - Footer present
        $flag .= '0000';                                      
        */

        return chr(bindec($flag));
    }


    protected function generate_frame_flags($flags) {
    
        // %abc00000 %ijk00000
        $flag1  = (@$flags['tag_alter']             ? '1' : '0');  // a - Tag alter preservation (true == discard)
        $flag1 .= (@$flags['file_alter']            ? '1' : '0');  // b - File alter preservation (true == discard)
        $flag1 .= (@$flags['read_only']             ? '1' : '0');  // c - Read only (true == read only)
        $flag1 .= '00000';                          
                                                    
        $flag2  = (@$flags['compression']           ? '1' : '0');  // i - Compression (true == compressed)
        $flag2 .= (@$flags['encryption']            ? '1' : '0');  // j - Encryption (true == encrypted)
        $flag2 .= (@$flags['grouping_identity']     ? '1' : '0');  // k - Grouping identity (true == contains group information)
        $flag2 .= '00000';                          
                                                    
        /**2.4                                      
        // %0abc0000 %0h00kmnp                      
        $flag1  = '0';                              
        $flag1  = (@$flags['tag_alter']             ? '1' : '0');  // a - Tag alter preservation (true == discard)
        $flag1 .= (@$flags['file_alter']            ? '1' : '0');  // b - File alter preservation (true == discard)
        $flag1 .= (@$flags['read_only']             ? '1' : '0');  // c - Read only (true == read only)
        $flag1 .= '0000';                           
                                                    
        $flag2  = '0';                              
        $flag2 .= (@$flags['grouping_identity']     ? '1' : '0');  // h - Grouping identity (true == contains group information)
        $flag2 .= '00';                             
        $flag2  = (@$flags['compression']           ? '1' : '0');  // k - Compression (true == compressed)
        $flag2 .= (@$flags['encryption']            ? '1' : '0');  // m - Encryption (true == encrypted)
        $flag2 .= (@$flags['unsynchronisation']     ? '1' : '0');  // n - Unsynchronisation (true == unsynchronised)
        $flag2 .= (@$flags['data_length_indicator'] ? '1' : '0');  // p - Data length indicator (true == data length indicator added)
        */

        return chr(bindec($flag1)).chr(bindec($flag2));
    }


    protected function generate_frame_data($frame_name, $source_data_array) {

        $frame_data = '';

        switch ($frame_name) {

            case 'UFID':
                // 4.1   UFID Unique file identifier
                // Owner identifier        <text string> $00
                // Identifier              <up to 64 bytes binary data>
                if (strlen($source_data_array['data']) > 64) {
                    throw new getid3_exception('Identifier not allowed to be longer than 64 bytes in '.$frame_name.' (supplied data was '.strlen($source_data_array['data']).' bytes long)');
                }
                $frame_data .= str_replace("\x00", '', $source_data_array['ownerid'])."\x00";
                $frame_data .= substr($source_data_array['data'], 0, 64); // max 64 bytes - truncate anything longer
                break;

            case 'TXXX':
                // 4.2.2 TXXX User defined text information frame
                // Text encoding     $xx
                // Description       <text string according to encoding> $00 (00)
                // Value             <text string according to encoding>
                $frame_data .= chr(3); // UTF-8 encoding
                $frame_data .= $source_data_array['description']."\x00";
                $frame_data .= $source_data_array['data'];
                break;

            case 'WXXX':
                // 4.3.2 WXXX User defined URL link frame
                // Text encoding     $xx
                // Description       <text string according to encoding> $00 (00)
                // URL               <text string>
                if (!isset($source_data_array['data']) || !$this->valid_url($source_data_array['data'], false, false)) {
                    throw new getid3_exception('Invalid URL in '.$frame_name.' ('.$source_data_array['data'].')');
                }
                $frame_data .= chr(3); // UTF-8 encoding
                $frame_data .= $source_data_array['description']."\x00";
                $frame_data .= $source_data_array['data'];
                break;

            case 'IPLS':
                // 4.4  IPLS Involved people list (ID3v2.3 only)
                // Text encoding     $xx
                // People list strings    <textstrings>
                $frame_data .= chr(3); // UTF-8 encoding
                $frame_data .= $source_data_array['data'];
                break;

            case 'MCDI':
                // 4.4   MCDI Music CD identifier
                // CD TOC                <binary data>
                $frame_data .= $source_data_array['data'];
                break;

            case 'ETCO':
                // 4.5   ETCO Event timing codes
                // Time stamp format    $xx
                //   Where time stamp format is:
                // $01  (32-bit value) MPEG frames from beginning of file
                // $02  (32-bit value) milliseconds from beginning of file
                //   Followed by a list of key events in the following format:
                // Type of event   $xx
                // Time stamp      $xx (xx ...)
                //   The 'Time stamp' is set to zero if directly at the beginning of the sound
                //   or after the previous event. All events MUST be sorted in chronological order.
                if (($source_data_array['timestampformat'] > 2) || ($source_data_array['timestampformat'] < 1)) {
                    throw new getid3_exception('Invalid Time Stamp Format byte in '.$frame_name.' ('.$source_data_array['timestampformat'].')');
                }
                $frame_data .= chr($source_data_array['timestampformat']);
                foreach ($source_data_array as $key => $val) {
                    if (!$this->ID3v2IsValidETCOevent($val['typeid'])) {
                        throw new getid3_exception('Invalid Event Type byte in '.$frame_name.' ('.$val['typeid'].')');
                    }
                    if (($key != 'timestampformat') && ($key != 'flags')) {
                        if (($val['timestamp'] > 0) && ($previousETCOtimestamp >= $val['timestamp'])) {
                            //   The 'Time stamp' is set to zero if directly at the beginning of the sound
                            //   or after the previous event. All events MUST be sorted in chronological order.
                            throw new getid3_exception('Out-of-order timestamp in '.$frame_name.' ('.$val['timestamp'].') for Event Type ('.$val['typeid'].')');
                        }
                        $frame_data .= chr($val['typeid']);
                        $frame_data .= getid3_lib::BigEndian2String($val['timestamp'], 4, false);
                    }
                }
                break;

            case 'MLLT':
                // 4.6   MLLT MPEG location lookup table
                // MPEG frames between reference  $xx xx
                // Bytes between reference        $xx xx xx
                // Milliseconds between reference $xx xx xx
                // Bits for bytes deviation       $xx
                // Bits for milliseconds dev.     $xx
                //   Then for every reference the following data is included;
                // Deviation in bytes         %xxx....
                // Deviation in milliseconds  %xxx....
                if (($source_data_array['framesbetweenreferences'] > 0) && ($source_data_array['framesbetweenreferences'] <= 65535)) {
                    $frame_data .= getid3_lib::BigEndian2String($source_data_array['framesbetweenreferences'], 2, false);
                }
                else {
                    throw new getid3_exception('Invalid MPEG Frames Between References in '.$frame_name.' ('.$source_data_array['framesbetweenreferences'].')');
                }
                if (($source_data_array['bytesbetweenreferences'] > 0) && ($source_data_array['bytesbetweenreferences'] <= 16777215)) {
                    $frame_data .= getid3_lib::BigEndian2String($source_data_array['bytesbetweenreferences'], 3, false);
                }
                else {
                    throw new getid3_exception('Invalid bytes Between References in '.$frame_name.' ('.$source_data_array['bytesbetweenreferences'].')');
                }
                if (($source_data_array['msbetweenreferences'] > 0) && ($source_data_array['msbetweenreferences'] <= 16777215)) {
                    $frame_data .= getid3_lib::BigEndian2String($source_data_array['msbetweenreferences'], 3, false);
                }
                else {
                    throw new getid3_exception('Invalid Milliseconds Between References in '.$frame_name.' ('.$source_data_array['msbetweenreferences'].')');
                }
                if (!$this->IsWithinBitRange($source_data_array['bitsforbytesdeviation'], 8, false)) {
                    if (($source_data_array['bitsforbytesdeviation'] % 4) == 0) {
                        $frame_data .= chr($source_data_array['bitsforbytesdeviation']);
                    }
                    else {
                        throw new getid3_exception('Bits For Bytes Deviation in '.$frame_name.' ('.$source_data_array['bitsforbytesdeviation'].') must be a multiple of 4.');
                    }
                }
                else {
                    throw new getid3_exception('Invalid Bits For Bytes Deviation in '.$frame_name.' ('.$source_data_array['bitsforbytesdeviation'].')');
                }
                if (!$this->IsWithinBitRange($source_data_array['bitsformsdeviation'], 8, false)) {
                    if (($source_data_array['bitsformsdeviation'] % 4) == 0) {
                        $frame_data .= chr($source_data_array['bitsformsdeviation']);
                    }
                    else {
                        throw new getid3_exception('Bits For Milliseconds Deviation in '.$frame_name.' ('.$source_data_array['bitsforbytesdeviation'].') must be a multiple of 4.');
                    }
                }
                else {
                    throw new getid3_exception('Invalid Bits For Milliseconds Deviation in '.$frame_name.' ('.$source_data_array['bitsformsdeviation'].')');
                }
                foreach ($source_data_array as $key => $val) {
                    if (($key != 'framesbetweenreferences') && ($key != 'bytesbetweenreferences') && ($key != 'msbetweenreferences') && ($key != 'bitsforbytesdeviation') && ($key != 'bitsformsdeviation') && ($key != 'flags')) {
                        $unwritten_bit_stream .= str_pad(getid3_lib::Dec2Bin($val['bytedeviation']), $source_data_array['bitsforbytesdeviation'], '0', STR_PAD_LEFT);
                        $unwritten_bit_stream .= str_pad(getid3_lib::Dec2Bin($val['msdeviation']),   $source_data_array['bitsformsdeviation'],    '0', STR_PAD_LEFT);
                    }
                }
                for ($i = 0; $i < strlen($unwritten_bit_stream); $i += 8) {
                    $high_nibble = bindec(substr($unwritten_bit_stream, $i, 4)) << 4;
                    $low_nibble  = bindec(substr($unwritten_bit_stream, $i + 4, 4));
                    $frame_data .= chr($high_nibble & $low_nibble);
                }
                break;

            case 'SYTC':
                // 4.7   SYTC Synchronised tempo codes
                // Time stamp format   $xx
                // Tempo data          <binary data>
                //   Where time stamp format is:
                // $01  (32-bit value) MPEG frames from beginning of file
                // $02  (32-bit value) milliseconds from beginning of file
                if (($source_data_array['timestampformat'] > 2) || ($source_data_array['timestampformat'] < 1)) {
                    throw new getid3_exception('Invalid Time Stamp Format byte in '.$frame_name.' ('.$source_data_array['timestampformat'].')');
                }
                $frame_data .= chr($source_data_array['timestampformat']);
                foreach ($source_data_array as $key => $val) {
                    if (!$this->ID3v2IsValidETCOevent($val['typeid'])) {
                        throw new getid3_exception('Invalid Event Type byte in '.$frame_name.' ('.$val['typeid'].')');
                    }
                    if (($key != 'timestampformat') && ($key != 'flags')) {
                        if (($val['tempo'] < 0) || ($val['tempo'] > 510)) {
                            throw new getid3_exception('Invalid Tempo (max = 510) in '.$frame_name.' ('.$val['tempo'].') at timestamp ('.$val['timestamp'].')');
                        }
                        if ($val['tempo'] > 255) {
                            $frame_data .= chr(255);
                            $val['tempo'] -= 255;
                        }
                        $frame_data .= chr($val['tempo']);
                        $frame_data .= getid3_lib::BigEndian2String($val['timestamp'], 4, false);
                    }
                }
                break;

            case 'USLT':
                // 4.8   USLT Unsynchronised lyric/text transcription
                // Text encoding        $xx
                // Language             $xx xx xx
                // Content descriptor   <text string according to encoding> $00 (00)
                // Lyrics/text          <full text string according to encoding>
                if (getid3_id3v2::LanguageLookup($source_data_array['language'], true) == '') {
                    throw new getid3_exception('Invalid Language in '.$frame_name.' ('.$source_data_array['language'].')');
                }
                $frame_data .= chr(3); // UTF-8 encoding
                $frame_data .= strtolower($source_data_array['language']);
                $frame_data .= $source_data_array['description']."\x00";
                $frame_data .= $source_data_array['data'];
                break;

            case 'SYLT':
                // 4.9   SYLT Synchronised lyric/text
                // Text encoding        $xx
                // Language             $xx xx xx
                // Time stamp format    $xx
                //   $01  (32-bit value) MPEG frames from beginning of file
                //   $02  (32-bit value) milliseconds from beginning of file
                // Content type         $xx
                // Content descriptor   <text string according to encoding> $00 (00)
                //   Terminated text to be synced (typically a syllable)
                //   Sync identifier (terminator to above string)   $00 (00)
                //   Time stamp                                     $xx (xx ...)
                if (getid3_id3v2::LanguageLookup($source_data_array['language'], true) == '') {
                    throw new getid3_exception('Invalid Language in '.$frame_name.' ('.$source_data_array['language'].')');
                }
                if (($source_data_array['timestampformat'] > 2) || ($source_data_array['timestampformat'] < 1)) {
                    throw new getid3_exception('Invalid Time Stamp Format byte in '.$frame_name.' ('.$source_data_array['timestampformat'].')');
                }
                if (!$this->ID3v2IsValidSYLTtype($source_data_array['contenttypeid'])) {
                    throw new getid3_exception('Invalid Content Type byte in '.$frame_name.' ('.$source_data_array['contenttypeid'].')');
                }
                if (!is_array($source_data_array['data'])) {
                    throw new getid3_exception('Invalid Lyric/Timestamp data in '.$frame_name.' (must be an array)');
                }
                $frame_data .= chr(3); // UTF-8 encoding
                $frame_data .= strtolower($source_data_array['language']);
                $frame_data .= chr($source_data_array['timestampformat']);
                $frame_data .= chr($source_data_array['contenttypeid']);
                $frame_data .= $source_data_array['description']."\x00";
                ksort($source_data_array['data']);
                foreach ($source_data_array['data'] as $key => $val) {
                    $frame_data .= $val['data']."\x00";
                    $frame_data .= getid3_lib::BigEndian2String($val['timestamp'], 4, false);
                }
                break;

            case 'COMM':
                // 4.10  COMM Comments
                // Text encoding          $xx
                // Language               $xx xx xx
                // Short content descrip. <text string according to encoding> $00 (00)
                // The actual text        <full text string according to encoding>
                if (getid3_id3v2::LanguageLookup($source_data_array['language'], true) == '') {
                    throw new getid3_exception('Invalid Language in '.$frame_name.' ('.$source_data_array['language'].')');
                }
                $frame_data .= chr(3); // UTF-8 encoding
                $frame_data .= strtolower($source_data_array['language']);
                $frame_data .= $source_data_array['description']."\x00";
                $frame_data .= $source_data_array['data'];
                break;

            case 'RVA2':
                // 4.11  RVA2 Relative volume adjustment (2) (ID3v2.4+ only)
                // Identification          <text string> $00
                //   The 'identification' string is used to identify the situation and/or
                //   device where this adjustment should apply. The following is then
                //   repeated for every channel:
                // Type of channel         $xx
                // Volume adjustment       $xx xx
                // Bits representing peak  $xx
                // Peak volume             $xx (xx ...)
                $frame_data .= str_replace("\x00", '', $source_data_array['description'])."\x00";
                foreach ($source_data_array as $key => $val) {
                    if ($key != 'description') {
                        $frame_data .= chr($val['channeltypeid']);
                        $frame_data .= getid3_lib::BigEndian2String($val['volumeadjust'], 2, false, true); // signed 16-bit
                        if (!$this->IsWithinBitRange($source_data_array['bitspeakvolume'], 8, false)) {
                            $frame_data .= chr($val['bitspeakvolume']);
                            if ($val['bitspeakvolume'] > 0) {
                                $frame_data .= getid3_lib::BigEndian2String($val['peakvolume'], ceil($val['bitspeakvolume'] / 8), false, false);
                            }
                        } else {
                            throw new getid3_exception('Invalid Bits Representing Peak Volume in '.$frame_name.' ('.$val['bitspeakvolume'].') (range = 0 to 255)');
                        }
                    }
                }
                break;

            case 'RVAD':
                // 4.12  RVAD Relative volume adjustment (ID3v2.3 only)
                // Increment/decrement     %00fedcba
                // Bits used for volume descr.        $xx
                // Relative volume change, right      $xx xx (xx ...) // a
                // Relative volume change, left       $xx xx (xx ...) // b
                // Peak volume right                  $xx xx (xx ...)
                // Peak volume left                   $xx xx (xx ...)
                // Relative volume change, right back $xx xx (xx ...) // c
                // Relative volume change, left back  $xx xx (xx ...) // d
                // Peak volume right back             $xx xx (xx ...)
                // Peak volume left back              $xx xx (xx ...)
                // Relative volume change, center     $xx xx (xx ...) // e
                // Peak volume center                 $xx xx (xx ...)
                // Relative volume change, bass       $xx xx (xx ...) // f
                // Peak volume bass                   $xx xx (xx ...)
                if (!$this->IsWithinBitRange($source_data_array['bitsvolume'], 8, false)) {
                    throw new getid3_exception('Invalid Bits For Volume Description byte in '.$frame_name.' ('.$source_data_array['bitsvolume'].') (range = 1 to 255)');
                } else {
                    $inc_dec_flag .= '00';
                    $inc_dec_flag .= $source_data_array['incdec']['right']     ? '1' : '0'; // a - Relative volume change, right
                    $inc_dec_flag .= $source_data_array['incdec']['left']      ? '1' : '0'; // b - Relative volume change, left
                    $inc_dec_flag .= $source_data_array['incdec']['rightrear'] ? '1' : '0'; // c - Relative volume change, right back
                    $inc_dec_flag .= $source_data_array['incdec']['leftrear']  ? '1' : '0'; // d - Relative volume change, left back
                    $inc_dec_flag .= $source_data_array['incdec']['center']    ? '1' : '0'; // e - Relative volume change, center
                    $inc_dec_flag .= $source_data_array['incdec']['bass']      ? '1' : '0'; // f - Relative volume change, bass
                    $frame_data .= chr(bindec($inc_dec_flag));
                    $frame_data .= chr($source_data_array['bitsvolume']);
                    $frame_data .= getid3_lib::BigEndian2String($source_data_array['volumechange']['right'], ceil($source_data_array['bitsvolume'] / 8), false);
                    $frame_data .= getid3_lib::BigEndian2String($source_data_array['volumechange']['left'],  ceil($source_data_array['bitsvolume'] / 8), false);
                    $frame_data .= getid3_lib::BigEndian2String($source_data_array['peakvolume']['right'], ceil($source_data_array['bitsvolume'] / 8), false);
                    $frame_data .= getid3_lib::BigEndian2String($source_data_array['peakvolume']['left'],  ceil($source_data_array['bitsvolume'] / 8), false);
                    if ($source_data_array['volumechange']['rightrear'] || $source_data_array['volumechange']['leftrear'] ||
                        $source_data_array['peakvolume']['rightrear'] || $source_data_array['peakvolume']['leftrear'] ||
                        $source_data_array['volumechange']['center'] || $source_data_array['peakvolume']['center'] ||
                        $source_data_array['volumechange']['bass'] || $source_data_array['peakvolume']['bass']) {
                            $frame_data .= getid3_lib::BigEndian2String($source_data_array['volumechange']['rightrear'], ceil($source_data_array['bitsvolume']/8), false);
                            $frame_data .= getid3_lib::BigEndian2String($source_data_array['volumechange']['leftrear'],  ceil($source_data_array['bitsvolume']/8), false);
                            $frame_data .= getid3_lib::BigEndian2String($source_data_array['peakvolume']['rightrear'], ceil($source_data_array['bitsvolume']/8), false);
                            $frame_data .= getid3_lib::BigEndian2String($source_data_array['peakvolume']['leftrear'],  ceil($source_data_array['bitsvolume']/8), false);
                    }
                    if ($source_data_array['volumechange']['center'] || $source_data_array['peakvolume']['center'] ||
                        $source_data_array['volumechange']['bass'] || $source_data_array['peakvolume']['bass']) {
                            $frame_data .= getid3_lib::BigEndian2String($source_data_array['volumechange']['center'], ceil($source_data_array['bitsvolume']/8), false);
                            $frame_data .= getid3_lib::BigEndian2String($source_data_array['peakvolume']['center'], ceil($source_data_array['bitsvolume']/8), false);
                    }
                    if ($source_data_array['volumechange']['bass'] || $source_data_array['peakvolume']['bass']) {
                            $frame_data .= getid3_lib::BigEndian2String($source_data_array['volumechange']['bass'], ceil($source_data_array['bitsvolume']/8), false);
                            $frame_data .= getid3_lib::BigEndian2String($source_data_array['peakvolume']['bass'], ceil($source_data_array['bitsvolume']/8), false);
                    }
                }
                break;

            case 'EQU2':
                // 4.12  EQU2 Equalisation (2) (ID3v2.4+ only)
                // Interpolation method  $xx
                //   $00  Band
                //   $01  Linear
                // Identification        <text string> $00
                //   The following is then repeated for every adjustment point
                // Frequency          $xx xx
                // Volume adjustment  $xx xx
                if (($source_data_array['interpolationmethod'] < 0) || ($source_data_array['interpolationmethod'] > 1)) {
                    throw new getid3_exception('Invalid Interpolation Method byte in '.$frame_name.' ('.$source_data_array['interpolationmethod'].') (valid = 0 or 1)');
                }
                $frame_data .= chr($source_data_array['interpolationmethod']);
                $frame_data .= str_replace("\x00", '', $source_data_array['description'])."\x00";
                foreach ($source_data_array['data'] as $key => $val) {
                    $frame_data .= getid3_lib::BigEndian2String(intval(round($key * 2)), 2, false);
                    $frame_data .= getid3_lib::BigEndian2String($val, 2, false, true); // signed 16-bit
                }
                break;

            case 'EQUA':
                // 4.12  EQUA Equalisation (ID3v2.3 only)
                // Adjustment bits    $xx
                //   This is followed by 2 bytes + ('adjustment bits' rounded up to the
                //   nearest byte) for every equalisation band in the following format,
                //   giving a frequency range of 0 - 32767Hz:
                // Increment/decrement   %x (MSB of the Frequency)
                // Frequency             (lower 15 bits)
                // Adjustment            $xx (xx ...)
                if (!$this->IsWithinBitRange($source_data_array['bitsvolume'], 8, false)) {
                    throw new getid3_exception('Invalid Adjustment Bits byte in '.$frame_name.' ('.$source_data_array['bitsvolume'].') (range = 1 to 255)');
                }
                $frame_data .= chr($source_data_array['adjustmentbits']);
                foreach ($source_data_array as $key => $val) {
                    if ($key != 'bitsvolume') {
                        if (($key > 32767) || ($key < 0)) {
                            throw new getid3_exception('Invalid Frequency in '.$frame_name.' ('.$key.') (range = 0 to 32767)');
                        } else {
                            if ($val >= 0) {
                                // put MSB of frequency to 1 if increment, 0 if decrement
                                $key |= 0x8000;
                            }
                            $frame_data .= getid3_lib::BigEndian2String($key, 2, false);
                            $frame_data .= getid3_lib::BigEndian2String($val, ceil($source_data_array['adjustmentbits'] / 8), false);
                        }
                    }
                }
                break;

            case 'RVRB':
                // 4.13  RVRB Reverb
                // Reverb left (ms)                 $xx xx
                // Reverb right (ms)                $xx xx
                // Reverb bounces, left             $xx
                // Reverb bounces, right            $xx
                // Reverb feedback, left to left    $xx
                // Reverb feedback, left to right   $xx
                // Reverb feedback, right to right  $xx
                // Reverb feedback, right to left   $xx
                // Premix left to right             $xx
                // Premix right to left             $xx
                if (!$this->IsWithinBitRange($source_data_array['left'], 16, false)) {
                    throw new getid3_exception('Invalid Reverb Left in '.$frame_name.' ('.$source_data_array['left'].') (range = 0 to 65535)');
                }
                if (!$this->IsWithinBitRange($source_data_array['right'], 16, false)) {
                    throw new getid3_exception('Invalid Reverb Left in '.$frame_name.' ('.$source_data_array['right'].') (range = 0 to 65535)');
                }
                if (!$this->IsWithinBitRange($source_data_array['bouncesL'], 8, false)) {
                    throw new getid3_exception('Invalid Reverb Bounces, Left in '.$frame_name.' ('.$source_data_array['bouncesL'].') (range = 0 to 255)');
                }
                if (!$this->IsWithinBitRange($source_data_array['bouncesR'], 8, false)) {
                    throw new getid3_exception('Invalid Reverb Bounces, Right in '.$frame_name.' ('.$source_data_array['bouncesR'].') (range = 0 to 255)');
                }
                if (!$this->IsWithinBitRange($source_data_array['feedbackLL'], 8, false)) {
                    throw new getid3_exception('Invalid Reverb Feedback, Left-To-Left in '.$frame_name.' ('.$source_data_array['feedbackLL'].') (range = 0 to 255)');
                }
                if (!$this->IsWithinBitRange($source_data_array['feedbackLR'], 8, false)) {
                    throw new getid3_exception('Invalid Reverb Feedback, Left-To-Right in '.$frame_name.' ('.$source_data_array['feedbackLR'].') (range = 0 to 255)');
                }
                if (!$this->IsWithinBitRange($source_data_array['feedbackRR'], 8, false)) {
                    throw new getid3_exception('Invalid Reverb Feedback, Right-To-Right in '.$frame_name.' ('.$source_data_array['feedbackRR'].') (range = 0 to 255)');
                }
                if (!$this->IsWithinBitRange($source_data_array['feedbackRL'], 8, false)) {
                    throw new getid3_exception('Invalid Reverb Feedback, Right-To-Left in '.$frame_name.' ('.$source_data_array['feedbackRL'].') (range = 0 to 255)');
                }
                if (!$this->IsWithinBitRange($source_data_array['premixLR'], 8, false)) {
                    throw new getid3_exception('Invalid Premix, Left-To-Right in '.$frame_name.' ('.$source_data_array['premixLR'].') (range = 0 to 255)');
                }
                if (!$this->IsWithinBitRange($source_data_array['premixRL'], 8, false)) {
                    throw new getid3_exception('Invalid Premix, Right-To-Left in '.$frame_name.' ('.$source_data_array['premixRL'].') (range = 0 to 255)');
                }
                $frame_data .= getid3_lib::BigEndian2String($source_data_array['left'], 2, false);
                $frame_data .= getid3_lib::BigEndian2String($source_data_array['right'], 2, false);
                $frame_data .= chr($source_data_array['bouncesL']);
                $frame_data .= chr($source_data_array['bouncesR']);
                $frame_data .= chr($source_data_array['feedbackLL']);
                $frame_data .= chr($source_data_array['feedbackLR']);
                $frame_data .= chr($source_data_array['feedbackRR']);
                $frame_data .= chr($source_data_array['feedbackRL']);
                $frame_data .= chr($source_data_array['premixLR']);
                $frame_data .= chr($source_data_array['premixRL']);
                break;

            case 'APIC':
                // 4.14  APIC Attached picture
                // Text encoding      $xx
                // MIME type          <text string> $00
                // Picture type       $xx
                // Description        <text string according to encoding> $00 (00)
                // Picture data       <binary data>
                if (!$this->ID3v2IsValidAPICpicturetype($source_data_array['picturetypeid'])) {
                    throw new getid3_exception('Invalid Picture Type byte in '.$frame_name.' ('.$source_data_array['picturetypeid'].') for ID3v2.'.getid3_id3v2_write::major_version);
                }
                if ((getid3_id3v2_write::major_version >= 3) && (!$this->ID3v2IsValidAPICimageformat($source_data_array['mime']))) {
                    throw new getid3_exception('Invalid MIME Type in '.$frame_name.' ('.$source_data_array['mime'].') for ID3v2.'.getid3_id3v2_write::major_version);
                }
                if (($source_data_array['mime'] == '-->') && (!$this->valid_url($source_data_array['data'], false, false))) {
                    throw new getid3_exception('Invalid URL in '.$frame_name.' ('.$source_data_array['data'].')');
                }
                $frame_data .= chr(3); // UTF-8 encoding
                $frame_data .= str_replace("\x00", '', $source_data_array['mime'])."\x00";
                $frame_data .= chr($source_data_array['picturetypeid']);
                $frame_data .= @$source_data_array['description']."\x00";
                $frame_data .= $source_data_array['data'];
                break;

            case 'GEOB':
                // 4.15  GEOB General encapsulated object
                // Text encoding          $xx
                // MIME type              <text string> $00
                // Filename               <text string according to encoding> $00 (00)
                // Content description    <text string according to encoding> $00 (00)
                // Encapsulated object    <binary data>
                if (!$this->IsValidMIMEstring($source_data_array['mime'])) {
                    throw new getid3_exception('Invalid MIME Type in '.$frame_name.' ('.$source_data_array['mime'].')');
                }
                if (!$source_data_array['description']) {
                    throw new getid3_exception('Missing Description in '.$frame_name);
                }
                $frame_data .= chr(3); // UTF-8 encoding
                $frame_data .= str_replace("\x00", '', $source_data_array['mime'])."\x00";
                $frame_data .= $source_data_array['filename']."\x00";
                $frame_data .= $source_data_array['description']."\x00";
                $frame_data .= $source_data_array['data'];
                break;

            case 'PCNT':
                // 4.16  PCNT Play counter
                //   When the counter reaches all one's, one byte is inserted in
                //   front of the counter thus making the counter eight bits bigger
                // Counter        $xx xx xx xx (xx ...)
                $frame_data .= getid3_lib::BigEndian2String($source_data_array['data'], 4, false);
                break;

            case 'POPM':
                // 4.17  POPM Popularimeter
                //   When the counter reaches all one's, one byte is inserted in
                //   front of the counter thus making the counter eight bits bigger
                // Email to user   <text string> $00
                // Rating          $xx
                // Counter         $xx xx xx xx (xx ...)
                if (!$this->IsWithinBitRange($source_data_array['rating'], 8, false)) {
                    throw new getid3_exception('Invalid Rating byte in '.$frame_name.' ('.$source_data_array['rating'].') (range = 0 to 255)');
                }
                if (!IsValidEmail($source_data_array['email'])) {
                    throw new getid3_exception('Invalid Email in '.$frame_name.' ('.$source_data_array['email'].')');
                }
                $frame_data .= str_replace("\x00", '', $source_data_array['email'])."\x00";
                $frame_data .= chr($source_data_array['rating']);
                $frame_data .= getid3_lib::BigEndian2String($source_data_array['data'], 4, false);
                break;

            case 'RBUF':
                // 4.18  RBUF Recommended buffer size
                // Buffer size               $xx xx xx
                // Embedded info flag        %0000000x
                // Offset to next tag        $xx xx xx xx
                if (!$this->IsWithinBitRange($source_data_array['buffersize'], 24, false)) {
                    throw new getid3_exception('Invalid Buffer Size in '.$frame_name);
                }
                if (!$this->IsWithinBitRange($source_data_array['nexttagoffset'], 32, false)) {
                    throw new getid3_exception('Invalid Offset To Next Tag in '.$frame_name);
                }
                $frame_data .= getid3_lib::BigEndian2String($source_data_array['buffersize'], 3, false);
                $flag .= '0000000';
                $flag .= $source_data_array['flags']['embededinfo'] ? '1' : '0';
                $frame_data .= chr(bindec($flag));
                $frame_data .= getid3_lib::BigEndian2String($source_data_array['nexttagoffset'], 4, false);
                break;

            case 'AENC':
                // 4.19  AENC Audio encryption
                // Owner identifier   <text string> $00
                // Preview start      $xx xx
                // Preview length     $xx xx
                // Encryption info    <binary data>
                if (!$this->IsWithinBitRange($source_data_array['previewstart'], 16, false)) {
                    throw new getid3_exception('Invalid Preview Start in '.$frame_name.' ('.$source_data_array['previewstart'].')');
                }
                if (!$this->IsWithinBitRange($source_data_array['previewlength'], 16, false)) {
                    throw new getid3_exception('Invalid Preview Length in '.$frame_name.' ('.$source_data_array['previewlength'].')');
                }
                $frame_data .= str_replace("\x00", '', $source_data_array['ownerid'])."\x00";
                $frame_data .= getid3_lib::BigEndian2String($source_data_array['previewstart'], 2, false);
                $frame_data .= getid3_lib::BigEndian2String($source_data_array['previewlength'], 2, false);
                $frame_data .= $source_data_array['encryptioninfo'];
                break;

            case 'LINK':
                // 4.20  LINK Linked information
                // Frame identifier               $xx xx xx xx
                // URL                            <text string> $00
                // ID and additional data         <text string(s)>
                if (!getid3_id3v2::valid_frame_name($source_data_array['frameid'], getid3_id3v2_write::major_version)) {
                    throw new getid3_exception('Invalid Frame Identifier in '.$frame_name.' ('.$source_data_array['frameid'].')');
                }
                if (!$this->valid_url($source_data_array['data'], true, false)) {
                    throw new getid3_exception('Invalid URL in '.$frame_name.' ('.$source_data_array['data'].')');
                }
                if ((($source_data_array['frameid'] == 'AENC') || ($source_data_array['frameid'] == 'APIC') || ($source_data_array['frameid'] == 'GEOB') || ($source_data_array['frameid'] == 'TXXX')) && ($source_data_array['additionaldata'] == '')) {
                    throw new getid3_exception('Content Descriptor must be specified as additional data for Frame Identifier of '.$source_data_array['frameid'].' in '.$frame_name);
                }
                if (($source_data_array['frameid'] == 'USER') && (getid3_id3v2::LanguageLookup($source_data_array['additionaldata'], true) == '')) {
                    throw new getid3_exception('Language must be specified as additional data for Frame Identifier of '.$source_data_array['frameid'].' in '.$frame_name);
                }
                if (($source_data_array['frameid'] == 'PRIV') && ($source_data_array['additionaldata'] == '')) {
                    throw new getid3_exception('Owner Identifier must be specified as additional data for Frame Identifier of '.$source_data_array['frameid'].' in '.$frame_name);
                }
                if ((($source_data_array['frameid'] == 'COMM') || ($source_data_array['frameid'] == 'SYLT') || ($source_data_array['frameid'] == 'USLT')) && ((getid3_id3v2::LanguageLookup(substr($source_data_array['additionaldata'], 0, 3), true) == '') || (substr($source_data_array['additionaldata'], 3) == ''))) {
                    throw new getid3_exception('Language followed by Content Descriptor must be specified as additional data for Frame Identifier of '.$source_data_array['frameid'].' in '.$frame_name);
                }
                $frame_data .= $source_data_array['frameid'];
                $frame_data .= str_replace("\x00", '', $source_data_array['data'])."\x00";
                switch ($source_data_array['frameid']) {
                    case 'COMM':
                    case 'SYLT':
                    case 'USLT':
                    case 'PRIV':
                    case 'USER':
                    case 'AENC':
                    case 'APIC':
                    case 'GEOB':
                    case 'TXXX':
                        $frame_data .= $source_data_array['additionaldata'];
                        break;

                    case 'ASPI':
                    case 'ETCO':
                    case 'EQU2':
                    case 'MCID':
                    case 'MLLT':
                    case 'OWNE':
                    case 'RVA2':
                    case 'RVRB':
                    case 'SYTC':
                    case 'IPLS':
                    case 'RVAD':
                    case 'EQUA':
                        // no additional data required
                        break;

                    case 'RBUF':
                        if (getid3_id3v2_write::major_version == 3) {
                            // no additional data required
                        } else {
                            throw new getid3_exception($source_data_array['frameid'].' is not a valid Frame Identifier in '.$frame_name.' (in ID3v2.'.getid3_id3v2_write::major_version.')');
                        }

                    default:
                        if ((substr($source_data_array['frameid'], 0, 1) == 'T') || (substr($source_data_array['frameid'], 0, 1) == 'W')) {
                            // no additional data required
                        } else {
                            throw new getid3_exception($source_data_array['frameid'].' is not a valid Frame Identifier in '.$frame_name.' (in ID3v2.'.getid3_id3v2_write::major_version.')');
                        }
                }
                break;

            case 'POSS':
                // 4.21  POSS Position synchronisation frame (ID3v2.3+ only)
                // Time stamp format         $xx
                // Position                  $xx (xx ...)
                if (($source_data_array['timestampformat'] < 1) || ($source_data_array['timestampformat'] > 2)) {
                    throw new getid3_exception('Invalid Time Stamp Format in '.$frame_name.' ('.$source_data_array['timestampformat'].') (valid = 1 or 2)');
                }
                if (!$this->IsWithinBitRange($source_data_array['position'], 32, false)) {
                    throw new getid3_exception('Invalid Position in '.$frame_name.' ('.$source_data_array['position'].') (range = 0 to 4294967295)');
                }
                $frame_data .= chr($source_data_array['timestampformat']);
                $frame_data .= getid3_lib::BigEndian2String($source_data_array['position'], 4, false);
                break;

            case 'USER':
                // 4.22  USER Terms of use (ID3v2.3+ only)
                // Text encoding        $xx
                // Language             $xx xx xx
                // The actual text      <text string according to encoding>
                if (getid3_id3v2::LanguageLookup($source_data_array['language'], true) == '') {
                    throw new getid3_exception('Invalid Language in '.$frame_name.' ('.$source_data_array['language'].')');
                }
                $frame_data .= chr(3); // UTF-8 encoding
                $frame_data .= strtolower($source_data_array['language']);
                $frame_data .= $source_data_array['data'];
                break;

            case 'OWNE':
                // 4.23  OWNE Ownership frame (ID3v2.3+ only)
                // Text encoding     $xx
                // Price paid        <text string> $00
                // Date of purch.    <text string>
                // Seller            <text string according to encoding>
                if (!$this->IsANumber($source_data_array['pricepaid']['value'], false)) {
                    throw new getid3_exception('Invalid Price Paid in '.$frame_name.' ('.$source_data_array['pricepaid']['value'].')');
                }
                if (!$this->IsValidDateStampString($source_data_array['purchasedate'])) {
                    throw new getid3_exception('Invalid Date Of Purchase in '.$frame_name.' ('.$source_data_array['purchasedate'].') (format = YYYYMMDD)');
                }
                $frame_data .= chr(3); // UTF-8 encoding
                $frame_data .= str_replace("\x00", '', $source_data_array['pricepaid']['value'])."\x00";
                $frame_data .= $source_data_array['purchasedate'];
                $frame_data .= $source_data_array['seller'];
                break;

            case 'COMR':
                // 4.24  COMR Commercial frame (ID3v2.3+ only)
                // Text encoding      $xx
                // Price string       <text string> $00
                // Valid until        <text string>
                // Contact URL        <text string> $00
                // Received as        $xx
                // Name of seller     <text string according to encoding> $00 (00)
                // Description        <text string according to encoding> $00 (00)
                // Picture MIME type  <string> $00
                // Seller logo        <binary data>
                if (!$this->IsValidDateStampString($source_data_array['pricevaliduntil'])) {
                    throw new getid3_exception('Invalid Valid Until date in '.$frame_name.' ('.$source_data_array['pricevaliduntil'].') (format = YYYYMMDD)');
                }
                if (!$this->valid_url($source_data_array['contacturl'], false, true)) {
                    throw new getid3_exception('Invalid Contact URL in '.$frame_name.' ('.$source_data_array['contacturl'].') (allowed schemes: http, https, ftp, mailto)');
                }
                if (!$this->ID3v2IsValidCOMRreceivedAs($source_data_array['receivedasid'])) {
                    throw new getid3_exception('Invalid Received As byte in '.$frame_name.' ('.$source_data_array['contacturl'].') (range = 0 to 8)');
                }if (!$this->IsValidMIMEstring($source_data_array['mime'])) {
                    throw new getid3_exception('Invalid MIME Type in '.$frame_name.' ('.$source_data_array['mime'].')');
                }
                $frame_data .= chr(3); // UTF-8 encoding
                unset($price_string);
                foreach ($source_data_array['price'] as $key => $val) {
                    if ($this->ID3v2IsValidPriceString($key.$val['value'])) {
                        $price_strings[] = $key.$val['value'];
                    } else {
                        throw new getid3_exception('Invalid Price String in '.$frame_name.' ('.$key.$val['value'].')');
                    }
                }
                $frame_data .= implode('/', $price_strings);
                $frame_data .= $source_data_array['pricevaliduntil'];
                $frame_data .= str_replace("\x00", '', $source_data_array['contacturl'])."\x00";
                $frame_data .= chr($source_data_array['receivedasid']);
                $frame_data .= $source_data_array['sellername']."\x00";
                $frame_data .= $source_data_array['description']."\x00";
                $frame_data .= $source_data_array['mime']."\x00";
                $frame_data .= $source_data_array['logo'];
                break;

            case 'ENCR':
                // 4.25  ENCR Encryption method registration (ID3v2.3+ only)
                // Owner identifier    <text string> $00
                // Method symbol       $xx
                // Encryption data     <binary data>
                if (!$this->IsWithinBitRange($source_data_array['methodsymbol'], 8, false)) {
                    throw new getid3_exception('Invalid Group Symbol in '.$frame_name.' ('.$source_data_array['methodsymbol'].') (range = 0 to 255)');
                }
                $frame_data .= str_replace("\x00", '', $source_data_array['ownerid'])."\x00";
                $frame_data .= ord($source_data_array['methodsymbol']);
                $frame_data .= $source_data_array['data'];
                break;

            case 'GRID':
                // 4.26  GRID Group identification registration (ID3v2.3+ only)
                // Owner identifier      <text string> $00
                // Group symbol          $xx
                // Group dependent data  <binary data>
                if (!$this->IsWithinBitRange($source_data_array['groupsymbol'], 8, false)) {
                    throw new getid3_exception('Invalid Group Symbol in '.$frame_name.' ('.$source_data_array['groupsymbol'].') (range = 0 to 255)');
                }
                $frame_data .= str_replace("\x00", '', $source_data_array['ownerid'])."\x00";
                $frame_data .= ord($source_data_array['groupsymbol']);
                $frame_data .= $source_data_array['data'];
                break;

            case 'PRIV':
                // 4.27  PRIV Private frame (ID3v2.3+ only)
                // Owner identifier      <text string> $00
                // The private data      <binary data>
                $frame_data .= str_replace("\x00", '', $source_data_array['ownerid'])."\x00";
                $frame_data .= $source_data_array['data'];
                break;

            case 'SIGN':
                // 4.28  SIGN Signature frame (ID3v2.4+ only)
                // Group symbol      $xx
                // Signature         <binary data>
                if (!$this->IsWithinBitRange($source_data_array['groupsymbol'], 8, false)) {
                    throw new getid3_exception('Invalid Group Symbol in '.$frame_name.' ('.$source_data_array['groupsymbol'].') (range = 0 to 255)');
                }
                $frame_data .= ord($source_data_array['groupsymbol']);
                $frame_data .= $source_data_array['data'];
                break;

            case 'SEEK':
                // 4.29  SEEK Seek frame (ID3v2.4+ only)
                // Minimum offset to next tag       $xx xx xx xx
                if (!$this->IsWithinBitRange($source_data_array['data'], 32, false)) {
                    throw new getid3_exception('Invalid Minimum Offset in '.$frame_name.' ('.$source_data_array['data'].') (range = 0 to 4294967295)');
                }
                $frame_data .= getid3_lib::BigEndian2String($source_data_array['data'], 4, false);
                break;

            case 'ASPI':
                // 4.30  ASPI Audio seek point index (ID3v2.4+ only)
                // Indexed data start (S)         $xx xx xx xx
                // Indexed data length (L)        $xx xx xx xx
                // Number of index points (N)     $xx xx
                // Bits per index point (b)       $xx
                //   Then for every index point the following data is included:
                // Fraction at index (Fi)          $xx (xx)
                if (!$this->IsWithinBitRange($source_data_array['datastart'], 32, false)) {
                    throw new getid3_exception('Invalid Indexed Data Start in '.$frame_name.' ('.$source_data_array['datastart'].') (range = 0 to 4294967295)');
                }
                if (!$this->IsWithinBitRange($source_data_array['datalength'], 32, false)) {
                    throw new getid3_exception('Invalid Indexed Data Length in '.$frame_name.' ('.$source_data_array['datalength'].') (range = 0 to 4294967295)');
                }
                if (!$this->IsWithinBitRange($source_data_array['indexpoints'], 16, false)) {
                    throw new getid3_exception('Invalid Number Of Index Points in '.$frame_name.' ('.$source_data_array['indexpoints'].') (range = 0 to 65535)');
                }
                if (!$this->IsWithinBitRange($source_data_array['bitsperpoint'], 8, false)) {
                    throw new getid3_exception('Invalid Bits Per Index Point in '.$frame_name.' ('.$source_data_array['bitsperpoint'].') (range = 0 to 255)');
                }
                if ($source_data_array['indexpoints'] != count($source_data_array['indexes'])) {
                    throw new getid3_exception('Number Of Index Points does not match actual supplied data in '.$frame_name);
                }
                $frame_data .= getid3_lib::BigEndian2String($source_data_array['datastart'], 4, false);
                $frame_data .= getid3_lib::BigEndian2String($source_data_array['datalength'], 4, false);
                $frame_data .= getid3_lib::BigEndian2String($source_data_array['indexpoints'], 2, false);
                $frame_data .= getid3_lib::BigEndian2String($source_data_array['bitsperpoint'], 1, false);
                foreach ($source_data_array['indexes'] as $key => $val) {
                    $frame_data .= getid3_lib::BigEndian2String($val, ceil($source_data_array['bitsperpoint'] / 8), false);
                }
                break;

            case 'RGAD':
                //   RGAD Replay Gain Adjustment
                //   http://privatewww.essex.ac.uk/~djmrob/replaygain/
                // Peak Amplitude                     $xx $xx $xx $xx
                // Radio Replay Gain Adjustment        %aaabbbcd %dddddddd
                // Audiophile Replay Gain Adjustment   %aaabbbcd %dddddddd
                //   a - name code
                //   b - originator code
                //   c - sign bit
                //   d - replay gain adjustment

                if (($source_data_array['track_adjustment'] > 51) || ($source_data_array['track_adjustment'] < -51)) {
                    throw new getid3_exception('Invalid Track Adjustment in '.$frame_name.' ('.$source_data_array['track_adjustment'].') (range = -51.0 to +51.0)');
                }
                if (($source_data_array['album_adjustment'] > 51) || ($source_data_array['album_adjustment'] < -51)) {
                    throw new getid3_exception('Invalid Album Adjustment in '.$frame_name.' ('.$source_data_array['album_adjustment'].') (range = -51.0 to +51.0)');
                }
                if (!$this->ID3v2IsValidRGADname($source_data_array['raw']['track_name'])) {
                    throw new getid3_exception('Invalid Track Name Code in '.$frame_name.' ('.$source_data_array['raw']['track_name'].') (range = 0 to 2)');
                }
                if (!$this->ID3v2IsValidRGADname($source_data_array['raw']['album_name'])) {
                    throw new getid3_exception('Invalid Album Name Code in '.$frame_name.' ('.$source_data_array['raw']['album_name'].') (range = 0 to 2)');
                }
                if (!$this->ID3v2IsValidRGADoriginator($source_data_array['raw']['track_originator'])) {
                    throw new getid3_exception('Invalid Track Originator Code in '.$frame_name.' ('.$source_data_array['raw']['track_originator'].') (range = 0 to 3)');
                }
                if (!$this->ID3v2IsValidRGADoriginator($source_data_array['raw']['album_originator'])) {
                    throw new getid3_exception('Invalid Album Originator Code in '.$frame_name.' ('.$source_data_array['raw']['album_originator'].') (range = 0 to 3)');
                }
                $frame_data .= getid3_lib::Float2String($source_data_array['peakamplitude'], 32);
                $frame_data .= getid3_lib::RGADgainString($source_data_array['raw']['track_name'], $source_data_array['raw']['track_originator'], $source_data_array['track_adjustment']);
                $frame_data .= getid3_lib::RGADgainString($source_data_array['raw']['album_name'], $source_data_array['raw']['album_originator'], $source_data_array['album_adjustment']);
                break;

            default:

                if ($frame_name{0} == 'T') {
                    // 4.2. T???  Text information frames
                    // Text encoding                $xx
                    // Information                  <text string(s) according to encoding>
                    $frame_data .= chr(3); // UTF-8 encoding
                    $frame_data .= $source_data_array['data'];
                }

                elseif ($frame_name{0} == 'W') {
                    // 4.3. W???  URL link frames
                    // URL              <text string>
                    if (!$this->valid_url($source_data_array['data'], false, false)) {
                        throw new getid3_exception('Invalid URL in '.$frame_name.' ('.$source_data_array['data'].')');
                    } else {
                        $frame_data .= $source_data_array['data'];
                    }
                } else {
                    throw new getid3_exception($frame_name.' not supported by generate_frame_data()');
                }
                break;
        }

        return $frame_data;
    }


    protected function frame_allowed($frame_name, $source_data_array) {

        if (getid3_id3v2_write::major_version == 4) {
            switch ($frame_name) {
                case 'UFID':
                case 'AENC':
                case 'ENCR':
                case 'GRID':
                    if (!isset($source_data_array['ownerid'])) {
                        throw new getid3_exception('[ownerid] not specified for '.$frame_name);
                    }
                    if (in_array($frame_name.$source_data_array['ownerid'], $this->previous_frames)) {
                        throw new getid3_exception('Only one '.$frame_name.' tag allowed with the same OwnerID ('.$source_data_array['ownerid'].')');
                    }
                    $this->previous_frames[] = $frame_name.$source_data_array['ownerid'];
                    break;

                case 'TXXX':
                case 'WXXX':
                case 'RVA2':
                case 'EQU2':
                case 'APIC':
                case 'GEOB':
                    if (!isset($source_data_array['description'])) {
                        throw new getid3_exception('[description] not specified for '.$frame_name);
                    }
                    if (in_array($frame_name.$source_data_array['description'], $this->previous_frames)) {
                        throw new getid3_exception('Only one '.$frame_name.' tag allowed with the same Description ('.$source_data_array['description'].')');
                    }
                    $this->previous_frames[] = $frame_name.$source_data_array['description'];
                    break;

                case 'USER':
                    if (!isset($source_data_array['language'])) {
                        throw new getid3_exception('[language] not specified for '.$frame_name);
                    }
                    if (in_array($frame_name.$source_data_array['language'], $this->previous_frames)) {
                        throw new getid3_exception('Only one '.$frame_name.' tag allowed with the same Language ('.$source_data_array['language'].')');
                    }
                    $this->previous_frames[] = $frame_name.$source_data_array['language'];
                    break;

                case 'USLT':
                case 'SYLT':
                case 'COMM':
                    if (!isset($source_data_array['language'])) {
                        throw new getid3_exception('[language] not specified for '.$frame_name);
                    }
                    if (!isset($source_data_array['description'])) {
                        throw new getid3_exception('[description] not specified for '.$frame_name);
                    }
                    if (in_array($frame_name.$source_data_array['language'].$source_data_array['description'], $this->previous_frames)) {
                        throw new getid3_exception('Only one '.$frame_name.' tag allowed with the same Language + Description ('.$source_data_array['language'].' + '.$source_data_array['description'].')');
                    }
                    $this->previous_frames[] = $frame_name.$source_data_array['language'].$source_data_array['description'];
                    break;

                case 'POPM':
                    if (!isset($source_data_array['email'])) {
                        throw new getid3_exception('[email] not specified for '.$frame_name);
                    }
                    if (in_array($frame_name.$source_data_array['email'], $this->previous_frames)) {
                        throw new getid3_exception('Only one '.$frame_name.' tag allowed with the same Email ('.$source_data_array['email'].')');
                    }
                    $this->previous_frames[] = $frame_name.$source_data_array['email'];
                    break;

                case 'IPLS':
                case 'MCDI':
                case 'ETCO':
                case 'MLLT':
                case 'SYTC':
                case 'RVRB':
                case 'PCNT':
                case 'RBUF':
                case 'POSS':
                case 'OWNE':
                case 'SEEK':
                case 'ASPI':
                case 'RGAD':
                    if (in_array($frame_name, $this->previous_frames)) {
                        throw new getid3_exception('Only one '.$frame_name.' tag allowed');
                    }
                    $this->previous_frames[] = $frame_name;
                    break;

                case 'LINK':
                    // this isn't implemented quite right (yet) - it should check the target frame data for compliance
                    // but right now it just allows one linked frame of each type, to be safe.
                    if (!isset($source_data_array['frameid'])) {
                        throw new getid3_exception('[frameid] not specified for '.$frame_name);
                    }
                    if (in_array($frame_name.$source_data_array['frameid'], $this->previous_frames)) {
                        throw new getid3_exception('Only one '.$frame_name.' tag allowed with the same FrameID ('.$source_data_array['frameid'].')');
                    }
                    if (in_array($source_data_array['frameid'], $this->previous_frames)) {
                        // no links to singleton tags
                        throw new getid3_exception('Cannot specify a '.$frame_name.' tag to a singleton tag that already exists ('.$source_data_array['frameid'].')');
                    }
                    $this->previous_frames[] = $frame_name.$source_data_array['frameid']; // only one linked tag of this type
                    $this->previous_frames[] = $source_data_array['frameid'];             // no non-linked singleton tags of this type
                    break;

                case 'COMR':
                    //   There may be more than one 'commercial frame' in a tag, but no two may be identical
                    // Checking isn't implemented at all (yet) - just assumes that it's OK.
                    break;

                case 'PRIV':
                case 'SIGN':
                    if (!isset($source_data_array['ownerid'])) {
                        throw new getid3_exception('[ownerid] not specified for '.$frame_name);
                    }
                    if (!isset($source_data_array['data'])) {
                        throw new getid3_exception('[data] not specified for '.$frame_name);
                    }
                    if (in_array($frame_name.$source_data_array['ownerid'].$source_data_array['data'], $this->previous_frames)) {
                        throw new getid3_exception('Only one '.$frame_name.' tag allowed with the same OwnerID + Data ('.$source_data_array['ownerid'].' + '.$source_data_array['data'].')');
                    }
                    $this->previous_frames[] = $frame_name.$source_data_array['ownerid'].$source_data_array['data'];
                    break;

                default:
                    if (($frame_name{0} != 'T') && ($frame_name{0} != 'W')) {
                        throw new getid3_exception('Frame not allowed in ID3v2.'.getid3_id3v2_write::major_version.': '.$frame_name);
                    }
                    break;
            }

        } elseif (getid3_id3v2_write::major_version == 3) {

            switch ($frame_name) {
                case 'UFID':
                case 'AENC':
                case 'ENCR':
                case 'GRID':
                    if (!isset($source_data_array['ownerid'])) {
                        throw new getid3_exception('[ownerid] not specified for '.$frame_name);
                    }
                    if (in_array($frame_name.$source_data_array['ownerid'], $this->previous_frames)) {
                        throw new getid3_exception('Only one '.$frame_name.' tag allowed with the same OwnerID ('.$source_data_array['ownerid'].')');
                    }
                    $this->previous_frames[] = $frame_name.$source_data_array['ownerid'];
                    break;

                case 'TXXX':
                case 'WXXX':
                case 'APIC':
                case 'GEOB':
                    if (!isset($source_data_array['description'])) {
                        throw new getid3_exception('[description] not specified for '.$frame_name);
                    }
                    if (in_array($frame_name.$source_data_array['description'], $this->previous_frames)) {
                        throw new getid3_exception('Only one '.$frame_name.' tag allowed with the same Description ('.$source_data_array['description'].')');
                    }
                    $this->previous_frames[] = $frame_name.$source_data_array['description'];
                    break;

                case 'USER':
                    if (!isset($source_data_array['language'])) {
                        throw new getid3_exception('[language] not specified for '.$frame_name);
                    }
                    if (in_array($frame_name.$source_data_array['language'], $this->previous_frames)) {
                        throw new getid3_exception('Only one '.$frame_name.' tag allowed with the same Language ('.$source_data_array['language'].')');
                    }
                    $this->previous_frames[] = $frame_name.$source_data_array['language'];
                    break;

                case 'USLT':
                case 'SYLT':
                case 'COMM':
                    if (!isset($source_data_array['language'])) {
                        throw new getid3_exception('[language] not specified for '.$frame_name);
                    }
                    if (!isset($source_data_array['description'])) {
                        throw new getid3_exception('[description] not specified for '.$frame_name);
                    }
                    if (in_array($frame_name.$source_data_array['language'].$source_data_array['description'], $this->previous_frames)) {
                        throw new getid3_exception('Only one '.$frame_name.' tag allowed with the same Language + Description ('.$source_data_array['language'].' + '.$source_data_array['description'].')');
                    }
                    $this->previous_frames[] = $frame_name.$source_data_array['language'].$source_data_array['description'];
                    break;

                case 'POPM':
                    if (!isset($source_data_array['email'])) {
                        throw new getid3_exception('[email] not specified for '.$frame_name);
                    }
                    if (in_array($frame_name.$source_data_array['email'], $this->previous_frames)) {
                        throw new getid3_exception('Only one '.$frame_name.' tag allowed with the same Email ('.$source_data_array['email'].')');
                    }
                    $this->previous_frames[] = $frame_name.$source_data_array['email'];
                    break;

                case 'IPLS':
                case 'MCDI':
                case 'ETCO':
                case 'MLLT':
                case 'SYTC':
                case 'RVAD':
                case 'EQUA':
                case 'RVRB':
                case 'PCNT':
                case 'RBUF':
                case 'POSS':
                case 'OWNE':
                case 'RGAD':
                    if (in_array($frame_name, $this->previous_frames)) {
                        throw new getid3_exception('Only one '.$frame_name.' tag allowed');
                    }
                    $this->previous_frames[] = $frame_name;
                    break;

                case 'LINK':
                    // this isn't implemented quite right (yet) - it should check the target frame data for compliance
                    // but right now it just allows one linked frame of each type, to be safe.
                    if (!isset($source_data_array['frameid'])) {
                        throw new getid3_exception('[frameid] not specified for '.$frame_name);
                    }
                    if (in_array($frame_name.$source_data_array['frameid'], $this->previous_frames)) {
                        throw new getid3_exception('Only one '.$frame_name.' tag allowed with the same FrameID ('.$source_data_array['frameid'].')');
                    }
                    if (in_array($source_data_array['frameid'], $this->previous_frames)) {
                        // no links to singleton tags
                        throw new getid3_exception('Cannot specify a '.$frame_name.' tag to a singleton tag that already exists ('.$source_data_array['frameid'].')');
                    }
                    $this->previous_frames[] = $frame_name.$source_data_array['frameid']; // only one linked tag of this type
                    $this->previous_frames[] = $source_data_array['frameid'];             // no non-linked singleton tags of this type
                    break;

                case 'COMR':
                    //   There may be more than one 'commercial frame' in a tag, but no two may be identical
                    // Checking isn't implemented at all (yet) - just assumes that it's OK.
                    break;

                case 'PRIV':
                    if (!isset($source_data_array['ownerid'])) {
                        throw new getid3_exception('[ownerid] not specified for '.$frame_name);
                    }
                    if (!isset($source_data_array['data'])) {
                        throw new getid3_exception('[data] not specified for '.$frame_name);
                    }
                    if (in_array($frame_name.$source_data_array['ownerid'].$source_data_array['data'], $this->previous_frames)) {
                        throw new getid3_exception('Only one '.$frame_name.' tag allowed with the same OwnerID + Data ('.$source_data_array['ownerid'].' + '.$source_data_array['data'].')');
                    }
                    $this->previous_frames[] = $frame_name.$source_data_array['ownerid'].$source_data_array['data'];
                    break;

                default:
                    if (($frame_name{0} != 'T') && ($frame_name{0} != 'W')) {
                        throw new getid3_exception('Frame not allowed in ID3v2.'.getid3_id3v2_write::major_version.': '.$frame_name);
                    }
                    break;
            }
        }

        return true;
    }


    static public function ID3v2IsValidPriceString($price_string) {

        if (getid3_id3v2::LanguageLookup(substr($price_string, 0, 3), true) == '') {
            return false;
        } elseif (!$this->IsANumber(substr($price_string, 3), true)) {
            return false;
        }
        return true;
    }


    static public function ID3v2IsValidETCOevent($event_id) {

        if (($event_id < 0) || ($event_id > 0xFF)) {
            // outside range of 1 byte
            return false;
        } elseif (($event_id >= 0xF0) && ($event_id <= 0xFC)) {
            // reserved for future use
            return false;
        } elseif (($event_id >= 0x17) && ($event_id <= 0xDF)) {
            // reserved for future use
            return false;
        } elseif (($event_id >= 0x0E) && ($event_id <= 0x16) && (getid3_id3v2_write::major_version == 2)) {
            // not defined in ID3v2.2
            return false;
        } elseif (($event_id >= 0x15) && ($event_id <= 0x16) && (getid3_id3v2_write::major_version == 3)) {
            // not defined in ID3v2.3
            return false;
        }
        return true;
    }


    static public function ID3v2IsValidSYLTtype($content_type) {
        if (($content_type >= 0) && ($content_type <= 8) && (getid3_id3v2_write::major_version == 4)) {
            return true;
        } elseif (($content_type >= 0) && ($content_type <= 6) && (getid3_id3v2_write::major_version == 3)) {
            return true;
        }
        return false;
    }


    static public function ID3v2IsValidRVA2channeltype($channel_type) {

        if (($channel_type >= 0) && ($channel_type <= 8) && (getid3_id3v2_write::major_version == 4)) {
            return true;
        }
        return false;
    }


    static public function ID3v2IsValidAPICpicturetype($picture_type) {

        if (($picture_type >= 0) && ($picture_type <= 0x14) && (getid3_id3v2_write::major_version >= 2) && (getid3_id3v2_write::major_version <= 4)) {
            return true;
        }
        return false;
    }


    static public function ID3v2IsValidAPICimageformat($image_format) {

        if ($image_format == '-->') {
            return true;
        } elseif (getid3_id3v2_write::major_version == 2) {
            if ((strlen($image_format) == 3) && ($image_format == strtoupper($image_format))) {
                return true;
            }
        } elseif ((getid3_id3v2_write::major_version == 3) || (getid3_id3v2_write::major_version == 4)) {
            if ($this->IsValidMIMEstring($image_format)) {
                return true;
            }
        }
        return false;
    }


    static public function ID3v2IsValidCOMRreceivedAs($received_as) {

        if ((getid3_id3v2_write::major_version >= 3) && ($received_as >= 0) && ($received_as <= 8)) {
            return true;
        }
        return false;
    }


    static public function ID3v2IsValidRGADname($rgad_name) {

        if (($rgad_name >= 0) && ($rgad_name <= 2)) {
            return true;
        }
        return false;
    }


    static public function ID3v2IsValidRGADoriginator($rgad_originator) {

        if (($rgad_originator >= 0) && ($rgad_originator <= 3)) {
            return true;
        }
        return false;
    }


    static public function is_hash($var) {

        // written by dev-nullØchristophe*vg
        // taken from http://www.php.net/manual/en/function.array-merge-recursive.php
        if (is_array($var)) {
            $keys = array_keys($var);
            $all_num = true;
            for ($i = 0; $i < count($keys); $i++) {
                if (is_string($keys[$i])) {
                    return true;
                }
            }
        }
        return false;
    }


    static public function IsValidMIMEstring($mime_string) {

        if ((strlen($mime_string) >= 3) && (strpos($mime_string, '/') > 0) && (strpos($mime_string, '/') < (strlen($mime_string) - 1))) {
            return true;
        }
        return false;
    }


    static public function IsWithinBitRange($number, $max_bits, $signed=false) {

        if ($signed) {
            if (($number > (0 - pow(2, $max_bits - 1))) && ($number <= pow(2, $max_bits - 1))) {
                return true;
            }
        } else {
            if (($number >= 0) && ($number <= pow(2, $max_bits))) {
                return true;
            }
        }
        return false;
    }


    static public function safe_parse_url($url) {

        $parts = @parse_url($url);
        $parts['scheme'] = (isset($parts['scheme']) ? $parts['scheme'] : '');
        $parts['host']   = (isset($parts['host'])   ? $parts['host']   : '');
        $parts['user']   = (isset($parts['user'])   ? $parts['user']   : '');
        $parts['pass']   = (isset($parts['pass'])   ? $parts['pass']   : '');
        $parts['path']   = (isset($parts['path'])   ? $parts['path']   : '');
        $parts['query']  = (isset($parts['query'])  ? $parts['query']  : '');
        return $parts;
    }


///////////////////////
///////////////////////
///////////////////////
///////////////////////
///////////////////////
///////////////////////
///////////////////////
///////////////////////
///////////////////////
///////////////////////
///////////////////////
///////////////////////
///////////////////////
    ///////////////////////
    //// // probably should be an error, need to rewrite valid_url() to handle other encodings
    ///////////////////////
    ///////////////////////
    ///////////////////////
    ///////////////////////
    ///////////////////////

    static public function valid_url($url, $allow_user_pass=false) {

        if ($url == '') {
            return false;
        }
        if ($allow_user_pass !== true) {
            if (strstr($url, '@')) {
                // in the format http://user:pass@example.com  or http://user@example.com
                // but could easily be somebody incorrectly entering an email address in place of a URL
                return false;
            }
        }
        if ($parts = $this->safe_parse_url($url)) {
            if (($parts['scheme'] != 'http') && ($parts['scheme'] != 'https') && ($parts['scheme'] != 'ftp') && ($parts['scheme'] != 'gopher')) {
                return false;
            } elseif (!eregi("^[[:alnum:]]([-.]?[0-9a-z])*\.[a-z]{2,3}$", $parts['host'], $regs) && !IsValidDottedIP($parts['host'])) {
                return false;
            } elseif (!eregi("^([[:alnum:]-]|[\_])*$", $parts['user'], $regs)) {
                return false;
            } elseif (!eregi("^([[:alnum:]-]|[\_])*$", $parts['pass'], $regs)) {
                return false;
            } elseif (!eregi("^[[:alnum:]/_\.@~-]*$", $parts['path'], $regs)) {
                return false;
            } elseif (!eregi("^[[:alnum:]?&=+:;_()%#/,\.-]*$", $parts['query'], $regs)) {
                return false;
            } else {
                return true;
            }
        }
        return false;
    }







    public static function BigEndian2String($number, $min_bytes=1, $synch_safe=false, $signed=false) {
		
		if ($number < 0) {
			return false;
		}
		
		$maskbyte = (($synch_safe || $signed) ? 0x7F : 0xFF);
		
		$intstring = '';
		
		if ($signed) {
			if ($min_bytes > 4) {
				die('INTERNAL ERROR: Cannot have signed integers larger than 32-bits in BigEndian2String()');
			}
			$number = $number & (0x80 << (8 * ($min_bytes - 1)));
		}
		
		while ($number != 0) {
			$quotient = ($number / ($maskbyte + 1));
			$intstring = chr(ceil(($quotient - floor($quotient)) * $maskbyte)).$intstring;
			$number = floor($quotient);
		}
		return str_pad($intstring, $min_bytes, "\x00", STR_PAD_LEFT);
	}
}


?>