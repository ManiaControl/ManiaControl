<?php
/**
 * UpdateStructure
 *
 * @author steeffeen & kremsy
 */
namespace ManiaControl\Update;


class UpdateData {
	public $version = 0;
	public $channel = "";
	public $url = "";
	public $releaseDate = "";

	public function __construct($updateData) {
		$this->version     = $updateData->version;
		$this->channel     = $updateData->channel;
		$this->url         = $updateData->url;
		$this->releaseDate = $updateData->release_date;
	}
} 