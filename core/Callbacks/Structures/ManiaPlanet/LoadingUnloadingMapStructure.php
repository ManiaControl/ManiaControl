<?php

namespace ManiaControl\Callbacks\Structures\ManiaPlanet;


use ManiaControl\Callbacks\Structures\Common\BaseTimeStructure;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the Loading Map End and UnloadingMap Begin Callbacks
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class LoadingUnloadingMapStructure extends BaseTimeStructure {
	private $restarted;

	/**
	 * StartServerStructure constructor.
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @param                            $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);
	}

	/**
	 * Flag if the Server got Restarted
	 *
	 * @api
	 * @return mixed
	 */
	public function getRestarted() {
		return $this->restarted;
	}

	/**
	 * Gets the Map
	 *
	 * @api
	 * @return \ManiaControl\Maps\Map
	 */
	public function getMap() {
		return $this->maniaControl->getMapManager()->getMapByUid($this->getPlainJsonObject()->map->uid);
	}

}