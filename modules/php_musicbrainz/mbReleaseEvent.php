<?php
/* vim:set tabstop=4 softtabstop=4 shiftwidth=4 noexpandtab: */
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
