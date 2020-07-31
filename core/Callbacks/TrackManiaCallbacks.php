<?php

namespace ManiaControl\Callbacks;

use ManiaControl\Callbacks\Structures\Common\BasePlayerTimeStructure;
use ManiaControl\Callbacks\Structures\Common\UIPropertiesBaseStructure;
use ManiaControl\Callbacks\Structures\TrackMania\OnCommandStructure;
use ManiaControl\Callbacks\Structures\TrackMania\OnDefaultEventStructure;
use ManiaControl\Callbacks\Structures\TrackMania\OnPointsRepartitionStructure;
use ManiaControl\Callbacks\Structures\TrackMania\OnRespawnStructure;
use ManiaControl\Callbacks\Structures\TrackMania\OnScoresStructure;
use ManiaControl\Callbacks\Structures\TrackMania\OnStartLineEventStructure;
use ManiaControl\Callbacks\Structures\TrackMania\OnStuntEventStructure;
use ManiaControl\Callbacks\Structures\TrackMania\OnWarmupStartEndRoundStructure;
use ManiaControl\Callbacks\Structures\TrackMania\OnWayPointEventStructure;
use ManiaControl\ManiaControl;

/**
 * Class handling and parsing TrackMania Callbacks
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class TrackManiaCallbacks implements CallbackListener {
	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;


	/**
	 * Create a new TrackMania Callbacks Instance
	 *
	 * @param ManiaControl    $maniaControl
	 * @param CallbackManager $callbackManager
	 */
	public function __construct(ManiaControl $maniaControl, CallbackManager $callbackManager) {
		$this->maniaControl = $maniaControl;

		// Register for script callbacks
		$callbackManager->registerCallbackListener(Callbacks::SCRIPTCALLBACK, $this, 'handleScriptCallbacks');
		$callbackManager->registerCallbackListener(Callbacks::TM_ONWAYPOINT, $this, 'handleWayPointCallback');
	}

	/**
	 * Handle Script Callbacks
	 *
	 * @param string $name
	 * @param mixed  $data
	 */
	public function handleScriptCallbacks($name, $data) {
		if (!$this->maniaControl->getCallbackManager()->callbackListeningExists($name)) {
			return; //Leave that disabled while testing/implementing Callbacks
		}
		switch ($name) {
			case Callbacks::TM_SCORES:
				$this->maniaControl->getCallbackManager()->triggerCallback($name, new OnScoresStructure($this->maniaControl, $data));
				break;
			case Callbacks::TM_ONEVENTDEFAULT:
				$this->maniaControl->getCallbackManager()->triggerCallback($name, new OnDefaultEventStructure($this->maniaControl, $data));
				break;
			case Callbacks::TM_ONEVENTSTARTLINE:
				$this->maniaControl->getCallbackManager()->triggerCallback($name, new OnStartLineEventStructure($this->maniaControl, $data));
				break;
			case Callbacks::TM_ONCOMMAND:
				$this->maniaControl->getCallbackManager()->triggerCallback($name, new OnCommandStructure($this->maniaControl, $data));
				break;
			case Callbacks::TM_ONPLAYERADDED:
			case Callbacks::TM_ONPLAYERREMOVED:
			case Callbacks::TM_ONGIVEUP:
			case Callbacks::TM_ONSTARTCOUNTDOWN:
				$this->maniaControl->getCallbackManager()->triggerCallback($name, new BasePlayerTimeStructure($this->maniaControl, $data));
				break;
			case Callbacks::TM_ONWAYPOINT:
				$this->maniaControl->getCallbackManager()->triggerCallback($name, new OnWayPointEventStructure($this->maniaControl, $data));
				break;
			case Callbacks::TM_ONRESPAWN:
				$this->maniaControl->getCallbackManager()->triggerCallback($name, new OnRespawnStructure($this->maniaControl, $data));
				break;
			case Callbacks::TM_ONSTUNT:
				$this->maniaControl->getCallbackManager()->triggerCallback($name, new OnStuntEventStructure($this->maniaControl, $data));
				break;
			case Callbacks::TM_WARMUPSTART:
			case Callbacks::TM_WARMUPEND:
				$this->maniaControl->getCallbackManager()->triggerCallback($name);
				break;
			case Callbacks::TM_WARMUPSTARTROUND:
			case Callbacks::TM_WARMUPENDROUND:
				$this->maniaControl->getCallbackManager()->triggerCallback($name, new OnWarmupStartEndRoundStructure($this->maniaControl, $data));
				break;
			case Callbacks::TM_POINTSREPARTITION:
				$this->maniaControl->getCallbackManager()->triggerCallback($name, new OnPointsRepartitionStructure($this->maniaControl, $data));
				break;
			case Callbacks::TM_UIPROPERTIES:
				$this->maniaControl->getCallbackManager()->triggerCallback($name, new UIPropertiesBaseStructure($this->maniaControl, $data));
				break;
		}
	}

	/**
	 * Trigger the three different Types of Callbacks
	 *
	 * @param \ManiaControl\Callbacks\Structures\TrackMania\OnWayPointEventStructure $structure
	 */
	public function handleWayPointCallback(OnWayPointEventStructure $structure) {
		if ($structure->getIsEndRace()) {
			$this->maniaControl->getCallbackManager()->addAdhocCallback(Callbacks::TM_ONFINISHLINE, $structure);
		} else if ($structure->getIsEndLap()) {
			$this->maniaControl->getCallbackManager()->addAdhocCallback(Callbacks::TM_ONLAPFINISH, $structure);
		}
	}
}
