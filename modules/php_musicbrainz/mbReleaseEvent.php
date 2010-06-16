<?php
/* vim:set tabstop=4 softtabstop=4 shiftwidth=4 noexpandtab: */
/*
 Copyright 2009, 2010 Timothy John Wood, Paul Arthur MacIain

 This file is part of php_musicbrainz
 
 php_musicbrainz is free software: you can redistribute it and/or modify
 it under the terms of the GNU Lesser General Public License as published by
 the Free Software Foundation, either version 2.1 of the License, or
 (at your option) any later version.
 
 php_musicbrainz is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU Lesser General Public License for more details.
 
 You should have received a copy of the GNU Lesser General Public License
 along with php_musicbrainz.  If not, see <http://www.gnu.org/licenses/>.
*/
class mbReleaseEvent {
    private $country;
    private $dateStr;
    private $catalogNumber;
    private $barcode;
    private $label = null;

    public function __construct($country = '', $dateStr = '') {
        $this->country = $country;
        $this->dateStr = $dateStr;
    }

    public function setCountry      ($country ) { $this->country = $country;        }
    public function getCountry      (         ) { return $this->country;            }
    public function setCatalogNumber($c_number) { $this->catalogNumber = $c_number; }
    public function getCatalogNumber(         ) { return $this->catalogNumber;      }
    public function setBarcode      ($barcode ) { $this->barcode = $barcode;        }
    public function getBarcode      (         ) { return $this->barcode;            }
    public function setDate         ($date    ) { $this->date = $date;              }
    public function getDate         (         ) { return $this->date;               }

    public function setLabel(Label $label) {
        $this->label = $label;
    }

    public function getLabel() {
        return $this->label;
    }
}
?>
