<?php

namespace ManiaControl\Update;

/**
 * Plugin Update Data Structure
 * 
 * @author ManiaControl Team
 * @copyright ManiaControl Copyright © 2014 ManiaControl Team
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class PluginUpdateData {
	/*
	 * Public Properties
	 */
	public $pluginId = null;
	public $pluginName = null;
	public $pluginAuthor = null;
	public $pluginDescription = null;
	public $id = null;
	public $version = null;
	public $zipfile = null;
	public $url = null;

	/**
	 * Construct new Plugin Update Data
	 * 
	 * @param object $updateData
	 */
	public function __construct($updateData) {
		$this->pluginId = $updateData->id;
		$this->pluginName = $updateData->name;
		$this->pluginAuthor = $updateData->author;
		$this->pluginDescription = $updateData->description;
		if ($updateData->currentVersion) {
			$this->id = $updateData->currentVersion->id;
			$this->version = $updateData->currentVersion->version;
			$this->zipfile = $updateData->currentVersion->zipfile;
			$this->url = $updateData->currentVersion->url;
		}
	}

	/**
	 * Check if the Plugin Update Data is newer than the given Plugin Versin
	 * 
	 * @param float $version
	 * @return bool
	 */
	public function isNewerThan($version) {
		if (!$version) {
			return true;
		}
		return ($this->version > $version);
	}
} 
