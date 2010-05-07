<?php
/* vim:set tabstop=4 softtabstop=4 shiftwidth=4 noexpandtab: */
class mbRating {
    private $rating;

    public function __construct($rating=0) {
        $this->rating = $rating;
    }

    public function setRating($rating)		{ $this->rating = $rating; }
	public function getRating()			{ return $this->rating; }
}
?>
