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
use Maniaplanet\DedicatedServer\Xmlrpc\Exception;

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
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		//Register Callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_MODESCRIPTCALLBACK, $this, 'handleCallbacks');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_MODESCRIPTCALLBACKARRAY, $this, 'handleCallbacks');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_ONINIT, $this, 'onInit');
		//TODO won message at end of the map (disable as setting) (and public announce only all %50 (setting) players)
	}

	/**
	 *    Initialize the Rankings
	 */
	public function onInit() {
		try {
			$this->maniaControl->client->triggerModeScriptEvent('LibXmlRpc_GetRankings', '');
		} catch(Exception $e) {
			if ($e->getMessage() == 'Not in script mode.') {
				//	do nothing
				return;
			}
			throw $e;
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
		if (!is_string($data)) {
			return;
		}

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