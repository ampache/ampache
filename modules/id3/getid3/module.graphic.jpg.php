<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.graphic.jpg.php                                      //
// module for analyzing JPEG Image files                       //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_jpg
{


	function getid3_jpg(&$fd, &$ThisFileInfo) {
		$ThisFileInfo['fileformat']                  = 'jpg';
		$ThisFileInfo['video']['dataformat']         = 'jpg';
		$ThisFileInfo['video']['lossless']           = false;
		$ThisFileInfo['video']['bits_per_sample']    = 24;
		$ThisFileInfo['video']['pixel_aspect_ratio'] = (float) 1;

		fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);

		list($width, $height, $type) = getid3_lib::GetDataImageSize(fread($fd, $ThisFileInfo['filesize']));
		if ($type == 2) {

			$ThisFileInfo['video']['resolution_x'] = $width;
			$ThisFileInfo['video']['resolution_y'] = $height;

			if (version_compare(phpversion(), '4.2.0', '>=')) {

				if (function_exists('exif_read_data')) {

					ob_start();
					$ThisFileInfo['jpg']['exif'] = exif_read_data($ThisFileInfo['filenamepath'], '', true, false);
					$errors = ob_get_contents();
					if ($errors) {
						$ThisFileInfo['error'][] = strip_tags($errors);
						unset($ThisFileInfo['jpg']['exif']);
					}
					ob_end_clean();

				} else {

					$ThisFileInfo['warning'][] = 'EXIF parsing only available when '.(GETID3_OS_ISWINDOWS ? 'php_exif.dll enabled' : 'compiled with --enable-exif');

				}

			} else {

				$ThisFileInfo['warning'][] = 'EXIF parsing only available in PHP v4.2.0 and higher compiled with --enable-exif (or php_exif.dll enabled for Windows). You are using PHP v'.phpversion();

			}

			return true;

		}

		unset($ThisFileInfo['fileformat']);
		return false;
	}

}


?>