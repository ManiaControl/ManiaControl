<?php

namespace ManiaControl\Update;

/**
 * ManiaControl Update Data Model Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class UpdateData {
	/*
	 * Public properties
	 */
	public $version = null;
	public $channel = null;
	public $url = null;
	public $releaseDate = null;
	public $minDedicatedBuild = null;

	/**
	 * Construct new update data instance
	 *
	 * @param object $updateData
	 */
	public function __construct($updateData) {
		$this->version           = $updateData->version;
		$this->channel           = $updateData->channel;
		$this->url               = $updateData->url;
		$this->releaseDate       = $updateData->release_date;
		$this->minDedicatedBuild = $updateData->min_dedicated_build;
	}

	/**
	 * Check if the update data is newer than the given date
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
