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
// | module.graphic.bmp.php                                               |
// | Module for analyzing BMP graphic files.                              |
// | dependencies: NONE                                                   |
// +----------------------------------------------------------------------+
//
// $Id: module.graphic.bmp.php,v 1.4 2006/11/02 10:48:02 ah Exp $



class getid3_bmp extends getid3_handler
{


    public function Analyze() {

        $getid3 = $this->getid3;

        // BITMAPFILEHEADER [14 bytes] - http://msdn.microsoft.com/library/en-us/gdi/bitmaps_62uq.asp
        // all versions
        // WORD    bfType;
        // DWORD   bfSize;
        // WORD    bfReserved1;
        // WORD    bfReserved2;
        // DWORD   bfOffBits;

        // shortcuts
        $getid3->info['bmp']['header']['raw'] = array ();
        $info_bmp            = &$getid3->info['bmp'];
        $info_bmp_header     = &$info_bmp['header'];
        $info_bmp_header_raw = &$info_bmp_header['raw'];

        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);
        $bmp_header = fread($getid3->fp, 14 + 40);

        // Magic bytes
        $info_bmp_header_raw['identifier'] = 'BM';

        getid3_lib::ReadSequence('LittleEndian2Int', $info_bmp_header_raw, $bmp_header, 2,
            array (
                'filesize'    => 4,
                'reserved1'   => 2,
                'reserved2'   => 2,
                'data_offset' => 4,
                'header_size' => 4
            )
        );

        // Check if the hardcoded-to-1 "planes" is at offset 22 or 26
        $planes22 = getid3_lib::LittleEndian2Int(substr($bmp_header, 22, 2));
        $planes26 = getid3_lib::LittleEndian2Int(substr($bmp_header, 26, 2));
        if (($planes22 == 1) && ($planes26 != 1)) {
            $info_bmp['type_os']      = 'OS/2';
            $info_bmp['type_version'] = 1;
        }
        elseif (($planes26 == 1) && ($planes22 != 1)) {
            $info_bmp['type_os']      = 'Windows';
            $info_bmp['type_version'] = 1;
        }
        elseif ($info_bmp_header_raw['header_size'] == 12) {
            $info_bmp['type_os']      = 'OS/2';
            $info_bmp['type_version'] = 1;
        }
        elseif ($info_bmp_header_raw['header_size'] == 40) {
            $info_bmp['type_os']      = 'Windows';
            $info_bmp['type_version'] = 1;
        }
        elseif ($info_bmp_header_raw['header_size'] == 84) {
            $info_bmp['type_os']      = 'Windows';
            $info_bmp['type_version'] = 4;
        }
        elseif ($info_bmp_header_raw['header_size'] == 100) {
            $info_bmp['type_os']      = 'Windows';
            $info_bmp['type_version'] = 5;
        }
        else {
            throw new getid3_exception('Unknown BMP subtype (or not a BMP file)');
        }

        $getid3->info['fileformat']                  = 'bmp';
        $getid3->info['video']['dataformat']         = 'bmp';
        $getid3->info['video']['lossless']           = true;
        $getid3->info['video']['pixel_aspect_ratio'] = (float)1;

        if ($info_bmp['type_os'] == 'OS/2') {

            // OS/2-format BMP
            // http://netghost.narod.ru/gff/graphics/summary/os2bmp.htm

            // DWORD  Size;             /* Size of this structure in bytes */
            // DWORD  Width;            /* Bitmap width in pixels */
            // DWORD  Height;           /* Bitmap height in pixel */
            // WORD   NumPlanes;        /* Number of bit planes (color depth) */
            // WORD   BitsPerPixel;     /* Number of bits per pixel per plane */

            getid3_lib::ReadSequence('LittleEndian2Int', $info_bmp_header_raw, $bmp_header, 18,
                array (
                    'width'          => 2,
                    'height'         => 2,
                    'planes'         => 2,
                    'bits_per_pixel' => 2
                )
            );

            $getid3->info['video']['resolution_x']    = $info_bmp_header_raw['width'];
            $getid3->info['video']['resolution_y']    = $info_bmp_header_raw['height'];
            $getid3->info['video']['codec']           = 'BI_RGB '.$info_bmp_header_raw['bits_per_pixel'].'-bit';
            $getid3->info['video']['bits_per_sample'] = $info_bmp_header_raw['bits_per_pixel'];

            if ($info_bmp['type_version'] >= 2) {
                // DWORD  Compression;      /* Bitmap compression scheme */
                // DWORD  ImageDataSize;    /* Size of bitmap data in bytes */
                // DWORD  XResolution;      /* X resolution of display device */
                // DWORD  YResolution;      /* Y resolution of display device */
                // DWORD  ColorsUsed;       /* Number of color table indices used */
                // DWORD  ColorsImportant;  /* Number of important color indices */
                // WORD   Units;            /* Type of units used to measure resolution */
                // WORD   Reserved;         /* Pad structure to 4-byte boundary */
                // WORD   Recording;        /* Recording algorithm */
                // WORD   Rendering;        /* Halftoning algorithm used */
                // DWORD  Size1;            /* Reserved for halftoning algorithm use */
                // DWORD  Size2;            /* Reserved for halftoning algorithm use */
                // DWORD  ColorEncoding;    /* Color model used in bitmap */
                // DWORD  Identifier;       /* Reserved for application use */

                getid3_lib::ReadSequence('LittleEndian2Int', $info_bmp_header_raw, $bmp_header, 26,
                    array (
                        'compression'      => 4,
                        'bmp_data_size'    => 4,
                        'resolution_h'     => 4,
                        'resolution_v'     => 4,
                        'colors_used'      => 4,
                        'colors_important' => 4,
                        'resolution_units' => 2,
                        'reserved1'        => 2,
                        'recording'        => 2,
                        'rendering'        => 2,
                        'size1'            => 4,
                        'size2'            => 4,
                        'color_encoding'   => 4,
                        'identifier'       => 4
                    )
                );

                $info_bmp_header['compression'] = getid3_bmp::BMPcompressionOS2Lookup($info_bmp_header_raw['compression']);
                $getid3->info['video']['codec'] = $info_bmp_header['compression'].' '.$info_bmp_header_raw['bits_per_pixel'].'-bit';
            }

            return true;
        }


        if ($info_bmp['type_os'] == 'Windows') {

            // Windows-format BMP

            // BITMAPINFOHEADER - [40 bytes] http://msdn.microsoft.com/library/en-us/gdi/bitmaps_1rw2.asp
            // all versions
            // DWORD  biSize;
            // LONG   biWidth;
            // LONG   biHeight;
            // WORD   biPlanes;
            // WORD   biBitCount;
            // DWORD  biCompression;
            // DWORD  biSizeImage;
            // LONG   biXPelsPerMeter;
            // LONG   biYPelsPerMeter;
            // DWORD  biClrUsed;
            // DWORD  biClrImportant;

            getid3_lib::ReadSequence('LittleEndian2Int', $info_bmp_header_raw, $bmp_header, 18,
                array (
                    'width'            => -4,        //signed
                    'height'           => -4,        //signed
                    'planes'           => 2,
                    'bits_per_pixel'   => 2,
                    'compression'      => 4,
                    'bmp_data_size'    => 4,
                    'resolution_h'     => -4,        //signed
                    'resolution_v'     => -4,        //signed
                    'colors_used'      => 4,
                    'colors_important' => 4
                )
            );
            foreach (array ('width', 'height', 'resolution_h', 'resolution_v') as $key) {
                $info_bmp_header_raw[$key] = getid3_lib::LittleEndian2Int($info_bmp_header_raw[$key], true);
            }

            $info_bmp_header['compression']           = getid3_bmp::BMPcompressionWindowsLookup($info_bmp_header_raw['compression']);
            $getid3->info['video']['resolution_x']    = $info_bmp_header_raw['width'];
            $getid3->info['video']['resolution_y']    = $info_bmp_header_raw['height'];
            $getid3->info['video']['codec']           = $info_bmp_header['compression'].' '.$info_bmp_header_raw['bits_per_pixel'].'-bit';
            $getid3->info['video']['bits_per_sample'] = $info_bmp_header_raw['bits_per_pixel'];

            // should only be v4+, but BMPs with type_version==1 and BI_BITFIELDS compression have been seen
            if (($info_bmp['type_version'] >= 4) || ($info_bmp_header_raw['compression'] == 3)) {


                $bmp_header .= fread($getid3->fp, 44);

                // BITMAPV4HEADER - [44 bytes] - http://msdn.microsoft.com/library/en-us/gdi/bitmaps_2k1e.asp
                // Win95+, WinNT4.0+
                // DWORD        bV4RedMask;
                // DWORD        bV4GreenMask;
                // DWORD        bV4BlueMask;
                // DWORD        bV4AlphaMask;
                // DWORD        bV4CSType;
                // CIEXYZTRIPLE bV4Endpoints;
                // DWORD        bV4GammaRed;
                // DWORD        bV4GammaGreen;
                // DWORD        bV4GammaBlue;

                getid3_lib::ReadSequence('LittleEndian2Int', $info_bmp_header_raw, $bmp_header, 54,
                    array (
                        'red_mask'     => 4,
                        'green_mask'   => 4,
                        'blue_mask'    => 4,
                        'alpha_mask'   => 4,
                        'cs_type'      => 4,
                        'ciexyz_red'   => -4,       //string
                        'ciexyz_green' => -4,       //string
                        'ciexyz_blue'  => -4,       //string
                        'gamma_red'    => 4,
                        'gamma_green'  => 4,
                        'gamma_blue'   => 4
                    )
                );

                $info_bmp_header['ciexyz_red']   = getid3_bmp::FixedPoint2_30(strrev($info_bmp_header_raw['ciexyz_red']));
                $info_bmp_header['ciexyz_green'] = getid3_bmp::FixedPoint2_30(strrev($info_bmp_header_raw['ciexyz_green']));
                $info_bmp_header['ciexyz_blue']  = getid3_bmp::FixedPoint2_30(strrev($info_bmp_header_raw['ciexyz_blue']));


                if ($info_bmp['type_version'] >= 5) {
                    $bmp_header .= fread($getid3->fp, 16);

                    // BITMAPV5HEADER - [16 bytes] - http://msdn.microsoft.com/library/en-us/gdi/bitmaps_7c36.asp
                    // Win98+, Win2000+
                    // DWORD        bV5Intent;
                    // DWORD        bV5ProfileData;
                    // DWORD        bV5ProfileSize;
                    // DWORD        bV5Reserved;

                    getid3_lib::ReadSequence('LittleEndian2Int', $info_bmp_header_raw, $bmp_header, 98,
                        array (
                            'intent'              => 4,
                            'profile_data_offset' => 4,
                            'profile_data_size'   => 4,
                            'reserved3'           => 4
                        )
                    );

                }
            }

            return true;
        }


        throw new getid3_exception('Unknown BMP format in header.');

    }



    public static function BMPcompressionWindowsLookup($compression_id) {

        static $lookup = array (
            0 => 'BI_RGB',
            1 => 'BI_RLE8',
            2 => 'BI_RLE4',
            3 => 'BI_BITFIELDS',
            4 => 'BI_JPEG',
            5 => 'BI_PNG'
        );
        return (isset($lookup[$compression_id]) ? $lookup[$compression_id] : 'invalid');
    }



    public static function BMPcompressionOS2Lookup($compression_id) {

        static $lookup = array (
            0 => 'BI_RGB',
            1 => 'BI_RLE8',
            2 => 'BI_RLE4',
            3 => 'Huffman 1D',
            4 => 'BI_RLE24',
        );
        return (isset($lookup[$compression_id]) ? $lookup[$compression_id] : 'invalid');
    }


    public static function FixedPoint2_30($raw_data) {

        $binary_string = getid3_lib::BigEndian2Bin($raw_data);
        return bindec(substr($binary_string, 0, 2)) + (float)(bindec(substr($binary_string, 2, 30)) / 1073741824);        // pow(2, 30) = 1073741824
    }


}


?>