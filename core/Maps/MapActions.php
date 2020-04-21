<?php

namespace ManiaControl\Maps;

use ManiaControl\Communication\CommunicationAnswer;
use ManiaControl\Communication\CommunicationListener;
use ManiaControl\Communication\CommunicationMethods;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\ManiaControl;
use Maniaplanet\DedicatedServer\Xmlrpc\ChangeInProgressException;

/**
 * ManiaControl Map Actions Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class MapActions implements CommunicationListener, UsageInformationAble {
	use UsageInformationTrait;
	
	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

	/**
	 * Construct a map actions instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		//Communication Listenings
		$this->maniaControl->getCommunicationManager()->registerCommunicationListener(CommunicationMethods::SKIP_MAP, $this, function ($data) {
			$success = $this->skipMap();
			return new CommunicationAnswer(array("success" => $success));
		});

		$this->maniaControl->getCommunicationManager()->registerCommunicationListener(CommunicationMethods::RESTART_MAP, $this, function ($data) {
			$success = $this->restartMap();
			return new CommunicationAnswer(array("success" => $success));
		});

		$this->maniaControl->getCommunicationManager()->registerCommunicationListener(CommunicationMethods::SKIP_TO_MAP, $this, function ($data) {
			if (!is_object($data)) {
				return new CommunicationAnswer("Error in provided Data", true);
			}

			if (property_exists($data, "mxId")) {
				$success = $this->skipToMapByMxId($data->mxId);
			} else if (property_exists($data, "mapUid")) {
				$success = $this->skipToMapByUid($data->mapUid);
			} else {
				return new CommunicationAnswer("No mxId or mapUid provided.", true);
			}

			return new CommunicationAnswer(array("success" => $success));
		});
	}

	/**
	 * Skips to a Map by its given UID
	 *
	 * @param String $uid
	 * @return bool
	 */
	public function skipToMapByUid($uid) {
		//TODO message
		//Check if Map exists
		$map = $this->maniaControl->getMapManager()->getMapByUid($uid);
		if (!$map) {
			return false;
		}

		try {
			$this->maniaControl->getClient()->jumpToMapIdent($uid);
		} catch (ChangeInProgressException $e) {
			return false;
		}
		return true;
	}

	/**
	 * Skips to a Map by its given MxId
	 *
	 * @param int $mxId
	 * @return bool
	 */
	public function skipToMapByMxId($mxId) {
		$map = $this->maniaControl->getMapManager()->getMapByMxId($mxId);
		if (!$map) {
			return false;
		}
		return $this->skipToMapByUid($map->uid);
	}

	/**
	 * Skip the current Map
	 *
	 * @return bool
	 */
	public function skipMap() {
		//TODO message

		// Force an EndMap on the MapQueue to set the next Map
		$this->maniaControl->getMapManager()->getMapQueue()->endMap();

		// Ignore EndMap on MapQueue
		$this->maniaControl->getMapManager()->getMapQueue()->dontQueueNextMapChange();

		// Switch The Map
		try {
			$this->maniaControl->getClient()->nextMap();
		} catch (ChangeInProgressException $e) {
			return false;
		}

		return true;
	}

	/**
	 * Restarts the Current Map
	 *
	 * @return bool
	 */
	public function restartMap() {
		//TODO message

		//Restarts the Current Map
		try {
			$this->maniaControl->getClient()->restartMap();
		} catch (ChangeInProgressException $e) {
			return false;
		}

		return true;
	}
}
