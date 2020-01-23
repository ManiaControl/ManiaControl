<?php

namespace ManiaControl\Callbacks\Structures\Common;


use ManiaControl\ManiaControl;

/**
 * Structure Class for the Player Added and Removed Structure Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class BasePlayerTimeStructure extends BaseTimeStructure {
	protected $login;

	/**
	 * Construct a new On Hit Structure
	 *
	 * @param ManiaControl $maniaControl
	 * @param array        $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->login = $this->getPlainJsonObject()->login;
	}

	/**
	 * Gets the Player Object
	 *
	 * @api
	 * @return \ManiaControl\Players\Player
	 */
	public function getPlayer() {
		return $this->maniaControl->getPlayerManager()->getPlayer($this->login);
	}

	/**
	 * Returns the Login of the Player
	 *
	 * @api
	 * @return string
	 */
	public function getLogin() {
		return $this->login;
	}


}