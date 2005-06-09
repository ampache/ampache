<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// write.real.php                                              //
// module for writing RealAudio/RealVideo tags                 //
// dependencies: module.tag.real.php                           //
//                                                            ///
/////////////////////////////////////////////////////////////////

class getid3_write_real
{
	var $filename;
	var $tag_data;
	var $warnings     = array(); // any non-critical errors will be stored here
	var $errors       = array(); // any critical errors will be stored here
	var $paddedlength = 512;     // minimum length of CONT tag in bytes

	function getid3_write_real() {
		return true;
	}

	function WriteReal() {
		// File MUST be writeable - CHMOD(646) at least
		if (is_writeable($this->filename)) {
			if ($fp_source = @fopen($this->filename, 'r+b')) {

				// Initialize getID3 engine
				$getID3 = new getID3;
				$OldThisFileInfo = $getID3->analyze($this->filename);
				if (empty($OldThisFileInfo['chunks']) && !empty($OldThisFileInfo['old_ra_header'])) {
					$this->errors[] = 'Cannot write Real tags on old-style file format';
					return false;
				}

				$OldPROPinfo = false;
				$StartOfDATA = false;
				foreach ($OldThisFileInfo['chunks'] as $chunknumber => $chunkarray) {
					if ($chunkarray['name'] == 'PROP') {
						$OldPROPinfo = $chunkarray;
					} elseif ($chunkarray['name'] = 'DATA') {
						$StartOfDATA = $chunkarray['offset'];
					}
				}

				if (!empty($OldPROPinfo['length'])) {
					$this->paddedlength = max($OldPROPinfo['length'], $this->paddedlength);
				}

				$new_real_tag_data = GenerateRealTag();

				if (@$OldPROPinfo['length'] == $new_real_tag_data) {

					// new data length is same as old data length - just overwrite
					fseek($fp_source, $OldPROPinfo['offset'], SEEK_SET);
					fwrite($fp_source, $new_real_tag_data);

				} else {

					if (empty($OldPROPinfo)) {
						// no existing PROP chunk
						$BeforeOffset = $StartOfDATA;
						$AfterOffset  = $StartOfDATA;
					} else {
						// new data is longer than old data
						$BeforeOffset = $OldPROPinfo['offset'];
						$AfterOffset  = $OldPROPinfo['offset'] + $OldPROPinfo['length'];
					}


				}

				fclose($fp_source);
				return true;

			} else {
				$this->errors[] = 'Could not open '.$this->filename.' mode "r+b"';
				return false;
			}
		}
		$this->errors[] = 'File is not writeable: '.$this->filename;
		return false;
	}

	function GenerateRealTag() {
		$RealCONT  = "\x00\x00"; // object version

		$RealCONT .= BigEndian2String(strlen(@$this->tag_data['title']), 4);
		$RealCONT .= @$this->tag_data['title'];

		$RealCONT .= BigEndian2String(strlen(@$this->tag_data['artist']), 4);
		$RealCONT .= @$this->tag_data['artist'];

		$RealCONT .= BigEndian2String(strlen(@$this->tag_data['copyright']), 4);
		$RealCONT .= @$this->tag_data['copyright'];

		$RealCONT .= BigEndian2String(strlen(@$this->tag_data['comment']), 4);
		$RealCONT .= @$this->tag_data['comment'];

		if ($this->paddedlength > (strlen($RealCONT) + 8)) {
			$RealCONT .= str_repeat("\x00", $this->paddedlength - strlen($RealCONT) - 8);
		}

		$RealCONT  = 'CONT'.BigEndian2String(strlen($RealCONT) + 8, 4).$RealCONT; // CONT chunk identifier + chunk length

		return $RealCONT;
	}

	function RemoveReal() {
		// File MUST be writeable - CHMOD(646) at least
		if (is_writeable($this->filename)) {
			if ($fp_source = @fopen($this->filename, 'r+b')) {

return false;
				//fseek($fp_source, -128, SEEK_END);
				//if (fread($fp_source, 3) == 'TAG') {
				//	ftruncate($fp_source, filesize($this->filename) - 128);
				//} else {
				//	// no real tag to begin with - do nothing
				//}
				fclose($fp_source);
				return true;

			} else {
				$this->errors[] = 'Could not open '.$this->filename.' mode "r+b"';
			}
		} else {
			$this->errors[] = $this->filename.' is not writeable';
		}
		return false;
	}

}

?>