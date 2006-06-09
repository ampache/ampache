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
//  Support for On2 VP6 codec and meta information by          //
//  Steve Webster <steve.webster@featurecreep.com>             //
//  * version 0.2 (22 February 2006)                           //
//                                                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.audio-video.flv.php                                  //
// module for analyzing Shockwave Flash Video files            //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////

define('GETID3_FLV_TAG_AUDIO', 8);
define('GETID3_FLV_TAG_VIDEO', 9);
define('GETID3_FLV_TAG_META', 18);

define('GETID3_FLV_VIDEO_H263',   2);
define('GETID3_FLV_VIDEO_SCREEN', 3);
define('GETID3_FLV_VIDEO_VP6',    4);

class getid3_flv
{

	function getid3_flv(&$fd, &$ThisFileInfo, $ReturnAllTagData=false) {
		fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);

		$FLVfileData = fread($fd, $ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']);

		$FLVmagic = substr($FLVfileData, 0, 3);
		if ($FLVmagic != 'FLV') {
			$ThisFileInfo['error'][] = 'Expecting "FLV" at offset '.$ThisFileInfo['avdataoffset'].', found "'.$ThisFileInfo['flv']['header']['signature'].'"';
			unset($ThisFileInfo['flv']);
			unset($ThisFileInfo['fileformat']);
			return false;
		}
		$ThisFileInfo['flv']['header']['signature'] = $FLVmagic;
		$ThisFileInfo['flv']['header']['version']   = ord($FLVfileData{3});
		$ThisFileInfo['fileformat'] = 'flv';

		$TypeFlags = ord($FLVfileData{4});
		$ThisFileInfo['flv']['header']['hasAudio'] = (bool) ($TypeFlags & 4);
		$ThisFileInfo['flv']['header']['hasVideo'] = (bool) ($TypeFlags & 1);

		$FrameSizeDataLength = getid3_lib::BigEndian2Int(substr($FLVfileData, 5, 4));

		// FLV tags
		$CurrentOffset = $FrameSizeDataLength;
		$FLVdataLength = strlen($FLVfileData);

		$Duration = 0;

		$SoundFormat = null;
		$VideoFormat = null;
		while ($CurrentOffset < $FLVdataLength) {
			// previous tag size
			$PreviousTagLength = getid3_lib::BigEndian2Int(substr($FLVfileData, $CurrentOffset, 4));
			$CurrentOffset += 4;

			$TagType = ord(substr($FLVfileData, $CurrentOffset, 1));
			$DataLength = getid3_lib::BigEndian2Int(substr($FLVfileData, $CurrentOffset + 1, 3));
			$Timestamp  = getid3_lib::BigEndian2Int(substr($FLVfileData, $CurrentOffset + 4, 3));

			switch ($TagType) {
				case GETID3_FLV_TAG_AUDIO:
					if (is_null($SoundFormat)) {
						$SoundInfo = ord(substr($FLVfileData, $CurrentOffset + 11, 1));
						$SoundFormat = $SoundInfo & 0x07;
						$ThisFileInfo['flv']['audio']['audioFormat']     = $SoundFormat;
						$ThisFileInfo['flv']['audio']['audioRate']       = ($SoundInfo & 0x30) / 0x10;
						$ThisFileInfo['flv']['audio']['audioSampleSize'] = ($SoundInfo & 0x40) / 0x40;
						$ThisFileInfo['flv']['audio']['audioType']       = ($SoundInfo & 0x80) / 0x80;
					}
					break;

				case GETID3_FLV_TAG_VIDEO:
					if (is_null($VideoFormat)) {
						$VideoInfo = ord(substr($FLVfileData, $CurrentOffset + 11, 1));
						$VideoFormat = $VideoInfo & 0x07;
						$ThisFileInfo['flv']['video']['videoCodec'] = $VideoFormat;

						if ($VideoFormat != GETID3_FLV_VIDEO_VP6) {

							$PictureSizeType = (getid3_lib::BigEndian2Int(substr($FLVfileData, $CurrentOffset + 15, 2))) >> 7;
							$PictureSizeType = $PictureSizeType & 0x0007;
							$ThisFileInfo['flv']['header']['videoSizeType'] = $PictureSizeType;
							switch ($PictureSizeType) {
								case 0:
									$PictureSizeEnc = getid3_lib::BigEndian2Int(substr($FLVfileData, $CurrentOffset + 16, 2));
									$PictureSizeEnc <<= 1;
									$ThisFileInfo['video']['resolution_x'] = ($PictureSizeEnc & 0xFF00) >> 8;
									$PictureSizeEnc = getid3_lib::BigEndian2Int(substr($FLVfileData, $CurrentOffset + 17, 2));
									$PictureSizeEnc <<= 1;
									$ThisFileInfo['video']['resolution_y'] = ($PictureSizeEnc & 0xFF00) >> 8;
									break;

								case 1:
									$PictureSizeEnc = getid3_lib::BigEndian2Int(substr($FLVfileData, $CurrentOffset + 16, 4));
									$PictureSizeEnc <<= 1;
									$ThisFileInfo['video']['resolution_x'] = ($PictureSizeEnc & 0xFFFF0000) >> 16;

									$PictureSizeEnc = getid3_lib::BigEndian2Int(substr($FLVfileData, $CurrentOffset + 18, 4));
									$PictureSizeEnc <<= 1;
									$ThisFileInfo['video']['resolution_y'] = ($PictureSizeEnc & 0xFFFF0000) >> 16;
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
					}
					break;

				// Meta tag
				case GETID3_FLV_TAG_META:

					$reader = new AMFReader(new AMFStream(substr($FLVfileData, $CurrentOffset + 11, $DataLength)));
					$eventName = $reader->readData();
					$ThisFileInfo['meta'][$eventName] = $reader->readData();

					unset($reader);

					$ThisFileInfo['video']['frame_rate']         = $ThisFileInfo['meta']['onMetaData']['framerate'];
					$ThisFileInfo['video']['resolution_x']       = $ThisFileInfo['meta']['onMetaData']['width'];
					$ThisFileInfo['video']['resolution_y']       = $ThisFileInfo['meta']['onMetaData']['height'];

					break;

				default:
					// noop
					break;
			}

			if ($Timestamp > $Duration) {
				$Duration = $Timestamp;
			}

			$CurrentOffset += ($DataLength + 11);
		}

		$ThisFileInfo['playtime_seconds'] = $Duration / 1000;
		$ThisFileInfo['bitrate'] = ($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']) / $ThisFileInfo['playtime_seconds'];

		if ($ThisFileInfo['flv']['header']['hasAudio']) {
			$ThisFileInfo['audio']['codec']           =   $this->FLVaudioFormat($ThisFileInfo['flv']['audio']['audioFormat']);
			$ThisFileInfo['audio']['sample_rate']     =     $this->FLVaudioRate($ThisFileInfo['flv']['audio']['audioRate']);
			$ThisFileInfo['audio']['bits_per_sample'] = $this->FLVaudioBitDepth($ThisFileInfo['flv']['audio']['audioSampleSize']);

			$ThisFileInfo['audio']['channels']   = $ThisFileInfo['flv']['audio']['audioType'] + 1; // 0=mono,1=stereo
			$ThisFileInfo['audio']['lossless']   = ($ThisFileInfo['flv']['audio']['audioFormat'] ? false : true); // 0=uncompressed
			$ThisFileInfo['audio']['dataformat'] = 'flv';
		}
		if (@$ThisFileInfo['flv']['header']['hasVideo']) {
			$ThisFileInfo['video']['codec']      =   $this->FLVvideoCodec($ThisFileInfo['flv']['video']['videoCodec']);
			$ThisFileInfo['video']['dataformat'] = 'flv';
			$ThisFileInfo['video']['lossless']   = false;
		}

		return true;
	}


	function FLVaudioFormat($id) {
		$FLVaudioFormat = array(
			0 => 'uncompressed',
			1 => 'ADPCM',
			2 => 'mp3',
			5 => 'Nellymoser 8kHz mono',
			6 => 'Nellymoser',
		);
		return (@$FLVaudioFormat[$id] ? @$FLVaudioFormat[$id] : false);
	}

	function FLVaudioRate($id) {
		$FLVaudioRate = array(
			0 =>  5500,
			1 => 11025,
			2 => 22050,
			3 => 44100,
		);
		return (@$FLVaudioRate[$id] ? @$FLVaudioRate[$id] : false);
	}

	function FLVaudioBitDepth($id) {
		$FLVaudioBitDepth = array(
			0 =>  8,
			1 => 16,
		);
		return (@$FLVaudioBitDepth[$id] ? @$FLVaudioBitDepth[$id] : false);
	}

	function FLVvideoCodec($id) {
		$FLVvideoCodec = array(
			GETID3_FLV_VIDEO_H263   => 'Sorenson H.263',
			GETID3_FLV_VIDEO_SCREEN => 'Screen video',
			GETID3_FLV_VIDEO_VP6    => 'On2 VP6',
		);
		return (@$FLVvideoCodec[$id] ? @$FLVvideoCodec[$id] : false);
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

	function readMixedArray() {
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

	function readArray() {
		$length = $this->stream->readLong();

		$data = array();

		for ($i = 0; $i < count($length); $i++) {
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

?>