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
// | dependencies: NONE                                                   |
// +----------------------------------------------------------------------+
// | FLV module by Seth Kaufman <sethØwhirl-i.gig*com>                    |
// |                                                                      |
// | * version 0.1 (26 June 2005)                                         |
// |                                                                      |
// | minor modifications by James Heinrich <infoØgetid3*org>              |
// | * version 0.1.1 (15 July 2005)                                       |
// |                                                                      |
// | Support for On2 VP6 codec and meta information by                    |
// | Steve Webster <steve.websterØfeaturecreep*com>                       |
// | * version 0.2 (22 February 2006)                                     |
// |                                                                      |
// | Modified to not read entire file into memory                         |
// | by James Heinrich <infoØgetid3*org>                                  |
// | * version 0.3 (15 June 2006)                                         |
// |                                                                      |
// | Modifications by Allan Hansen <ahØartemis*dk>                        |
// | Adapted module for PHP5 and getID3 2.0.0.                            |
// +----------------------------------------------------------------------+
//
// $Id: module.audio-video.flv.php,v 1.7 2006/11/10 11:20:12 ah Exp $



class getid3_flv extends getid3_handler
{
    
    const TAG_AUDIO    =  8;
    const TAG_VIDEO    =  9;
    const TAG_META     = 18;

    const VIDEO_H263   = 2;
    const VIDEO_SCREEN = 3;
    const VIDEO_VP6    = 4;

    
	public function Analyze()
	{
	    $info = &$this->getid3->info;
	    
	    $info['flv'] = array ();
	    $info_flv = &$info['flv'];
	    
		fseek($this->getid3->fp, $info['avdataoffset'], SEEK_SET);

		$flv_data_length = $info['avdataend'] - $info['avdataoffset'];
		$flv_header = fread($this->getid3->fp, 5);

		$info['fileformat'] = 'flv';
		$info_flv['header']['signature'] =                           substr($flv_header, 0, 3);
		$info_flv['header']['version']   = getid3_lib::BigEndian2Int(substr($flv_header, 3, 1));
		$type_flags                      = getid3_lib::BigEndian2Int(substr($flv_header, 4, 1));

		$info_flv['header']['hasAudio'] = (bool) ($type_flags & 0x04);
		$info_flv['header']['hasVideo'] = (bool) ($type_flags & 0x01);

		$frame_size_data_length = getid3_lib::BigEndian2Int(fread($this->getid3->fp, 4));
		$flv_header_frame_length = 9;
		if ($frame_size_data_length > $flv_header_frame_length) {
			fseek($this->getid3->fp, $frame_size_data_length - $flv_header_frame_length, SEEK_CUR);
		}

		$duration = 0;
		while ((ftell($this->getid3->fp) + 1) < $info['avdataend']) {
			
			$this_tag_header = fread($this->getid3->fp, 16);

			$previous_tag_length = getid3_lib::BigEndian2Int(substr($this_tag_header,  0, 4));
			$tag_type            = getid3_lib::BigEndian2Int(substr($this_tag_header,  4, 1));
			$data_length         = getid3_lib::BigEndian2Int(substr($this_tag_header,  5, 3));
			$timestamp           = getid3_lib::BigEndian2Int(substr($this_tag_header,  8, 3));
			$last_header_byte    = getid3_lib::BigEndian2Int(substr($this_tag_header, 15, 1));
			$next_offset         = ftell($this->getid3->fp) - 1 + $data_length;

			switch ($tag_type) {
				
				case getid3_flv::TAG_AUDIO:
					if (!isset($info_flv['audio']['audioFormat'])) {
						$info_flv['audio']['audioFormat']     =  $last_header_byte & 0x07;
						$info_flv['audio']['audioRate']       = ($last_header_byte & 0x30) / 0x10;
						$info_flv['audio']['audioSampleSize'] = ($last_header_byte & 0x40) / 0x40;
						$info_flv['audio']['audioType']       = ($last_header_byte & 0x80) / 0x80;
					}
					break;


				case getid3_flv::TAG_VIDEO:
					if (!isset($info_flv['video']['videoCodec'])) {
						$info_flv['video']['videoCodec'] = $last_header_byte & 0x07;

						$flv_video_header = fread($this->getid3->fp, 11);

						if ($info_flv['video']['videoCodec'] != getid3_flv::VIDEO_VP6) {

							$picture_size_type = (getid3_lib::BigEndian2Int(substr($flv_video_header, 3, 2))) >> 7;
							$picture_size_type = $picture_size_type & 0x0007;
							$info_flv['header']['videoSizeType'] = $picture_size_type;
							
							switch ($picture_size_type) {
								case 0:
									$picture_size_enc = getid3_lib::BigEndian2Int(substr($flv_video_header, 5, 2));
									$picture_size_enc <<= 1;
									$info['video']['resolution_x'] = ($picture_size_enc & 0xFF00) >> 8;
									$picture_size_enc = getid3_lib::BigEndian2Int(substr($flv_video_header, 6, 2));
									$picture_size_enc <<= 1;
									$info['video']['resolution_y'] = ($picture_size_enc & 0xFF00) >> 8;
									break;

								case 1:
									$picture_size_enc = getid3_lib::BigEndian2Int(substr($flv_video_header, 5, 4));
									$picture_size_enc <<= 1;
									$info['video']['resolution_x'] = ($picture_size_enc & 0xFFFF0000) >> 16;

									$picture_size_enc = getid3_lib::BigEndian2Int(substr($flv_video_header, 7, 4));
									$picture_size_enc <<= 1;
									$info['video']['resolution_y'] = ($picture_size_enc & 0xFFFF0000) >> 16;
									break;

								case 2:
									$info['video']['resolution_x'] = 352;
									$info['video']['resolution_y'] = 288;
									break;

								case 3:
									$info['video']['resolution_x'] = 176;
									$info['video']['resolution_y'] = 144;
									break;

								case 4:
									$info['video']['resolution_x'] = 128;
									$info['video']['resolution_y'] = 96;
									break;

								case 5:
									$info['video']['resolution_x'] = 320;
									$info['video']['resolution_y'] = 240;
									break;

								case 6:
									$info['video']['resolution_x'] = 160;
									$info['video']['resolution_y'] = 120;
									break;

								default:
									$info['video']['resolution_x'] = 0;
									$info['video']['resolution_y'] = 0;
									break;
							}
						}
					}
					break;


				// Meta tag
				case getid3_flv::TAG_META:

					fseek($this->getid3->fp, -1, SEEK_CUR);
					$reader = new AMFReader(new AMFStream(fread($this->getid3->fp, $data_length)));
					$event_name = $reader->readData();
					$info['meta'][$event_name] = $reader->readData();
					unset($reader);

					$info['video']['frame_rate']   = @$info['meta']['onMetaData']['framerate'];
					$info['video']['resolution_x'] = @$info['meta']['onMetaData']['width'];
					$info['video']['resolution_y'] = @$info['meta']['onMetaData']['height'];
					break;

				default:
					// noop
					break;
			}

			if ($timestamp > $duration) {
				$duration = $timestamp;
			}

			fseek($this->getid3->fp, $next_offset, SEEK_SET);
		}

		if ($info['playtime_seconds'] = $duration / 1000) {
		    $info['bitrate'] = ($info['avdataend'] - $info['avdataoffset']) / $info['playtime_seconds'];
		}

		if ($info_flv['header']['hasAudio']) {
			$info['audio']['codec']           = $this->FLVaudioFormat($info_flv['audio']['audioFormat']);
			$info['audio']['sample_rate']     = $this->FLVaudioRate($info_flv['audio']['audioRate']);
			$info['audio']['bits_per_sample'] = $this->FLVaudioBitDepth($info_flv['audio']['audioSampleSize']);

			$info['audio']['channels']   = $info_flv['audio']['audioType'] + 1; // 0=mono,1=stereo
			$info['audio']['lossless']   = ($info_flv['audio']['audioFormat'] ? false : true); // 0=uncompressed
			$info['audio']['dataformat'] = 'flv';
		}
		if (@$info_flv['header']['hasVideo']) {
			$info['video']['codec']      = $this->FLVvideoCodec($info_flv['video']['videoCodec']);
			$info['video']['dataformat'] = 'flv';
			$info['video']['lossless']   = false;
		}

		return true;
	}


	public static function FLVaudioFormat($id) {
	    
		static $lookup = array(
			0 => 'uncompressed',
			1 => 'ADPCM',
			2 => 'mp3',
			5 => 'Nellymoser 8kHz mono',
			6 => 'Nellymoser',
		);
		return (@$lookup[$id] ? @$lookup[$id] : false);
	}


	public static function FLVaudioRate($id) {
	    
		static $lookup = array(
			0 =>  5500,
			1 => 11025,
			2 => 22050,
			3 => 44100,
		);
		return (@$lookup[$id] ? @$lookup[$id] : false);
	}


	public static function FLVaudioBitDepth($id) {
	    
		static $lookup = array(
			0 =>  8,
			1 => 16,
		);
		return (@$lookup[$id] ? @$lookup[$id] : false);
	}


	public static function FLVvideoCodec($id) {
	    
		static $lookup = array(
			getid3_flv::VIDEO_H263   => 'Sorenson H.263',
			getid3_flv::VIDEO_SCREEN => 'Screen video',
			getid3_flv::VIDEO_VP6    => 'On2 VP6',
		);
		return (@$lookup[$id] ? @$lookup[$id] : false);
	}
}



class AMFStream 
{
	public $bytes;
	public $pos;


	public function AMFStream($bytes) {
	    
		$this->bytes = $bytes;
		$this->pos = 0;
	}


	public function readByte() {
	    
		return getid3_lib::BigEndian2Int(substr($this->bytes, $this->pos++, 1));
	}


	public function readInt() {
	    
		return ($this->readByte() << 8) + $this->readByte();
	}


	public function readLong() {
	    
		return ($this->readByte() << 24) + ($this->readByte() << 16) + ($this->readByte() << 8) + $this->readByte();
	}


	public function readDouble() {
	    
		return getid3_lib::BigEndian2Float($this->read(8));
	}


	public function readUTF() {
	    
		$length = $this->readInt();
		return $this->read($length);
	}


	public function readLongUTF() {
	    
		$length = $this->readLong();
		return $this->read($length);
	}


	public function read($length) {

		$val = substr($this->bytes, $this->pos, $length);
		$this->pos += $length;
		return $val;
	}

	
	public function peekByte() {
	    
		$pos = $this->pos;
		$val = $this->readByte();
		$this->pos = $pos;
		return $val;
	}


	public function peekInt() {

		$pos = $this->pos;
		$val = $this->readInt();
		$this->pos = $pos;
		return $val;
	}


	public function peekLong() {
	    
		$pos = $this->pos;
		$val = $this->readLong();
		$this->pos = $pos;
		return $val;
	}


	public function peekDouble() {  
	    
		$pos = $this->pos;
		$val = $this->readDouble();
		$this->pos = $pos;
		return $val;
	}


	public function peekUTF() {
	    
		$pos = $this->pos;
		$val = $this->readUTF();
		$this->pos = $pos;
		return $val;
	}


	public function peekLongUTF() {
	    
		$pos = $this->pos;
		$val = $this->readLongUTF();
		$this->pos = $pos;
		return $val;
	}
}



class AMFReader 
{
	public $stream;

	public function __construct($stream) {
	    
		$this->stream = $stream;
	}


	public function readData() {
	    
		$value = null;

		$type = $this->stream->readByte();

		switch($type) {
			// Double
			case 0:
				$value = $this->readDouble();
			break;

			// Boolean
			case 1:
				$value = $this->readBoolean();
				break;

			// String
			case 2:
				$value = $this->readString();
				break;

			// Object
			case 3:
				$value = $this->readObject();
				break;

			// null
			case 6:
				return null;
				break;

			// Mixed array
			case 8:
				$value = $this->readMixedArray();
				break;

			// Array
			case 10:
				$value = $this->readArray();
				break;

			// Date
			case 11:
				$value = $this->readDate();
				break;

			// Long string
			case 13:
				$value = $this->readLongString();
				break;

			// XML (handled as string)
			case 15:
				$value = $this->readXML();
				break;

			// Typed object (handled as object)
			case 16:
				$value = $this->readTypedObject();
				break;

			// Long string
			default:
				$value = '(unknown or unsupported data type)';
			break;
		}

		return $value;
	}


	public function readDouble() {    
	    
		return $this->stream->readDouble();
	}


	public function readBoolean() {
	    
		return $this->stream->readByte() == 1;
	}
           
           
	public function readString() {
	    
		return $this->stream->readUTF();
	}


	public function readObject() {
	    
		// Get highest numerical index - ignored
		$highestIndex = $this->stream->readLong();

		$data = array();

		while ($key = $this->stream->readUTF()) {
			// Mixed array record ends with empty string (0x00 0x00) and 0x09
			if (($key == '') && ($this->stream->peekByte() == 0x09)) {
				// Consume byte
				$this->stream->readByte();
				break;
			}

			$data[$key] = $this->readData();
		}

		return $data;
	}


	public function readMixedArray() {
	    
		// Get highest numerical index - ignored
		$highestIndex = $this->stream->readLong();

		$data = array();

		while ($key = $this->stream->readUTF()) {
			// Mixed array record ends with empty string (0x00 0x00) and 0x09
			if (($key == '') && ($this->stream->peekByte() == 0x09)) {
				// Consume byte
				$this->stream->readByte();
				break;
			}

			if (is_numeric($key)) {
				$key = (float) $key;
			}

			$data[$key] = $this->readData();
		}

		return $data;
	}


	public function readArray() {
	    
		$length = $this->stream->readLong();

		$data = array();

		for ($i = 0; $i < count($length); $i++) {
			$data[] = $this->readData();
		}

		return $data;
	}


	public function readDate() {
	    
		$timestamp = $this->stream->readDouble();
		$timezone = $this->stream->readInt();
		return $timestamp;
	}


	public function readLongString() {
	    
		return $this->stream->readLongUTF();
	}


	public function readXML() {
	    
		return $this->stream->readLongUTF();
	}


	public function readTypedObject() {  
	    
		$className = $this->stream->readUTF();
		return $this->readObject();
	}
}

?>