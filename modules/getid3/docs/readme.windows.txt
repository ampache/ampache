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
// | List of binary files required under Windows for some features and/or |
// | file formats.                                                        |
// +----------------------------------------------------------------------+
//
// $Id: readme.windows.txt,v 1.1 2006/12/03 21:12:11 ah Exp $



Windows users may want to download the latest version of the 
"getID3()-WindowsSupport" package and extract it to c:\windows\system32
or another directory in the system path.

The package is required for these features:

    * Shorten support.
    * md5_data/sha1_data of Ogg Vorbis files
    
The package will also greatly speed up calculation of md5_data for other
files.



Included files:
=====================================================

Taken from http://www.cygwin.com/
* cygwin1.dll

Taken from http://unxutils.sourceforge.net/
* head.exe
* md5sum.exe
* tail.exe

Taken from http://ebible.org/mpj/software.htm
* sha1sum.exe

Taken from http://www.vorbis.com/download.psp
* vorbiscomment.exe

Taken from http://flac.sourceforge.net/download.html
* metaflac.exe

Taken from http://www.etree.org/shncom.html
* shorten.exe
