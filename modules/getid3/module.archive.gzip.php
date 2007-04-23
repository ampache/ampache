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
// | module.archive.gzip.php                                              |
// | module for analyzing GZIP files                                      |
// | dependencies: PHP compiled with zlib support (optional)              |
// +----------------------------------------------------------------------+
// | Module originally written by Mike Mozolin <teddybearØmail*ru>        |
// +----------------------------------------------------------------------+
//
// $Id: module.archive.gzip.php,v 1.4 2006/12/04 16:00:35 ah Exp $

        
        
class getid3_gzip extends getid3_handler
{

    // public: Optional file list - disable for speed.
    public $option_gzip_parse_contents = true; // decode gzipped files, if possible, and parse recursively (.tar.gz for example)

    
    // Reads the gzip-file
    function Analyze() {

        $info = &$this->getid3->info;

        $info['fileformat'] = 'gzip';

        $start_length = 10;
        $unpack_header = 'a1id1/a1id2/a1cmethod/a1flags/a4mtime/a1xflags/a1os';
        
        //+---+---+---+---+---+---+---+---+---+---+
        //|ID1|ID2|CM |FLG|     MTIME     |XFL|OS |
        //+---+---+---+---+---+---+---+---+---+---+
        
        @fseek($this->getid3->fp, 0);
        $buffer = @fread($this->getid3->fp, $info['filesize']);

        $arr_members = explode("\x1F\x8B\x08", $buffer);
        
        while (true) {
            $is_wrong_members = false;
            $num_members = intval(count($arr_members));
            for ($i = 0; $i < $num_members; $i++) {
                if (strlen($arr_members[$i]) == 0) {
                    continue;
                }
                $buf = "\x1F\x8B\x08".$arr_members[$i];

                $attr = unpack($unpack_header, substr($buf, 0, $start_length));
                if (!$this->get_os_type(ord($attr['os']))) {
        
                    // Merge member with previous if wrong OS type
                    $arr_members[$i - 1] .= $buf;
                    $arr_members[$i] = '';
                    $is_wrong_members = true;
                    continue;
                }
            }
            if (!$is_wrong_members) {
                break;
            }
        }

        $fpointer = 0;
        $idx = 0;
        for ($i = 0; $i < $num_members; $i++) {
            if (strlen($arr_members[$i]) == 0) {
                continue;
            }
            $info_gzip_member_header_idx = &$info['gzip']['member_header'][++$idx];

            $buff = "\x1F\x8B\x08".$arr_members[$i];

            $attr = unpack($unpack_header, substr($buff, 0, $start_length));
            $info_gzip_member_header_idx['filemtime']      = getid3_lib::LittleEndian2Int($attr['mtime']);
            $info_gzip_member_header_idx['raw']['id1']     = ord($attr['cmethod']);
            $info_gzip_member_header_idx['raw']['id2']     = ord($attr['cmethod']);
            $info_gzip_member_header_idx['raw']['cmethod'] = ord($attr['cmethod']);
            $info_gzip_member_header_idx['raw']['os']      = ord($attr['os']);
            $info_gzip_member_header_idx['raw']['xflags']  = ord($attr['xflags']);
            $info_gzip_member_header_idx['raw']['flags']   = ord($attr['flags']);

            $info_gzip_member_header_idx['flags']['crc16']    = (bool) ($info_gzip_member_header_idx['raw']['flags'] & 0x02);
            $info_gzip_member_header_idx['flags']['extra']    = (bool) ($info_gzip_member_header_idx['raw']['flags'] & 0x04);
            $info_gzip_member_header_idx['flags']['filename'] = (bool) ($info_gzip_member_header_idx['raw']['flags'] & 0x08);
            $info_gzip_member_header_idx['flags']['comment']  = (bool) ($info_gzip_member_header_idx['raw']['flags'] & 0x10);

            $info_gzip_member_header_idx['compression'] = $this->get_xflag_type($info_gzip_member_header_idx['raw']['xflags']);

            $info_gzip_member_header_idx['os'] = $this->get_os_type($info_gzip_member_header_idx['raw']['os']);
            if (!$info_gzip_member_header_idx['os']) {
                $info['error'][] = 'Read error on gzip file';
                return false;
            }

            $fpointer = 10;
            $arr_xsubfield = array ();
            
            // bit 2 - FLG.FEXTRA
            //+---+---+=================================+
            //| XLEN  |...XLEN bytes of "extra field"...|
            //+---+---+=================================+
            
            if ($info_gzip_member_header_idx['flags']['extra']) {
                $w_xlen = substr($buff, $fpointer, 2);
                $xlen = getid3_lib::LittleEndian2Int($w_xlen);
                $fpointer += 2;

                $info_gzip_member_header_idx['raw']['xfield'] = substr($buff, $fpointer, $xlen);
            
                // Extra SubFields
                //+---+---+---+---+==================================+
                //|SI1|SI2|  LEN  |... LEN bytes of subfield data ...|
                //+---+---+---+---+==================================+
            
                $idx = 0;
                while (true) {
                    if ($idx >= $xlen) {
                        break;
                    }
                    $si1 = ord(substr($buff, $fpointer + $idx++, 1));
                    $si2 = ord(substr($buff, $fpointer + $idx++, 1));
                    if (($si1 == 0x41) && ($si2 == 0x70)) {
                        $w_xsublen = substr($buff, $fpointer+$idx, 2);
                        $xsublen = getid3_lib::LittleEndian2Int($w_xsublen);
                        $idx += 2;
                        $arr_xsubfield[] = substr($buff, $fpointer+$idx, $xsublen);
                        $idx += $xsublen;
                    } else {
                        break;
                    }
                }
                $fpointer += $xlen;
            }
            
            // bit 3 - FLG.FNAME
            //+=========================================+
            //|...original file name, zero-terminated...|
            //+=========================================+
            // GZIP files may have only one file, with no filename, so assume original filename is current filename without .gz
            
            $info_gzip_member_header_idx['filename'] = eregi_replace('.gz$', '', @$info['filename']);
            if ($info_gzip_member_header_idx['flags']['filename']) {
                while (true) {
                    if (ord($buff[$fpointer]) == 0) {
                        $fpointer++;
                        break;
                    }
                    $info_gzip_member_header_idx['filename'] .= $buff[$fpointer];
                    $fpointer++;
                }
            }
            
            // bit 4 - FLG.FCOMMENT
            //+===================================+
            //|...file comment, zero-terminated...|
            //+===================================+
            
            if ($info_gzip_member_header_idx['flags']['comment']) {
                while (true) {
                    if (ord($buff[$fpointer]) == 0) {
                        $fpointer++;
                        break;
                    }
                    $info_gzip_member_header_idx['comment'] .= $buff[$fpointer];
                    $fpointer++;
                }
            }
            
            // bit 1 - FLG.FHCRC
            //+---+---+
            //| CRC16 |
            //+---+---+
            
            if ($info_gzip_member_header_idx['flags']['crc16']) {
                $w_crc = substr($buff, $fpointer, 2);
                $info_gzip_member_header_idx['crc16'] = getid3_lib::LittleEndian2Int($w_crc);
                $fpointer += 2;
            }
            
            // bit 0 - FLG.FTEXT
            //if ($info_gzip_member_header_idx['raw']['flags'] & 0x01) {
            //  Ignored...
            //}
            // bits 5, 6, 7 - reserved

            $info_gzip_member_header_idx['crc32']    = getid3_lib::LittleEndian2Int(substr($buff, strlen($buff) - 8, 4));
            $info_gzip_member_header_idx['filesize'] = getid3_lib::LittleEndian2Int(substr($buff, strlen($buff) - 4));

            if ($this->option_gzip_parse_contents) {

                // Try to inflate GZip
                
                if (!function_exists('gzinflate')) {
                    $this->getid3->warning('PHP does not have zlib support - contents not parsed.');
                    return true;
                }

                $csize = 0;
                $inflated = '';
                $chkcrc32 = '';

                $cdata = substr($buff, $fpointer);
                $cdata = substr($cdata, 0, strlen($cdata) - 8);
                $csize = strlen($cdata);
                $inflated = gzinflate($cdata);

                // Calculate CRC32 for inflated content
                $info_gzip_member_header_idx['crc32_valid'] = (bool) (sprintf('%u', crc32($inflated)) == $info_gzip_member_header_idx['crc32']);

                
                //// Analyse contents
                
                // write content to temp file
                if (($temp_file_name = tempnam('*', 'getID3'))  === false) {
                    throw new getid3_exception('Unable to create temporary file.');
                }

                if ($tmp = fopen($temp_file_name, 'wb')) {
                    fwrite($tmp, $inflated);
                    fclose($tmp);
                    
                    // clone getid3 - we want same settings
                    $clone = clone $this->getid3;
                    unset($clone->info);
                    try {
                        $clone->Analyze($temp_file_name);
                        $info_gzip_member_header_idx['parsed_content'] = $clone->info;
                    }
                    catch (getid3_exception $e) {
                        // unable to parse contents
                    }
                    
                    unlink($temp_file_name);
                }
            
                // Unknown/unhandled format 
                else {
                                        
                }
            }
        }
        return true;
    }


    // Converts the OS type
    public static function get_os_type($key) {
        static $os_type = array (
            '0'   => 'FAT filesystem (MS-DOS, OS/2, NT/Win32)',
            '1'   => 'Amiga',
            '2'   => 'VMS (or OpenVMS)',
            '3'   => 'Unix',
            '4'   => 'VM/CMS',
            '5'   => 'Atari TOS',
            '6'   => 'HPFS filesystem (OS/2, NT)',
            '7'   => 'Macintosh',
            '8'   => 'Z-System',
            '9'   => 'CP/M',
            '10'  => 'TOPS-20',
            '11'  => 'NTFS filesystem (NT)',
            '12'  => 'QDOS',
            '13'  => 'Acorn RISCOS',
            '255' => 'unknown'
        );
        return @$os_type[$key];
    }


    // Converts the eXtra FLags
    public static function get_xflag_type($key) {
        static $xflag_type = array (
            '0' => 'unknown',
            '2' => 'maximum compression',
            '4' => 'fastest algorithm'
        );
        return @$xflag_type[$key];
    }
    
}

?>