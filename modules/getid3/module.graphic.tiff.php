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
// | module.graphic.tiff.php                                              |
// | Module for analyzing TIFF graphic files.                             |
// | dependencies: NONE                                                   |
// +----------------------------------------------------------------------+
//
// $Id: module.graphic.tiff.php,v 1.2 2006/11/02 10:48:02 ah Exp $

        
        
class getid3_tiff extends getid3_handler
{

    public function Analyze() {
        
        $getid3 = $this->getid3;

        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);
        $tiff_header = fread($getid3->fp, 4);

        $getid3->info['tiff']['byte_order'] = substr($tiff_header, 0, 2) == 'II' ? 'Intel' : 'Motorola';
        $endian2int = substr($tiff_header, 0, 2) == 'II' ? 'LittleEndian2Int' : 'BigEndian2Int';
                
        $getid3->info['fileformat']          = 'tiff';
        $getid3->info['video']['dataformat'] = 'tiff';
        $getid3->info['video']['lossless']   = true;
        $getid3->info['tiff']['ifd']         = array ();
        $current_ifd                         = array ();

        $field_type_byte_length = array (1=>1, 2=>1, 3=>2, 4=>4, 5=>8);

        $next_ifd_offset = getid3_lib::$endian2int(fread($getid3->fp, 4));

        while ($next_ifd_offset > 0) {

            $current_ifd['offset'] = $next_ifd_offset;

            fseek($getid3->fp, $getid3->info['avdataoffset'] + $next_ifd_offset, SEEK_SET);
            $current_ifd['fieldcount'] = getid3_lib::$endian2int(fread($getid3->fp, 2));

            for ($i = 0; $i < $current_ifd['fieldcount']; $i++) {
                
                // shortcut
                $current_ifd['fields'][$i] = array ();
                $current_ifd_fields_i = &$current_ifd['fields'][$i];
                
                $current_ifd_fields_i['raw']['tag']    = getid3_lib::$endian2int(fread($getid3->fp, 2));
                $current_ifd_fields_i['raw']['type']   = getid3_lib::$endian2int(fread($getid3->fp, 2));
                $current_ifd_fields_i['raw']['length'] = getid3_lib::$endian2int(fread($getid3->fp, 4));
                $current_ifd_fields_i['raw']['offset'] = fread($getid3->fp, 4);

                switch ($current_ifd_fields_i['raw']['type']) {
                    case 1: // BYTE  An 8-bit unsigned integer.
                        if ($current_ifd_fields_i['raw']['length'] <= 4) {
                            $current_ifd_fields_i['value']  = getid3_lib::$endian2int(substr($current_ifd_fields_i['raw']['offset'], 0, 1));
                        } else {
                            $current_ifd_fields_i['offset'] = getid3_lib::$endian2int($current_ifd_fields_i['raw']['offset']);
                        }
                        break;

                    case 2: // ASCII 8-bit bytes  that store ASCII codes; the last byte must be null.
                        if ($current_ifd_fields_i['raw']['length'] <= 4) {
                            $current_ifd_fields_i['value']  = substr($current_ifd_fields_i['raw']['offset'], 3);
                        } else {
                            $current_ifd_fields_i['offset'] = getid3_lib::$endian2int($current_ifd_fields_i['raw']['offset']);
                        }
                        break;

                    case 3: // SHORT A 16-bit (2-byte) unsigned integer.
                        if ($current_ifd_fields_i['raw']['length'] <= 2) {
                            $current_ifd_fields_i['value']  = getid3_lib::$endian2int(substr($current_ifd_fields_i['raw']['offset'], 0, 2));
                        } else {
                            $current_ifd_fields_i['offset'] = getid3_lib::$endian2int($current_ifd_fields_i['raw']['offset']);
                        }
                        break;

                    case 4: // LONG  A 32-bit (4-byte) unsigned integer.
                        if ($current_ifd_fields_i['raw']['length'] <= 1) {
                            $current_ifd_fields_i['value']  = getid3_lib::$endian2int($current_ifd_fields_i['raw']['offset']);
                        } else {
                            $current_ifd_fields_i['offset'] = getid3_lib::$endian2int($current_ifd_fields_i['raw']['offset']);
                        }
                        break;

                    case 5: // RATIONAL   Two LONG_s:  the first represents the numerator of a fraction, the second the denominator.
                        break;
                }
            }

            $getid3->info['tiff']['ifd'][] = $current_ifd;
            $current_ifd = array ();
            $next_ifd_offset = getid3_lib::$endian2int(fread($getid3->fp, 4));

        }

        foreach ($getid3->info['tiff']['ifd'] as $ifd_id => $ifd_array) {
            foreach ($ifd_array['fields'] as $key => $field_array) {
                switch ($field_array['raw']['tag']) {
                    case 256: // ImageWidth
                    case 257: // ImageLength
                    case 258: // BitsPerSample
                    case 259: // Compression
                        if (!isset($field_array['value'])) {
                            fseek($getid3->fp, $field_array['offset'], SEEK_SET);
                            $getid3->info['tiff']['ifd'][$ifd_id]['fields'][$key]['raw']['data'] = fread($getid3->fp, $field_array['raw']['length'] * $field_type_byte_length[$field_array['raw']['type']]);
                        }
                        break;

                    case 270: // ImageDescription
                    case 271: // Make
                    case 272: // Model
                    case 305: // Software
                    case 306: // DateTime
                    case 315: // Artist
                    case 316: // HostComputer
                        if (isset($field_array['value'])) {
                            $getid3->info['tiff']['ifd'][$ifd_id]['fields'][$key]['raw']['data'] = $field_array['value'];
                        } else {
                            fseek($getid3->fp, $field_array['offset'], SEEK_SET);
                            $getid3->info['tiff']['ifd'][$ifd_id]['fields'][$key]['raw']['data'] = fread($getid3->fp, $field_array['raw']['length'] * $field_type_byte_length[$field_array['raw']['type']]);
                        }
                        break;
                }
                switch ($field_array['raw']['tag']) {
                    case 256: // ImageWidth
                        $getid3->info['video']['resolution_x'] = $field_array['value'];
                        break;

                    case 257: // ImageLength
                        $getid3->info['video']['resolution_y'] = $field_array['value'];
                        break;

                    case 258: // BitsPerSample
                        if (isset($field_array['value'])) {
                            $getid3->info['video']['bits_per_sample'] = $field_array['value'];
                        } else {
                            $getid3->info['video']['bits_per_sample'] = 0;
                            for ($i = 0; $i < $field_array['raw']['length']; $i++) {
                                $getid3->info['video']['bits_per_sample'] += getid3_lib::$endian2int(substr($getid3->info['tiff']['ifd'][$ifd_id]['fields'][$key]['raw']['data'], $i * $field_type_byte_length[$field_array['raw']['type']], $field_type_byte_length[$field_array['raw']['type']]));
                            }
                        }
                        break;

                    case 259: // Compression
                        $getid3->info['video']['codec'] = getid3_tiff::TIFFcompressionMethod($field_array['value']);
                        break;

                    case 270: // ImageDescription
                    case 271: // Make
                    case 272: // Model
                    case 305: // Software
                    case 306: // DateTime
                    case 315: // Artist
                    case 316: // HostComputer
                        @$getid3->info['tiff']['comments'][getid3_tiff::TIFFcommentName($field_array['raw']['tag'])][] = $getid3->info['tiff']['ifd'][$ifd_id]['fields'][$key]['raw']['data'];
                        break;

                    default:
                        break;
                }
            }
        }

        return true;
    }



    public static function TIFFcompressionMethod($id) {
        
        static $lookup = array (
            1     => 'Uncompressed',
            2     => 'Huffman',
            3     => 'Fax - CCITT 3',
            5     => 'LZW',
            32773 => 'PackBits',
        );
        return (isset($lookup[$id]) ? $lookup[$id] : 'unknown/invalid ('.$id.')');
    }



    public static function TIFFcommentName($id) {
        
        static $lookup = array (
            270 => 'imagedescription',
            271 => 'make',
            272 => 'model',
            305 => 'software',
            306 => 'datetime',
            315 => 'artist',
            316 => 'hostcomputer',
        );
        return (isset($lookup[$id]) ? $lookup[$id] : 'unknown/invalid ('.$id.')');
    }

}


?>