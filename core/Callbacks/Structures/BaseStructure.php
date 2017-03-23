<?php
/**
 * Created by PhpStorm.
 * User: Lukas
 * Date: 22. Mär. 2017
 * Time: 18:26
 */

namespace ManiaControl\Callbacks\Structures;


use ManiaControl\ManiaControl;

abstract class BaseStructure {
	/** @var ManiaControl $maniaControl */
	protected $maniaControl;
	private   $plainJson;

	/**
	 * Sets ManiaControl
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 */
	protected function setManiaControl(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
	}

	/**
	 * Decodes the Data and Sets the Json
	 *
	 * @param array $data
	 */
	protected function setJson($data) {
		$this->plainJson = json_decode($data[0]);
	}

	/**
	 * Gets the Plain Json
	 */
	public function getJson() {
		return $this->plainJson;
	}
	
	/**
	 * Var_Dump the Structure
	 */
	public function dump() {
		var_dump($this->getJson());
		var_dump(json_decode(json_encode($this)));
	}
}