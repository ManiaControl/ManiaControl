<?php

namespace ManiaControl\Server;

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Callbacks\Callbacks;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\ManiaControl;
use ManiaControl\Maps\Map;
use Maniaplanet\DedicatedServer\Xmlrpc\GameModeException;

//TODO delete this class
/**
 * Class managing Rankings
 *
 * @deprecated
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class RankingManager implements CallbackListener, UsageInformationAble {
	use UsageInformationTrait;
	
	/*
	 * Private properties
	 */
	private $rankings = array();

	/** @var ManiaControl $maniaControl */
	private $maniaControl;

	/**
	 * Construct a new ranking manager instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Callbacks
		$this->maniaControl->getCallbackManager()->registerCallbackListener(CallbackManager::CB_MP_MODESCRIPTCALLBACK, $this, 'handleCallbacks');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(CallbackManager::CB_MP_MODESCRIPTCALLBACKARRAY, $this, 'handleCallbacks');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::ONINIT, $this, 'onInit');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::BEGINMAP, $this, 'handleBeginMap');
		//TODO won message at end of the map (disable as setting) (and public announce only all %50 (setting) players)
	}

	/**
	 * Initialize the Rankings (never call this Method)
	 */
	public function onInit() {
		try {
			$this->maniaControl->getClient()->triggerModeScriptEvent('LibXmlRpc_GetRankings', '');
		} catch (GameModeException $e) {
		}
	}

	/**
	 * Handle stats on callbacks (never call this Method)
	 *
	 * @param array $callback
	 */
	public function handleCallbacks(array $callback) {
		$callbackName = $callback[1][0];

		//TODO not tested in TrackMania
		switch ($callbackName) {
			case 'updateRankings':
				$this->updateRankings($callback[1][1][0]);
				break;
			case 'endRound':
			case 'beginRound':
			case 'endMap':
			case 'endMap1':
				$this->updateRankings($callback[1]);
				break;
		}
	}

	/**
	 * Clear the rankings on the Begin of a Map
	 *
	 * @param Map $map
	 */
	public function handleBeginMap(Map $map) {
		$this->rankings = array();
	}

	/**
	 * Update Game Rankings (never call this Method)
	 *
	 * @param string $data
	 */
	public function updateRankings($data) {
		if (!is_string($data)) {
			return;
		}

		//TODO in legacy mode, no data is in parameter -> fetch via method getCurrentRanking

		$scores = explode(';', $data);
		foreach ($scores as $player) {
			if (strpos($player, ':') !== false) {
				$tmp                     = explode(':', $player);
				$this->rankings[$tmp[0]] = $tmp[1];
			}
		}
		array_multisort($this->rankings, SORT_DESC, SORT_NUMERIC);

		//TODO if Local Records activated-> sort asc
		//$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::RANKINGSUPDATED, $this->getRankings());
	}

	/**
	 * Get Rankings
	 *
	 * @return array
	 */
	public function getRankings() {
		return $this->rankings;
	}

	/**
	 * Get the Current Leading Players (as Login Array)
	 *
	 * @return array|null
	 */
	public function getLeaders() {
		$leaders = array();
		$prev    = -1;
		foreach ($this->rankings as $score) {
			if ($prev !== -1 && $prev < $score) {
				return $leaders;
			}
			// FIXME: $leader doesn't exist
			//array_push($leaders, $leader);
			$prev = $score;
		}
		return null;
	}

	public function getPlayerRanking() {
		//TODO complete this
	}
}
