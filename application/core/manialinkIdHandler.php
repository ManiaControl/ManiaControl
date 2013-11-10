<?php

namespace ManiaControl;

/**
 * Handler for manialink ids
 *
 * @author kremsy & steeffeen
 */
class ManialinkIdHandler {
	/**
	 * Private properties
	 */
	private $maniaLinkIdCount = 0;

	/**
	 * Reserve manialink ids
	 *
	 * @param int $count        	
	 * @return array with manialink Ids
	 */
	public function reserveManiaLinkIds($count) {
		$mlIds = array();
		for ($i = 0; $i < $count; $i++) {
			array_push($mlIds, ++$this->maniaLinkIdCount);
		}
		return $mlIds;
	}
}
?>