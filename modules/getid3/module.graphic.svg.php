<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.graphic.svg.php                                      //
// module for analyzing SVG Image files                        //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_svg
{


	function getid3_svg(&$fd, &$ThisFileInfo) {
		fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);

		$SVGheader = fread($fd, 4096);
		if (preg_match('#\<\?xml([^\>]+)\?\>#i', $SVGheader, $matches)) {
			$ThisFileInfo['svg']['xml']['raw'] = $matches;
		}
		if (preg_match('#\<\!DOCTYPE([^\>]+)\>#i', $SVGheader, $matches)) {
			$ThisFileInfo['svg']['doctype']['raw'] = $matches;
		}
		if (preg_match('#\<svg([^\>]+)\>#i', $SVGheader, $matches)) {
			$ThisFileInfo['svg']['svg']['raw'] = $matches;
		}
		if (isset($ThisFileInfo['svg']['svg']['raw'])) {

			$sections_to_fix = array('xml', 'doctype', 'svg');
			foreach ($sections_to_fix as $section_to_fix) {
				if (!isset($ThisFileInfo['svg'][$section_to_fix])) {
					continue;
				}
				$section_data = array();
				while (preg_match('/ "([^"]+)"/', $ThisFileInfo['svg'][$section_to_fix]['raw'][1], $matches)) {
					$section_data[] = $matches[1];
					$ThisFileInfo['svg'][$section_to_fix]['raw'][1] = str_replace($matches[0], '', $ThisFileInfo['svg'][$section_to_fix]['raw'][1]);
				}
				while (preg_match('/([^\s]+)="([^"]+)"/', $ThisFileInfo['svg'][$section_to_fix]['raw'][1], $matches)) {
					$section_data[] = $matches[0];
					$ThisFileInfo['svg'][$section_to_fix]['raw'][1] = str_replace($matches[0], '', $ThisFileInfo['svg'][$section_to_fix]['raw'][1]);
				}
				$section_data = array_merge($section_data, preg_split('/[\s,]+/', $ThisFileInfo['svg'][$section_to_fix]['raw'][1]));
				foreach ($section_data as $keyvaluepair) {
					$keyvaluepair = trim($keyvaluepair);
					if ($keyvaluepair) {
						@list($key, $value) = explode('=', $keyvaluepair);
						$ThisFileInfo['svg'][$section_to_fix]['sections'][$key] = trim($value, '"');
					}
				}
			}

			$ThisFileInfo['fileformat']                  = 'svg';
			$ThisFileInfo['video']['dataformat']         = 'svg';
			$ThisFileInfo['video']['lossless']           = true;
			//$ThisFileInfo['video']['bits_per_sample']    = 24;
			$ThisFileInfo['video']['pixel_aspect_ratio'] = (float) 1;

			if (@$ThisFileInfo['svg']['svg']['sections']['width']) {
				$ThisFileInfo['svg']['width']  = intval($ThisFileInfo['svg']['svg']['sections']['width']);
			}
			if (@$ThisFileInfo['svg']['svg']['sections']['height']) {
				$ThisFileInfo['svg']['height'] = intval($ThisFileInfo['svg']['svg']['sections']['height']);
			}
			if (@$ThisFileInfo['svg']['svg']['sections']['version']) {
				$ThisFileInfo['svg']['version'] = $ThisFileInfo['svg']['svg']['sections']['version'];
			}
			if (!isset($ThisFileInfo['svg']['version']) && isset($ThisFileInfo['svg']['doctype']['sections'])) {
				foreach ($ThisFileInfo['svg']['doctype']['sections'] as $key => $value) {
					if (preg_match('#//W3C//DTD SVG ([0-9\.]+)//#i', $key, $matches)) {
						$ThisFileInfo['svg']['version'] = $matches[1];
						break;
					}
				}
			}

			if (@$ThisFileInfo['svg']['width']) {
				$ThisFileInfo['video']['resolution_x'] = $ThisFileInfo['svg']['width'];
			}
			if (@$ThisFileInfo['svg']['height']) {
				$ThisFileInfo['video']['resolution_y'] = $ThisFileInfo['svg']['height'];
			}

			return true;
		}
		$ThisFileInfo['error'][] = 'Did not find expected <svg> tag';
		return false;
	}

}


?>