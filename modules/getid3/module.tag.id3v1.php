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
// | module.tag.id3v1.php                                                 |
// | module for analyzing ID3v1 tags                                      |
// | dependencies: NONE                                                   |
// +----------------------------------------------------------------------+
//
// $Id: module.tag.id3v1.php,v 1.6 2006/11/16 16:19:52 ah Exp $

        
        
class getid3_id3v1 extends getid3_handler
{

    public function Analyze() {

        $getid3 = $this->getid3;
                
        fseek($getid3->fp, -256, SEEK_END);
        $pre_id3v1 = fread($getid3->fp, 128);
        $id3v1_tag = fread($getid3->fp, 128);

        if (substr($id3v1_tag, 0, 3) == 'TAG') {
        
            $getid3->info['avdataend'] -= 128;
        
            // Shortcut
            $getid3->info['id3v1'] = array ();
            $info_id3v1 = &$getid3->info['id3v1'];

            $info_id3v1['title']   = getid3_id3v1::cutfield(substr($id3v1_tag,  3, 30));
            $info_id3v1['artist']  = getid3_id3v1::cutfield(substr($id3v1_tag, 33, 30));
            $info_id3v1['album']   = getid3_id3v1::cutfield(substr($id3v1_tag, 63, 30));
            $info_id3v1['year']    = getid3_id3v1::cutfield(substr($id3v1_tag, 93,  4));
            $info_id3v1['comment'] = substr($id3v1_tag,  97, 30);  // can't remove nulls yet, track detection depends on them
            $info_id3v1['genreid'] = ord(substr($id3v1_tag, 127, 1));

            // If second-last byte of comment field is null and last byte of comment field is non-null then this is ID3v1.1 and the comment field is 28 bytes long and the 30th byte is the track number
            if (($id3v1_tag{125} === "\x00") && ($id3v1_tag{126} !== "\x00")) {
                $info_id3v1['track']   = ord(substr($info_id3v1['comment'], 29,  1));
                $info_id3v1['comment'] =     substr($info_id3v1['comment'],  0, 28);
            }
            $info_id3v1['comment'] = getid3_id3v1::cutfield($info_id3v1['comment']);

            $info_id3v1['genre'] = getid3_id3v1::LookupGenreName($info_id3v1['genreid']);
            if (!empty($info_id3v1['genre'])) {
                unset($info_id3v1['genreid']);
            }
            if (empty($info_id3v1['genre']) || (@$info_id3v1['genre'] == 'Unknown')) {
                unset($info_id3v1['genre']);
            }

            foreach ($info_id3v1 as $key => $value) {
                $key != 'comments' and $info_id3v1['comments'][$key][0] = $value;
            }

            $info_id3v1['tag_offset_end']   = filesize($getid3->filename);
            $info_id3v1['tag_offset_start'] = $info_id3v1['tag_offset_end'] - 128;
        }   
            
        if (substr($pre_id3v1, 0, 3) == 'TAG') {
            // The way iTunes handles tags is, well, brain-damaged.
            // It completely ignores v1 if ID3v2 is present.
            // This goes as far as adding a new v1 tag *even if there already is one*

            // A suspected double-ID3v1 tag has been detected, but it could be that the "TAG" identifier is a legitimate part of an APE or Lyrics3 tag
            if (substr($pre_id3v1, 96, 8) == 'APETAGEX') {
                // an APE tag footer was found before the last ID3v1, assume false "TAG" synch
            } elseif (substr($pre_id3v1, 119, 6) == 'LYRICS') {
                // a Lyrics3 tag footer was found before the last ID3v1, assume false "TAG" synch
            } else {
                // APE and Lyrics3 footers not found - assume double ID3v1
                $getid3->warning('Duplicate ID3v1 tag detected - this has been known to happen with iTunes.');
                $getid3->info['avdataend'] -= 128;
            }
        }

        return true;
    }
    
    

    public static function cutfield($str) {
        
        return trim(substr($str, 0, strcspn($str, "\x00")));
    }



    public static function ArrayOfGenres($allow_SCMPX_extended=false) {
        
        static $lookup = array (
            0    => 'Blues',
            1    => 'Classic Rock',
            2    => 'Country',
            3    => 'Dance',
            4    => 'Disco',
            5    => 'Funk',
            6    => 'Grunge',
            7    => 'Hip-Hop',
            8    => 'Jazz',
            9    => 'Metal',
            10   => 'New Age',
            11   => 'Oldies',
            12   => 'Other',
            13   => 'Pop',
            14   => 'R&B',
            15   => 'Rap',
            16   => 'Reggae',
            17   => 'Rock',
            18   => 'Techno',
            19   => 'Industrial',
            20   => 'Alternative',
            21   => 'Ska',
            22   => 'Death Metal',
            23   => 'Pranks',
            24   => 'Soundtrack',
            25   => 'Euro-Techno',
            26   => 'Ambient',
            27   => 'Trip-Hop',
            28   => 'Vocal',
            29   => 'Jazz+Funk',
            30   => 'Fusion',
            31   => 'Trance',
            32   => 'Classical',
            33   => 'Instrumental',
            34   => 'Acid',
            35   => 'House',
            36   => 'Game',
            37   => 'Sound Clip',
            38   => 'Gospel',
            39   => 'Noise',
            40   => 'Alt. Rock',
            41   => 'Bass',
            42   => 'Soul',
            43   => 'Punk',
            44   => 'Space',
            45   => 'Meditative',
            46   => 'Instrumental Pop',
            47   => 'Instrumental Rock',
            48   => 'Ethnic',
            49   => 'Gothic',
            50   => 'Darkwave',
            51   => 'Techno-Industrial',
            52   => 'Electronic',
            53   => 'Pop-Folk',
            54   => 'Eurodance',
            55   => 'Dream',
            56   => 'Southern Rock',
            57   => 'Comedy',
            58   => 'Cult',
            59   => 'Gangsta Rap',
            60   => 'Top 40',
            61   => 'Christian Rap',
            62   => 'Pop/Funk',
            63   => 'Jungle',
            64   => 'Native American',
            65   => 'Cabaret',
            66   => 'New Wave',
            67   => 'Psychedelic',
            68   => 'Rave',
            69   => 'Showtunes',
            70   => 'Trailer',
            71   => 'Lo-Fi',
            72   => 'Tribal',
            73   => 'Acid Punk',
            74   => 'Acid Jazz',
            75   => 'Polka',
            76   => 'Retro',
            77   => 'Musical',
            78   => 'Rock & Roll',
            79   => 'Hard Rock',
            80   => 'Folk',
            81   => 'Folk/Rock',
            82   => 'National Folk',
            83   => 'Swing',
            84   => 'Fast-Fusion',
            85   => 'Bebob',
            86   => 'Latin',
            87   => 'Revival',
            88   => 'Celtic',
            89   => 'Bluegrass',
            90   => 'Avantgarde',
            91   => 'Gothic Rock',
            92   => 'Progressive Rock',
            93   => 'Psychedelic Rock',
            94   => 'Symphonic Rock',
            95   => 'Slow Rock',
            96   => 'Big Band',
            97   => 'Chorus',
            98   => 'Easy Listening',
            99   => 'Acoustic',
            100  => 'Humour',
            101  => 'Speech',
            102  => 'Chanson',
            103  => 'Opera',
            104  => 'Chamber Music',
            105  => 'Sonata',
            106  => 'Symphony',
            107  => 'Booty Bass',
            108  => 'Primus',
            109  => 'Porn Groove',
            110  => 'Satire',
            111  => 'Slow Jam',
            112  => 'Club',
            113  => 'Tango',
            114  => 'Samba',
            115  => 'Folklore',
            116  => 'Ballad',
            117  => 'Power Ballad',
            118  => 'Rhythmic Soul',
            119  => 'Freestyle',
            120  => 'Duet',
            121  => 'Punk Rock',
            122  => 'Drum Solo',
            123  => 'A Cappella',
            124  => 'Euro-House',
            125  => 'Dance Hall',
            126  => 'Goa',
            127  => 'Drum & Bass',
            128  => 'Club-House',
            129  => 'Hardcore',
            130  => 'Terror',
            131  => 'Indie',
            132  => 'BritPop',
            133  => 'Negerpunk',
            134  => 'Polsk Punk',
            135  => 'Beat',
            136  => 'Christian Gangsta Rap',
            137  => 'Heavy Metal',
            138  => 'Black Metal',
            139  => 'Crossover',
            140  => 'Contemporary Christian',
            141  => 'Christian Rock',
            142  => 'Merengue',
            143  => 'Salsa',
            144  => 'Trash Metal',
            145  => 'Anime',
            146  => 'JPop',
            147  => 'Synthpop',

            255  => 'Unknown',

            'CR' => 'Cover',
            'RX' => 'Remix'
        );

        static $lookupSCMPX = array ();
        if ($allow_SCMPX_extended && empty($lookupSCMPX)) {
            $lookupSCMPX = $lookup;
            // http://www.geocities.co.jp/SiliconValley-Oakland/3664/alittle.html#GenreExtended
            // Extended ID3v1 genres invented by SCMPX
            // Note that 255 "Japanese Anime" conflicts with standard "Unknown"
            $lookupSCMPX[240] = 'Sacred';
            $lookupSCMPX[241] = 'Northern Europe';
            $lookupSCMPX[242] = 'Irish & Scottish';
            $lookupSCMPX[243] = 'Scotland';
            $lookupSCMPX[244] = 'Ethnic Europe';
            $lookupSCMPX[245] = 'Enka';
            $lookupSCMPX[246] = 'Children\'s Song';
            $lookupSCMPX[247] = 'Japanese Sky';
            $lookupSCMPX[248] = 'Japanese Heavy Rock';
            $lookupSCMPX[249] = 'Japanese Doom Rock';
            $lookupSCMPX[250] = 'Japanese J-POP';
            $lookupSCMPX[251] = 'Japanese Seiyu';
            $lookupSCMPX[252] = 'Japanese Ambient Techno';
            $lookupSCMPX[253] = 'Japanese Moemoe';
            $lookupSCMPX[254] = 'Japanese Tokusatsu';
            //$lookupSCMPX[255] = 'Japanese Anime';
        }

        return ($allow_SCMPX_extended ? $lookupSCMPX : $lookup);
    }



    public static function LookupGenreName($genre_id, $allow_SCMPX_extended=true) {
    
        switch ($genre_id) {
            case 'RX':
            case 'CR':
                break;
            default:
                $genre_id = intval($genre_id); // to handle 3 or '3' or '03'
                break;
        }
        $lookup = getid3_id3v1::ArrayOfGenres($allow_SCMPX_extended);
        return (isset($lookup[$genre_id]) ? $lookup[$genre_id] : false);
    }
    

    public static function LookupGenreID($genre, $allow_SCMPX_extended=false) {
        
        $lookup = getid3_id3v1::ArrayOfGenres($allow_SCMPX_extended);
        $lower_case_no_space_search_term = strtolower(str_replace(' ', '', $genre));
        foreach ($lookup as $key => $value) {
            foreach ($lookup as $key => $value) {
                if (strtolower(str_replace(' ', '', $value)) == $lower_case_no_space_search_term) {
                    return $key;
                }
            }
            return false;
        }
        return (isset($lookup[$genre_id]) ? $lookup[$genre_id] : false);
    }

}


?>