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
// | module.archive.tar.php                                               |
// | module for analyzing TAR files                                       |
// | dependencies: NONE                                                   |
// +----------------------------------------------------------------------+
// | Module originally written by Mike Mozolin <teddybearØmail*ru>        |
// +----------------------------------------------------------------------+
//
// $Id: module.archive.tar.php,v 1.2 2006/11/02 10:48:00 ah Exp $

        
        
class getid3_tar extends getid3_handler
{

    function Analyze() {

        $info = &$this->getid3->info;

        $info['fileformat'] = 'tar';

        $fp = $this->getid3->fp;
        
        fseek($fp, 0);
        
        $unpack_header = 'a100fname/a8mode/a8uid/a8gid/a12size/a12mtime/a8chksum/a1typflag/a100lnkname/a6magic/a2ver/a32uname/a32gname/a8devmaj/a8devmin/a155/prefix';

        $null_512k = str_repeat("\0", 512); // end-of-file marker

        $already_warned = false;

        while (!feof($fp)) {
            
            $buffer = fread($fp, 512);
            
            // check the block
            $checksum = 0;
            for ($i = 0; $i < 148; $i++) {
                $checksum += ord(substr($buffer, $i, 1));
            }
            for ($i = 148; $i < 156; $i++) {
                $checksum += ord(' ');
            }
            for ($i = 156; $i < 512; $i++) {
                $checksum += ord(substr($buffer, $i, 1));
            }
            $attr    = unpack($unpack_header, $buffer);
            $name    =        trim(@$attr['fname']);
            $mode    = octdec(trim(@$attr['mode']));
            $uid     = octdec(trim(@$attr['uid']));
            $gid     = octdec(trim(@$attr['gid']));
            $size    = octdec(trim(@$attr['size']));
            $mtime   = octdec(trim(@$attr['mtime']));
            $chksum  = octdec(trim(@$attr['chksum']));
            $typflag =        trim(@$attr['typflag']);
            $lnkname =        trim(@$attr['lnkname']);
            $magic   =        trim(@$attr['magic']);
            $ver     =        trim(@$attr['ver']);
            $uname   =        trim(@$attr['uname']);
            $gname   =        trim(@$attr['gname']);
            $devmaj  = octdec(trim(@$attr['devmaj']));
            $devmin  = octdec(trim(@$attr['devmin']));
            $prefix  =        trim(@$attr['prefix']);

            // EOF Found
            if (($checksum == 256) && ($chksum == 0)) {
                break;
            }

            // Check if filename if 7bit as spec requires
            if (!$already_warned) {
                for ($i = 0; $i < strlen($name); $i++) {
                    if ($name{$i} < chr(32) || $name{$i} > chr(127)) {
                        $this->getid3->warning('Some filenames contains extended characters, which breaks the tar specifation. This is not uncommon, but you will have to handle the character encoding for filenames yourself.');
                        $already_warned = true;
                        break;
                    }
                }
            }
            
            if ($prefix) {
                $name = $prefix.'/'.$name;
            }
            if ((preg_match('#/$#', $name)) && !$name) {
                $typeflag = 5;
            }
            
            // If it's the end of the tar-file...
            if ($buffer == $null_512k) {
                break;
            }
            
            // Protect against tar-files with garbage at the end
            if ($name == '') {
                break;
            }
            
            $info['tar']['file_details'][$name] = array (
                'name'     => $name,
                'mode_raw' => $mode,
                'mode'     => getid3_tar::display_perms($mode),
                'uid'      => $uid,
                'gid'      => $gid,
                'size'     => $size,
                'mtime'    => $mtime,
                'chksum'   => $chksum,
                'typeflag' => getid3_tar::get_flag_type($typflag),
                'linkname' => $lnkname,
                'magic'    => $magic,
                'version'  => $ver,
                'uname'    => $uname,
                'gname'    => $gname,
                'devmajor' => $devmaj,
                'devminor' => $devmin
            );
            
            // Skip the next chunk
            fseek($fp, $size, SEEK_CUR);

            // Throw away padding
            if ($size % 512) {
                fseek($fp, 512 - $diff, SEEK_CUR);
            }
            
        }
        return true;
    }


    // Parses the file mode to file permissions
    public static function display_perms($mode) {

        // Determine Type
        if ($mode & 0x1000) {
            $type='p'; // FIFO pipe
        }
        elseif ($mode & 0x2000) {
            $type='c'; // Character special
        }
        elseif ($mode & 0x4000) {
            $type='d'; // Directory
        }
        elseif ($mode & 0x6000) {
            $type='b'; // Block special
        }
        elseif ($mode & 0x8000) {
            $type='-'; // Regular
        }
        elseif ($mode & 0xA000) {
            $type='l'; // Symbolic Link
        }
        elseif ($mode & 0xC000) {
            $type='s'; // Socket
        }
        else {
            $type='u'; // UNKNOWN
        }

        // Determine permissions
        $owner['read']    = (($mode & 00400) ? 'r' : '-');
        $owner['write']   = (($mode & 00200) ? 'w' : '-');
        $owner['execute'] = (($mode & 00100) ? 'x' : '-');
        $group['read']    = (($mode & 00040) ? 'r' : '-');
        $group['write']   = (($mode & 00020) ? 'w' : '-');
        $group['execute'] = (($mode & 00010) ? 'x' : '-');
        $world['read']    = (($mode & 00004) ? 'r' : '-');
        $world['write']   = (($mode & 00002) ? 'w' : '-');
        $world['execute'] = (($mode & 00001) ? 'x' : '-');

        // Adjust for SUID, SGID and sticky bit
        if ($mode & 0x800) {
            $owner['execute'] = ($owner['execute'] == 'x') ? 's' : 'S';
        }
        if ($mode & 0x400) {
            $group['execute'] = ($group['execute'] == 'x') ? 's' : 'S';
        }
        if ($mode & 0x200) {
            $world['execute'] = ($world['execute'] == 'x') ? 't' : 'T';
        }

        $s  = sprintf('%1s', $type);
        $s .= sprintf('%1s%1s%1s',      $owner['read'], $owner['write'], $owner['execute']);
        $s .= sprintf('%1s%1s%1s',      $group['read'], $group['write'], $group['execute']);
        $s .= sprintf('%1s%1s%1s'."\n", $world['read'], $world['write'], $world['execute']);

        return $s;
    }


    // Converts the file type
    public static function get_flag_type($typflag) {

        static $flag_types = array (
            '0' => 'LF_NORMAL',
            '1' => 'LF_LINK',
            '2' => 'LF_SYNLINK',
            '3' => 'LF_CHR',
            '4' => 'LF_BLK',
            '5' => 'LF_DIR',
            '6' => 'LF_FIFO',
            '7' => 'LF_CONFIG',
            'D' => 'LF_DUMPDIR',
            'K' => 'LF_LONGLINK',
            'L' => 'LF_LONGNAME',
            'M' => 'LF_MULTIVOL',
            'N' => 'LF_NAMES',
            'S' => 'LF_SPARSE',
            'V' => 'LF_VOLHDR'
        );

        return @$flag_types[$typflag];
    }
    
}

?>