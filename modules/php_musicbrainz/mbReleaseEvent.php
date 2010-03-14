<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
	class mbReleaseEvent {
		private $country;
		private $dateStr;
		private $catalogNumber;
		private $barcode;
		private $label = null;

		function mbReleaseEvent( $country = '', $dateStr = '' ) {
			$this->country = $country;
			$this->dateStr = $dateStr;
		}

		function setCountry	  ( $country  ) { $this->country = $country;		}
		function getCountry	  (		   ) { return $this->country;			}
		function setCatalogNumber( $c_number ) { $this->catalogNumber = $c_number; }
		function getCatalogNumber(		   ) { return $this->catalogNumber;	  }
		function setBarcode	  ( $barcode  ) { $this->barcode = $barcode;		}
		function getBarcode	  (		   ) { return $this->barcode;			}
		function setDate		 ( $date	 ) { $this->date = $date;			  }
		function getDate		 (		   ) { return $this->date;			   }

		function setLabel( Label $label ) {
			$this->label = $label;
		}

		function getLabel() {
			return $this->label;
		}
	}
?>
