<?php

namespace ManiaControl\Update;

/**
 * Update Data Structure
 * 
 * @author ManiaControl Team
 * @copyright ManiaControl Copyright © 2014 ManiaControl Team
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class UpdateData {
	/*
	 * Public Properties
	 */
	public $version = null;
	public $channel = null;
	public $url = null;
	public $releaseDate = null;
	public $minDedicatedBuild = null;

	/**
	 * Construct new Update Data
	 * 
	 * @param object $updateData
	 */
	public function __construct($updateData) {
		$this->version = $updateData->version;
		$this->channel = $updateData->channel;
		$this->url = $updateData->url;
		$this->releaseDate = $updateData->release_date;
		$this->minDedicatedBuild = $updateData->min_dedicated_build;
	}

	/**
	 * Check if the Update Data is newer than the given Date
	 * 
	 * @param string $compareDate
	 * @return bool
	 */
	public function isNewerThan($compareDate) {
		if (!$compareDate) {
			return true;
		}
		$compareTime = strtotime($compareDate);
		$releaseTime = strtotime($this->releaseDate);
		return ($releaseTime > $compareTime);
	}
} 
