<?php
/**
 * Class managing Rankings
 *
 * @author steeffeen & kremsy
 */
namespace ManiaControl\Server;


use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\ManiaControl;

class RankingManager implements CallbackListener {
	/**
	 * Private Properties
	 */
	private $rankings = array();

	/**
	 * @return mixed
	 */
	public function getRankings() {
		return $this->rankings;
	}

	/**
	 * Construct player manager
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) { //TODO statistic wins
		$this->maniaControl = $maniaControl;

		//Register Callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_MODESCRIPTCALLBACK, $this, 'handleCallbacks');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_MODESCRIPTCALLBACKARRAY, $this, 'handleCallbacks');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_ONINIT, $this, 'onInit');
		//TODO won message at end of the map (disable as setting)
	}

	/**
	 *    Initialize the Rankings
	 */
	public function onInit() {
		try {
			$this->maniaControl->client->triggerModeScriptEvent('LibXmlRpc_GetRankings', '');
		} catch(\Exception $e) {
			//do nothing
		}
	}


	/**
	 * Handle stats on callbacks
	 *
	 * @param array $callback
	 */
	public function handleCallbacks(array $callback) {
		$callbackName = $callback[1][0];

		//TODO not tested in TrackMania
		switch($callbackName) {
			case 'LibXmlRpc_Rankings':
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
	 * Update Game Rankings
	 *
	 * @param $data
	 */
	private function updateRankings($data) {
		$scores = explode(';', $data);
		foreach($scores as $player) {
			if (strpos($player, ':') !== false) {
				$tmp                     = explode(':', $player);
				$this->rankings[$tmp[0]] = $tmp[1];
			}
		}
		array_multisort($this->rankings, SORT_DESC, SORT_NUMERIC);

		//TODO if Local Records activated-> sort asc
	}

	/**
	 * Get the Current Leading Players (as Login Array)
	 *
	 * @return array|null
	 */
	public function getLeaders() {
		$leaders = array();
		$prev    = -1;
		foreach($this->rankings as $score) {
			if ($prev != -1 && $prev < $score) {
				return $leaders;
			}
			array_push($leaders, $leader);
			$prev = $score;
		}
		return null;
	}
} 