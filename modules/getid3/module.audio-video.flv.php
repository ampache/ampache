<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
//                                                             //
//  FLV module by Seth Kaufman <seth@whirl-i-gig.com>          //
//                                                             //
//  * version 0.1 (26 June 2005)                               //
//                                                             //
//  minor modifications by James Heinrich <info@getid3.org>    //
//  * version 0.1.1 (15 July 2005)                             //
//                                                             //
//  Support for On2 VP6 codec and meta information             //
//    by Steve Webster <steve.webster@featurecreep.com>        //
//  * version 0.2 (22 February 2006)                           //
//                                                             //
//  Modified to not read entire file into memory               //
//    by James Heinrich <info@getid3.org>                      //
//  * version 0.3 (15 June 2006)                               //
//                                                             //
//  Bugfixes for incorrectly parsed FLV dimensions             //
//    and incorrect parsing of onMetaTag                       //
//    by Evgeny Moysevich <moysevich@gmail.com>                //
//  * version 0.4 (07 December 2007)                           //
//                                                             //
//  Fixed parsing of audio tags and added additional codec     //
//    details. The duration is now read from onMetaTag (if     //
//    exists), rather than parsing whole file                  //
//    by Nigel Barnes <ngbarnes@hotmail.com>                   //
//  * version 0.5 (21 May 2009)                                //
//                                                             //
//  Better parsing of files with h264 video                    //
//    by Evgeny Moysevich <moysevichØgmail*com>                //
//  * version 0.6 (24 May 2009)                                //
//                                                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.audio-video.flv.php                                  //
// module for analyzing Shockwave Flash Video files            //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////

define('GETID3_FLV_TAG_AUDIO',          8);
define('GETID3_FLV_TAG_VIDEO',          9);
define('GETID3_FLV_TAG_META',          18);

define('GETID3_FLV_VIDEO_H263',         2);
define('GETID3_FLV_VIDEO_SCREEN',       3);
define('GETID3_FLV_VIDEO_VP6FLV',       4);
define('GETID3_FLV_VIDEO_VP6FLV_ALPHA', 5);
define('GETID3_FLV_VIDEO_SCREENV2',     6);
define('GETID3_FLV_VIDEO_H264',         7);

define('H264_AVC_SEQUENCE_HEADER',          0);
define('H264_PROFILE_BASELINE',            66);
define('H264_PROFILE_MAIN',                77);
define('H264_PROFILE_EXTENDED',            88);
define('H264_PROFILE_HIGH',               100);
define('H264_PROFILE_HIGH10',             110);
define('H264_PROFILE_HIGH422',            122);
define('H264_PROFILE_HIGH444',            144);
define('H264_PROFILE_HIGH444_PREDICTIVE', 244);

class getid3_flv
{

	function getid3_flv(&$fd, &$ThisFileInfo, $ReturnAllTagData=false) {
//$start_time = microtime(true);
		fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);

		$FLVdataLength = $ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset'];
		$FLVheader = fread($fd, 5);

		$ThisFileInfo['fileformat'] = 'flv';
		$ThisFileInfo['flv']['header']['signature'] =                           substr($FLVheader, 0, 3);
		$ThisFileInfo['flv']['header']['version']   = getid3_lib::BigEndian2Int(substr($FLVheader, 3, 1));
		$TypeFlags                                  = getid3_lib::BigEndian2Int(substr($FLVheader, 4, 1));

		if ($ThisFileInfo['flv']['header']['signature'] != 'FLV') {
			$ThisFileInfo['error'][] = 'Expecting "FLV" at offset '.$ThisFileInfo['avdataoffset'].', found "'.$ThisFileInfo['flv']['header']['signature'].'"';
			unset($ThisFileInfo['flv']);
			unset($ThisFileInfo['fileformat']);
			return false;
		}

		$ThisFileInfo['flv']['header']['hasAudio'] = (bool) ($TypeFlags & 0x04);
		$ThisFileInfo['flv']['header']['hasVideo'] = (bool) ($TypeFlags & 0x01);

		$FrameSizeDataLength = getid3_lib::BigEndian2Int(fread($fd, 4));
		$FLVheaderFrameLength = 9;
		if ($FrameSizeDataLength > $FLVheaderFrameLength) {
			fseek($fd, $FrameSizeDataLength - $FLVheaderFrameLength, SEEK_CUR);
		}
//echo __LINE__.'='.number_format(microtime(true) - $start_time, 3).'<br>';

		$Duration = 0;
		$found_video = false;
		$found_audio = false;
		$found_meta  = false;
		$tagParsed = 0;
		while (((ftell($fd) + 16) < $ThisFileInfo['avdataend']) && ($tagParsed <= 20 || !$found_meta))  {
			$ThisTagHeader = fread($fd, 16);

			$PreviousTagLength = getid3_lib::BigEndian2Int(substr($ThisTagHeader,  0, 4));
			$TagType           = getid3_lib::BigEndian2Int(substr($ThisTagHeader,  4, 1));
			$DataLength        = getid3_lib::BigEndian2Int(substr($ThisTagHeader,  5, 3));
			$Timestamp         = getid3_lib::BigEndian2Int(substr($ThisTagHeader,  8, 3));
			$LastHeaderByte    = getid3_lib::BigEndian2Int(substr($ThisTagHeader, 15, 1));
			$NextOffset = ftell($fd) - 1 + $DataLength;
			if ($Timestamp > $Duration) {
				$Duration = $Timestamp;
			}

//echo __LINE__.'['.ftell($fd).']=('.$TagType.')='.number_format(microtime(true) - $start_time, 3).'<br>';

			switch ($TagType) {
				case GETID3_FLV_TAG_AUDIO:
					if (!$found_audio) {
						$found_audio = true;
						$ThisFileInfo['flv']['audio']['audioFormat']     = ($LastHeaderByte >> 4) & 0x0F;
						$ThisFileInfo['flv']['audio']['audioRate']       = ($LastHeaderByte >> 2) & 0x03;
						$ThisFileInfo['flv']['audio']['audioSampleSize'] = ($LastHeaderByte >> 1) & 0x01;
						$ThisFileInfo['flv']['audio']['audioType']       =  $LastHeaderByte       & 0x01;
					}
					break;

				case GETID3_FLV_TAG_VIDEO:
					if (!$found_video) {
						$found_video = true;
						$ThisFileInfo['flv']['video']['videoCodec'] = $LastHeaderByte & 0x07;

						$FLVvideoHeader = fread($fd, 11);

						if ($ThisFileInfo['flv']['video']['videoCodec'] == GETID3_FLV_VIDEO_H264) {
							// this code block contributed by: moysevichØgmail*com

							$AVCPacketType = getid3_lib::BigEndian2Int(substr($FLVvideoHeader, 0, 1));
							if ($AVCPacketType == H264_AVC_SEQUENCE_HEADER) {
								//	read AVCDecoderConfigurationRecord
								$configurationVersion       = getid3_lib::BigEndian2Int(substr($FLVvideoHeader,  4, 1));
								$AVCProfileIndication       = getid3_lib::BigEndian2Int(substr($FLVvideoHeader,  5, 1));
								$profile_compatibility      = getid3_lib::BigEndian2Int(substr($FLVvideoHeader,  6, 1));
								$lengthSizeMinusOne         = getid3_lib::BigEndian2Int(substr($FLVvideoHeader,  7, 1));
								$numOfSequenceParameterSets = getid3_lib::BigEndian2Int(substr($FLVvideoHeader,  8, 1));

								if (($numOfSequenceParameterSets & 0x1F) != 0) {
									//	there is at least one SequenceParameterSet
									//	read size of the first SequenceParameterSet
									//$spsSize = getid3_lib::BigEndian2Int(substr($FLVvideoHeader, 9, 2));
									$spsSize = getid3_lib::LittleEndian2Int(substr($FLVvideoHeader, 9, 2));
									//	read the first SequenceParameterSet
									$sps = fread($fd, $spsSize);
									if (strlen($sps) == $spsSize) {	//	make sure that whole SequenceParameterSet was red
										$spsReader = new AVCSequenceParameterSetReader($sps);
										$spsReader->readData();
										$ThisFileInfo['video']['resolution_x'] = $spsReader->getWidth();
										$ThisFileInfo['video']['resolution_y'] = $spsReader->getHeight();
									}
								}
							}
							// end: moysevichØgmail*com

						} elseif ($ThisFileInfo['flv']['video']['videoCodec'] == GETID3_FLV_VIDEO_H263) {

							$PictureSizeType = (getid3_lib::BigEndian2Int(substr($FLVvideoHeader, 3, 2))) >> 7;
							$PictureSizeType = $PictureSizeType & 0x0007;
							$ThisFileInfo['flv']['header']['videoSizeType'] = $PictureSizeType;
							switch ($PictureSizeType) {
								case 0:
									//$PictureSizeEnc = getid3_lib::BigEndian2Int(substr($FLVvideoHeader, 5, 2));
									//$PictureSizeEnc <<= 1;
									//$ThisFileInfo['video']['resolution_x'] = ($PictureSizeEnc & 0xFF00) >> 8;
									//$PictureSizeEnc = getid3_lib::BigEndian2Int(substr($FLVvideoHeader, 6, 2));
									//$PictureSizeEnc <<= 1;
									//$ThisFileInfo['video']['resolution_y'] = ($PictureSizeEnc & 0xFF00) >> 8;

									$PictureSizeEnc['x'] = getid3_lib::BigEndian2Int(substr($FLVvideoHeader, 4, 2));
									$PictureSizeEnc['y'] = getid3_lib::BigEndian2Int(substr($FLVvideoHeader, 5, 2));
									$PictureSizeEnc['x'] >>= 7;
									$PictureSizeEnc['y'] >>= 7;
									$ThisFileInfo['video']['resolution_x'] = $PictureSizeEnc['x'] & 0xFF;
									$ThisFileInfo['video']['resolution_y'] = $PictureSizeEnc['y'] & 0xFF;
									break;

								case 1:
									$PictureSizeEnc['x'] = getid3_lib::BigEndian2Int(substr($FLVvideoHeader, 4, 3));
									$PictureSizeEnc['y'] = getid3_lib::BigEndian2Int(substr($FLVvideoHeader, 6, 3));
									$PictureSizeEnc['x'] >>= 7;
									$PictureSizeEnc['y'] >>= 7;
									$ThisFileInfo['video']['resolution_x'] = $PictureSizeEnc['x'] & 0xFFFF;
									$ThisFileInfo['video']['resolution_y'] = $PictureSizeEnc['y'] & 0xFFFF;
									break;

								case 2:
									$ThisFileInfo['video']['resolution_x'] = 352;
									$ThisFileInfo['video']['resolution_y'] = 288;
									break;

								case 3:
									$ThisFileInfo['video']['resolution_x'] = 176;
									$ThisFileInfo['video']['resolution_y'] = 144;
									break;

								case 4:
									$ThisFileInfo['video']['resolution_x'] = 128;
									$ThisFileInfo['video']['resolution_y'] = 96;
									break;

								case 5:
									$ThisFileInfo['video']['resolution_x'] = 320;
									$ThisFileInfo['video']['resolution_y'] = 240;
									break;

								case 6:
									$ThisFileInfo['video']['resolution_x'] = 160;
									$ThisFileInfo['video']['resolution_y'] = 120;
									break;

								default:
									$ThisFileInfo['video']['resolution_x'] = 0;
									$ThisFileInfo['video']['resolution_y'] = 0;
									break;

							}
						}
						$ThisFileInfo['video']['pixel_aspect_ratio'] = $ThisFileInfo['video']['resolution_x'] / $ThisFileInfo['video']['resolution_y'];
					}
					break;

				// Meta tag
				case GETID3_FLV_TAG_META:
					if (!$found_meta) {
						$found_meta = true;
						fseek($fd, -1, SEEK_CUR);
						$datachunk = fread($fd, $DataLength);
						$AMFstream = new AMFStream($datachunk);
						$reader = new AMFReader($AMFstream);
						$eventName = $reader->readData();
						$ThisFileInfo['flv']['meta'][$eventName] = $reader->readData();
						unset($reader);

						$copykeys = array('framerate'=>'frame_rate', 'width'=>'resolution_x', 'height'=>'resolution_y', 'audiodatarate'=>'bitrate', 'videodatarate'=>'bitrate');
						foreach ($copykeys as $sourcekey => $destkey) {
							if (isset($ThisFileInfo['flv']['meta']['onMetaData'][$sourcekey])) {
								switch ($sourcekey) {
									case 'width':
									case 'height':
										$ThisFileInfo['video'][$destkey] = intval(round($ThisFileInfo['flv']['meta']['onMetaData'][$sourcekey]));
										break;
									case 'audiodatarate':
										$ThisFileInfo['audio'][$destkey] = $ThisFileInfo['flv']['meta']['onMetaData'][$sourcekey];
										break;
									case 'videodatarate':
									case 'frame_rate':
									default:
										$ThisFileInfo['video'][$destkey] = $ThisFileInfo['flv']['meta']['onMetaData'][$sourcekey];
										break;
								}
							}
						}
					}
					break;

				default:
					// noop
					break;
			}

			fseek($fd, $NextOffset, SEEK_SET);

			// Increase parsed tag count: break out of loop if more than 20 tags parsed
			$tagParsed++;
		}

		$ThisFileInfo['playtime_seconds'] = $Duration / 1000;
		if ($ThisFileInfo['playtime_seconds'] > 0) {
			$ThisFileInfo['bitrate'] = (($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']) * 8) / $ThisFileInfo['playtime_seconds'];
		}

		if ($ThisFileInfo['flv']['header']['hasAudio']) {
			$ThisFileInfo['audio']['codec']           =   $this->FLVaudioFormat($ThisFileInfo['flv']['audio']['audioFormat']);
			$ThisFileInfo['audio']['sample_rate']     =     $this->FLVaudioRate($ThisFileInfo['flv']['audio']['audioRate']);
			$ThisFileInfo['audio']['bits_per_sample'] = $this->FLVaudioBitDepth($ThisFileInfo['flv']['audio']['audioSampleSize']);

			$ThisFileInfo['audio']['channels']   =  $ThisFileInfo['flv']['audio']['audioType'] + 1; // 0=mono,1=stereo
			$ThisFileInfo['audio']['lossless']   = ($ThisFileInfo['flv']['audio']['audioFormat'] ? false : true); // 0=uncompressed
			$ThisFileInfo['audio']['dataformat'] = 'flv';
		}
		if (!empty($ThisFileInfo['flv']['header']['hasVideo'])) {
			$ThisFileInfo['video']['codec']      = $this->FLVvideoCodec($ThisFileInfo['flv']['video']['videoCodec']);
			$ThisFileInfo['video']['dataformat'] = 'flv';
			$ThisFileInfo['video']['lossless']   = false;
		}

		// Set information from meta
		if (isset($ThisFileInfo['flv']['meta']['onMetaData']['duration'])) {
			$ThisFileInfo['playtime_seconds'] = $ThisFileInfo['flv']['meta']['onMetaData']['duration'];
			$ThisFileInfo['bitrate'] = (($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']) * 8) / $ThisFileInfo['playtime_seconds'];
		}
		if (isset($ThisFileInfo['flv']['meta']['onMetaData']['audiocodecid'])) {
			$ThisFileInfo['audio']['codec'] = $this->FLVaudioFormat($ThisFileInfo['flv']['meta']['onMetaData']['audiocodecid']);
		}
		if (isset($ThisFileInfo['flv']['meta']['onMetaData']['videocodecid'])) {
			$ThisFileInfo['video']['codec'] = $this->FLVvideoCodec($ThisFileInfo['flv']['meta']['onMetaData']['videocodecid']);
		}
		return true;
	}


	function FLVaudioFormat($id) {
		$FLVaudioFormat = array(
			0  => 'Linear PCM, platform endian',
			1  => 'ADPCM',
			2  => 'mp3',
			3  => 'Linear PCM, little endian',
			4  => 'Nellymoser 16kHz mono',
			5  => 'Nellymoser 8kHz mono',
			6  => 'Nellymoser',
			7  => 'G.711A-law logarithmic PCM',
			8  => 'G.711 mu-law logarithmic PCM',
			9  => 'reserved',
			10 => 'AAC',
			11 => false, // unknown?
			12 => false, // unknown?
			13 => false, // unknown?
			14 => 'mp3 8kHz',
			15 => 'Device-specific sound',
		);
		return (isset($FLVaudioFormat[$id]) ? $FLVaudioFormat[$id] : false);
	}

	function FLVaudioRate($id) {
		$FLVaudioRate = array(
			0 =>  5500,
			1 => 11025,
			2 => 22050,
			3 => 44100,
		);
		return (isset($FLVaudioRate[$id]) ? $FLVaudioRate[$id] : false);
	}

	function FLVaudioBitDepth($id) {
		$FLVaudioBitDepth = array(
			0 =>  8,
			1 => 16,
		);
		return (isset($FLVaudioBitDepth[$id]) ? $FLVaudioBitDepth[$id] : false);
	}

	function FLVvideoCodec($id) {
		$FLVvideoCodec = array(
			GETID3_FLV_VIDEO_H263         => 'Sorenson H.263',
			GETID3_FLV_VIDEO_SCREEN       => 'Screen video',
			GETID3_FLV_VIDEO_VP6FLV       => 'On2 VP6',
			GETID3_FLV_VIDEO_VP6FLV_ALPHA => 'On2 VP6 with alpha channel',
			GETID3_FLV_VIDEO_SCREENV2     => 'Screen video v2',
			GETID3_FLV_VIDEO_H264         => 'Sorenson H.264',
		);
		return (isset($FLVvideoCodec[$id]) ? $FLVvideoCodec[$id] : false);
	}
}

class AMFStream {
	var $bytes;
	var $pos;

	function AMFStream(&$bytes) {
		$this->bytes =& $bytes;
		$this->pos = 0;
	}

	function readByte() {
		return getid3_lib::BigEndian2Int(substr($this->bytes, $this->pos++, 1));
	}

	function readInt() {
		return ($this->readByte() << 8) + $this->readByte();
	}

	function readLong() {
		return ($this->readByte() << 24) + ($this->readByte() << 16) + ($this->readByte() << 8) + $this->readByte();
	}

	function readDouble() {
		return getid3_lib::BigEndian2Float($this->read(8));
	}

	function readUTF() {
		$length = $this->readInt();
		return $this->read($length);
	}

	function readLongUTF() {
		$length = $this->readLong();
		return $this->read($length);
	}

	function read($length) {
		$val = substr($this->bytes, $this->pos, $length);
		$this->pos += $length;
		return $val;
	}

	function peekByte() {
		$pos = $this->pos;
		$val = $this->readByte();
		$this->pos = $pos;
		return $val;
	}

	function peekInt() {
		$pos = $this->pos;
		$val = $this->readInt();
		$this->pos = $pos;
		return $val;
	}

	function peekLong() {
		$pos = $this->pos;
		$val = $this->readLong();
		$this->pos = $pos;
		return $val;
	}

	function peekDouble() {
		$pos = $this->pos;
		$val = $this->readDouble();
		$this->pos = $pos;
		return $val;
	}

	function peekUTF() {
		$pos = $this->pos;
		$val = $this->readUTF();
		$this->pos = $pos;
		return $val;
	}

	function peekLongUTF() {
		$pos = $this->pos;
		$val = $this->readLongUTF();
		$this->pos = $pos;
		return $val;
	}
}

class AMFReader {
	var $stream;

	function AMFReader(&$stream) {
		$this->stream =& $stream;
	}

	function readData() {
		$value = null;

		$type = $this->stream->readByte();
		switch ($type) {

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

	function readDouble() {
		return $this->stream->readDouble();
	}

	function readBoolean() {
		return $this->stream->readByte() == 1;
	}

	function readString() {
		return $this->stream->readUTF();
	}

	function readObject() {
		// Get highest numerical index - ignored
//		$highestIndex = $this->stream->readLong();

		$data = array();

		while ($key = $this->stream->readUTF()) {
			$data[$key] = $this->readData();
		}
		// Mixed array record ends with empty string (0x00 0x00) and 0x09
		if (($key == '') && ($this->stream->peekByte() == 0x09)) {
			// Consume byte
			$this->stream->readByte();
		}
		return $data;
	}

	function readMixedArray() {
		// Get highest numerical index - ignored
		$highestIndex = $this->stream->readLong();

		$data = array();

		while ($key = $this->stream->readUTF()) {
			if (is_numeric($key)) {
				$key = (float) $key;
			}
			$data[$key] = $this->readData();
		}
		// Mixed array record ends with empty string (0x00 0x00) and 0x09
		if (($key == '') && ($this->stream->peekByte() == 0x09)) {
			// Consume byte
			$this->stream->readByte();
		}

		return $data;
	}

	function readArray() {
		$length = $this->stream->readLong();
		$data = array();

		for ($i = 0; $i < $length; $i++) {
			$data[] = $this->readData();
		}
		return $data;
	}

	function readDate() {
		$timestamp = $this->stream->readDouble();
		$timezone = $this->stream->readInt();
		return $timestamp;
	}

	function readLongString() {
		return $this->stream->readLongUTF();
	}

	function readXML() {
		return $this->stream->readLongUTF();
	}

	function readTypedObject() {
		$className = $this->stream->readUTF();
		return $this->readObject();
	}
}

class AVCSequenceParameterSetReader {
	var $sps;
	var $start = 0;
	var $currentBytes = 0;
	var $currentBits = 0;
	var $width;
	var $height;

	function AVCSequenceParameterSetReader($sps) {
		$this->sps = $sps;
	}

	function readData() {
		$this->skipBits(8);
		$this->skipBits(8);
		$profile = $this->getBits(8);	//	read profile
		$this->skipBits(16);
		$this->expGolombUe();	//	read sps id
		if (in_array($profile, array(H264_PROFILE_HIGH, H264_PROFILE_HIGH10, H264_PROFILE_HIGH422, H264_PROFILE_HIGH444, H264_PROFILE_HIGH444_PREDICTIVE))) {
			if ($this->expGolombUe() == 3) {
				$this->skipBits(1);
			}
			$this->expGolombUe();
			$this->expGolombUe();
			$this->skipBits(1);
			if ($this->getBit()) {
				for ($i = 0; $i < 8; $i++) {
					if ($this->getBit()) {
						$size = $i < 6 ? 16 : 64;
						$lastScale = 8;
						$nextScale = 8;
						for ($j = 0; $j < $size; $j++) {
							if ($nextScale != 0) {
								$deltaScale = $this->expGolombUe();
								$nextScale = ($lastScale + $deltaScale + 256) % 256;
							}
							if ($nextScale != 0) {
								$lastScale = $nextScale;
							}
						}
					}
				}
			}
		}
		$this->expGolombUe();
		$pocType = $this->expGolombUe();
		if ($pocType == 0) {
			$this->expGolombUe();
		} elseif ($pocType == 1) {
			$this->skipBits(1);
			$this->expGolombSe();
			$this->expGolombSe();
			$pocCycleLength = $this->expGolombUe();
			for ($i = 0; $i < $pocCycleLength; $i++) {
				$this->expGolombSe();
			}
		}
		$this->expGolombUe();
		$this->skipBits(1);
		$this->width = ($this->expGolombUe() + 1) * 16;
		$heightMap = $this->expGolombUe() + 1;
		$this->height = (2 - $this->getBit()) * $heightMap * 16;
	}

	function skipBits($bits) {
		$newBits = $this->currentBits + $bits;
		$this->currentBytes += (int)floor($newBits / 8);
		$this->currentBits = $newBits % 8;
	}

	function getBit() {
		$result = (getid3_lib::BigEndian2Int(substr($this->sps, $this->currentBytes, 1)) >> (7 - $this->currentBits)) & 0x01;
		$this->skipBits(1);
		return $result;
	}

	function getBits($bits) {
		$result = 0;
		for ($i = 0; $i < $bits; $i++) {
			$result = ($result << 1) + $this->getBit();
		}
		return $result;
	}

	function expGolombUe() {
		$significantBits = 0;
		$bit = $this->getBit();
		while ($bit == 0) {
			$significantBits++;
			$bit = $this->getBit();
		}
		return (1 << $significantBits) + $this->getBits($significantBits) - 1;
	}

	function expGolombSe() {
		$result = $this->expGolombUe();
		if (($result & 0x01) == 0) {
			return -($result >> 1);
		} else {
			return ($result + 1) >> 1;
		}
	}

	function getWidth() {
		return $this->width;
	}

	function getHeight() {
		return $this->height;
	}
}

?>