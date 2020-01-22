<?php

namespace ManiaControl\Callbacks\Structures\ShootMania\Models;

/**
 * VictoryTypes Interface (only available in Elite atm)
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface VictoryTypes {
	const TIME_LIMIT           = 1;
	const CAPTURE              = 2;
	const ATTACKER_ELIMINATED  = 3;
	const DEFENDERS_ELIMINATED = 4;
}
