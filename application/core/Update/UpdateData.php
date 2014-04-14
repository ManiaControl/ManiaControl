<?php

namespace ManiaControl\Update;

/**
 * UpdateStructure
 *
 * @author steeffeen & kremsy
 * @copyright ManiaControl Copyright © 2014 ManiaControl Team
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class UpdateData {
	/*
	 * Public Properties
	 */
	public $version = 0;
	public $channel = "";
	public $url = "";
	public $releaseDate = "";
	public $minDedicatedBuild = "";
	/**
	 * Construct new Update Data
	 * 
	 * @param Object $updateData
	 */
	public function __construct($updateData) {
		$this->version     = $updateData->version;
		$this->channel     = $updateData->channel;
		$this->url         = $updateData->url;
		$this->releaseDate = $updateData->release_date;
		$this->minDedicatedBuild = $updateData->min_dedicated_build;
	}
} 
