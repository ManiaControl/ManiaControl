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
	private   $plainJsonObject;

	protected function __construct(ManiaControl $maniaControl, $data) {
		$this->maniaControl    = $maniaControl;
		$this->plainJsonObject = json_decode($data[0]);
	}

	/**
	 * Gets the Plain Json
	 */
	public function getPlainJsonObject() {
		return $this->plainJsonObject;
	}

	/**
	 * Var_Dump the Structure
	 */
	public function dump() {
		var_dump(json_decode(json_encode($this)));
		var_dump("Class Name including Namespace: " . get_class($this));
	}
}