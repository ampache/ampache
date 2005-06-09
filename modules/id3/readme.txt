/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                        ///
/////////////////////////////////////////////////////////////////

        This code is released under the GNU GPL:
          http://www.gnu.org/copyleft/gpl.html

     +---------------------------------------------+
     | If you do use this code somewhere, send me  |
     | an email and tell me how/where you used it. |
     |                                             |
     | If you want to donate, there is a link on   |
     | http://www.getid3.org for PayPal donations. |
     +---------------------------------------------+

Quick Start
===========

Q: How can I check that getID3() works on my server/files?
A: Unzip getID3() to a directory, then access /demos/demo.browse.php


Mailing List
============

It's highly recommended that you sign up for the getID3()-Announce
mailing list to be notified when new versions are released, as they
may contain important bugfixes (as well as new features, of course).
Sign up for the mailing list from http://getid3.sourceforge.net


What does getID3() do?
======================

Reads & parses (to varying degrees):
 ¤ tags:
  * APE (v1 and v2)
  * ID3v1 (& ID3v1.1)
  * ID3v2 (v2.4, v2.3, v2.2)
  * Lyrics3 (v1 & v2)

 ¤ audio-lossy:
  * MP3/MP2/MP1
  * MPC / Musepack
  * Ogg (Vorbis, OggFLAC, Speex)
  * RealAudio
  * Speex
  * VQF

 ¤ audio-lossless:
  * AIFF
  * AU
  * Bonk
  * CD-audio (*.cda)
  * FLAC
  * LA (Lossless Audio)
  * LPAC
  * MIDI
  * Monkey's Audio
  * OptimFROG
  * RKAU
  * VOC
  * WAV (RIFF)
  * WavPack

 ¤ audio-video:
  * ASF: ASF, Windows Media Audio (WMA), Windows Media Video (WMV)
  * AVI (RIFF)
  * Flash
  * MPEG-1 / MPEG-2
  * NSV (Nullsoft Streaming Video)
  * Quicktime
  * RealVideo

 ¤ still image:
  * BMP
  * GIF
  * JPEG
  * PNG

 ¤ data:
  * ISO-9660 CD-ROM image (directory structure)
  * SZIP (limited support)
  * ZIP (directory structure)


Writes:
  * ID3v1 (& ID3v1.1)
  * ID3v2 (v2.3 & v2.4)
  * VorbisComment on OggVorbis
  * VorbisComment on FLAC (not OggFLAC)
  * APE v2
  * Lyrics3 (delete only)


Requirements
============

* PHP 4.1.0 (or higher)


Usage
=====

require_once('/path/getid3.php');
$getID3 = new getID3;
$fileinfo = $getID3->analyze($filename);

For an example of a complete directory-browsing, file-scanning
implementation of getID3(), please run /demos/demo.browse.php

See /demos/demo.basic.php for a very basic use of getID3() with no
fancy output, just scanning one file.

See /demos/scandir.php for a sample recursive scanning code that
scans every file in a given directory, and all sub-directories

See /demos/simple.php for a simple example script that scans all
files in one directory and output artist, title, bitrate and playtime

See /demos/mimeonly.php for a simple example script that scans a
single file and returns only the MIME information

To analyze remote files over HTTP or FTP you need to copy the file
locally first before running getID3(). Your code would look something
like this:

// Copy remote file locally to scan with getID3()
$remotefilename = 'http://www.example.com/filename.mp3';
if ($fp_remote = fopen($remotefilename, 'rb')) {
    $localtempfilename = tempnam('/tmp', 'getID3');
    if ($fp_local = fopen($localtempfilename, 'wb')) {
        while ($buffer = fread($fp_remote, 8192)) {
            fwrite($fp_local, $buffer);
        }
        fclose($fp_local);

		// Initialize getID3 engine
		$getID3 = new getID3;

		$ThisFileInfo = $getID3->analyze($filename);

        // Delete temporary file
        unlink($localtempfilename);
    }
    fclose($fp_remote);
}


// Writing tags:
require_once('getid3.php');
$getID3 = new getID3;
getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'write.php', __FILE__);
$tagwriter = new getid3_writetags;
$tagwriter->filename   = $Filename;
$tagwriter->tagformats = array('id3v2.3', 'ape');
$TagData['title'][]  = 'Song Title';
$TagData['artist'][] = 'Artist Name';
$tagwriter->tag_data = array(;
if ($tagwriter->WriteTags()) {
	echo 'success';
} else {
	echo 'failure';
}



What does the returned data structure look like?
================================================

See getid3.structure.txt

It is recommended that you look at the output of
/demos/demo.browse.php scanning the file(s) you're interested in to
confirm what data is actually returned for any particular filetype in
general, and your files in particular, as the actual data returned
may vary considerably depending on what information is available in
the file itself.


Notes
=====

If the format parser encounters a critical problem, it will return
something in $fileinfo['error'], describing the encountered error. If
a less critical error or notice is generated it will appear in
$fileinfo['warning']. Both keys may contain more than one warning or
error. If something is returned in ['error'] then the file was not
correctly parsed and returned data may or may not be correct and/or
complete. If something is returned in ['warning'] (and not ['error'])
then the data that is returned is OK - usually getID3() is reporting
errors in the file that have been worked around due to known bugs in
other programs. Some warnings may indicate that the data that is
returned is OK but that some data could not be extracted due to
errors in the file.


Known Bugs/Issues
=================

See the end of getid3.changelog.txt for notes on known issues with
getID3(), encoders, players, etc.


Disclaimer
==========

getID3() has been tested on many systems, on many types of files,
under many operating systems, and is generally believe to be stable
and safe. That being said, there is still the chance there is an
undiscovered and/or unfixed bug that may potentially corrupt your
file, especially within the writing functions. By using getID3() you
agree that it's not my fault if any of your files are corrupted.
In fact, I'm not liable for anything :)


/////////////////////////////////////////////////////////////////////

GNU General Public License - see license.txt

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to:
Free Software Foundation, Inc.
59 Temple Place - Suite 330
Boston, MA  02111-1307, USA.


/////////////////////////////////////////////////////////////////////

Reference material:
[www.id3.org material now mirrored at http://id3lib.sourceforge.net/id3/]
* http://www.id3.org/id3v2.4.0-structure.txt
* http://www.id3.org/id3v2.4.0-frames.txt
* http://www.id3.org/id3v2.4.0-changes.txt
* http://www.id3.org/id3v2.3.0.txt
* http://www.id3.org/id3v2-00.txt
* http://www.id3.org/mp3frame.html
* http://minnie.tuhs.org/pipermail/mp3encoder/2001-January/001800.html <mathewhendry@hotmail.com>
* http://www.dv.co.yu/mpgscript/mpeghdr.htm
* http://www.mp3-tech.org/programmer/frame_header.html
* http://users.belgacom.net/gc247244/extra/tag.html
* http://gabriel.mp3-tech.org/mp3infotag.html
* http://www.id3.org/iso4217.html
* http://www.unicode.org/Public/MAPPINGS/ISO8859/8859-1.TXT
* http://www.xiph.org/ogg/vorbis/doc/framing.html
* http://www.xiph.org/ogg/vorbis/doc/v-comment.html
* http://leknor.com/code/php/class.ogg.php.txt
* http://www.id3.org/iso639-2.html
* http://www.id3.org/lyrics3.html
* http://www.id3.org/lyrics3200.html
* http://www.psc.edu/general/software/packages/ieee/ieee.html
* http://www.scri.fsu.edu/~jac/MAD3401/Backgrnd/ieee-expl.html
* http://www.scri.fsu.edu/~jac/MAD3401/Backgrnd/binary.html
* http://www.jmcgowan.com/avi.html
* http://www.wotsit.org/
* http://www.herdsoft.com/ti/davincie/davp3xo2.htm
* http://www.mathdogs.com/vorbis-illuminated/bitstream-appendix.html
* "Standard MIDI File Format" by Dustin Caldwell (from www.wotsit.org)
* http://midistudio.com/Help/GMSpecs_Patches.htm
* http://www.xiph.org/archives/vorbis/200109/0459.html
* http://www.replaygain.org/
* http://www.lossless-audio.com/
* http://download.microsoft.com/download/winmediatech40/Doc/1.0/WIN98MeXP/EN-US/ASF_Specification_v.1.0.exe
* http://mediaxw.sourceforge.net/files/doc/Active%20Streaming%20Format%20(ASF)%201.0%20Specification.pdf
* http://www.uni-jena.de/~pfk/mpp/sv8/
* http://jfaul.de/atl/
* http://www.uni-jena.de/~pfk/mpp/
* http://www.libpng.org/pub/png/spec/png-1.2-pdg.html
* http://www.real.com/devzone/library/creating/rmsdk/doc/rmff.htm
* http://www.fastgraph.com/help/bmp_os2_header_format.html
* http://netghost.narod.ru/gff/graphics/summary/os2bmp.htm
* http://flac.sourceforge.net/format.html
* http://www.research.att.com/projects/mpegaudio/mpeg2.html
* http://www.audiocoding.com/wiki/index.php?page=AAC
* http://libmpeg.org/mpeg4/doc/w2203tfs.pdf
* http://www.geocities.com/xhelmboyx/quicktime/formats/qtm-layout.txt
* http://developer.apple.com/techpubs/quicktime/qtdevdocs/RM/frameset.htm
* http://www.nullsoft.com/nsv/
* http://www.wotsit.org/download.asp?f=iso9660
* http://sandbox.mc.edu/~bennet/cs110/tc/tctod.html
* http://www.cdroller.com/htm/readdata.html
* http://www.speex.org/manual/node10.html
* http://www.harmony-central.com/Computer/Programming/aiff-file-format.doc
* http://www.faqs.org/rfcs/rfc2361.html
* http://ghido.shelter.ro/
* http://www.ebu.ch/tech_t3285.pdf
* http://www.sr.se/utveckling/tu/bwf
* http://ftp.aessc.org/pub/aes46-2002.pdf
* http://cartchunk.org:8080/
* http://www.broadcastpapers.com/radio/cartchunk01.htm
* http://www.hr/josip/DSP/AudioFile2.html
* http://home.attbi.com/~chris.bagwell/AudioFormats-11.html
* http://www.pure-mac.com/extkey.html
* http://cesnet.dl.sourceforge.net/sourceforge/bonkenc/bonk-binary-format-0.9.txt
* http://www.headbands.com/gspot/
* http://www.openswf.org/spec/SWFfileformat.html
* http://j-faul.virtualave.net/
* http://www.btinternet.com/~AnthonyJ/Atari/programming/avr_format.html
* http://cui.unige.ch/OSG/info/AudioFormats/ap11.html
* http://sswf.sourceforge.net/SWFalexref.html
* http://www.geocities.com/xhelmboyx/quicktime/formats/qti-layout.txt
* http://www-lehre.informatik.uni-osnabrueck.de/~fbstark/diplom/docs/swf/Flash_Uncovered.htm
* http://developer.apple.com/quicktime/icefloe/dispatch012.html
* http://www.csdn.net/Dev/Format/graphics/PCD.htm
* http://tta.iszf.irk.ru/
* http://www.atsc.org/standards/a_52a.pdf
* http://www.alanwood.net/unicode/
* http://www.freelists.org/archives/matroska-devel/07-2003/msg00010.html
* http://www.its.msstate.edu/net/real/reports/config/tags.stats
* http://homepages.slingshot.co.nz/~helmboy/quicktime/formats/qtm-layout.txt
* http://brennan.young.net/Comp/LiveStage/things.html
* http://www.multiweb.cz/twoinches/MP3inside.htm
* http://www.geocities.co.jp/SiliconValley-Oakland/3664/alittle.html#GenreExtended
* http://www.mactech.com/articles/mactech/Vol.06/06.01/SANENormalized/
* http://www.unicode.org/unicode/faq/utf_bom.html
* http://tta.corecodec.org/?menu=format
* http://www.scvi.net/nsvformat.htm