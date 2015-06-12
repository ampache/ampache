<?php

namespace Moinax\TvDb;

/**
 * Actor object
 *
 * @package TvDb
 * @author Lucas Personnaz <lucas.personnaz@gmail.com>
 **/

class Actor 
{

	/**
	 * @var int
	 */
	public $id;

	/**
	 * @var string
	 */
	public $image;

	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var string
	 */
	public $role;

	/**
	 * @var int
	 */
	public $sortOrder;

	/**
	 * Constructor
	 *
	 * @access public
	 * @param \SimpleXMLElement $data A simplexml element created from thetvdb.com's xml data for the actor
	 * @return \Moinax\TvDb\Actor
	 */
	public function __construct($data)
	{
		$this->id = (int)$data->id;
		$this->image = (string)$data->Image;
		$this->name = (string)$data->Name;
		$this->role = (string)$data->Role;
		$this->sortOrder = (int)$data->SortOrder;
	}

}