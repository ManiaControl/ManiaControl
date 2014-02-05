<?php

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Plugins\Plugin;
use ManiaControl\Statistics\StatisticCollector;
use ManiaControl\Statistics\StatisticManager;
use Maniaplanet\DedicatedServer\Structures\AbstractStructure;

class InstagibRankingPlugin implements Plugin, CallbackListener {
	/**
	 * Constants
	 */
	const PLUGIN_ID                      = 11;
	const PLUGIN_VERSION                 = 0.1;
	const PLUGIN_NAME                    = 'InstagibRankingPlugin';
	const PLUGIN_AUTHOR                  = 'kremsy';
	const TABLE_RANK                     = 'mc_rank';
	const SETTING_MIN_HITS_RATIO_RANKING = 'Min Hits on Ratio Rankings';

	/**
	 * Private Properties
	 */
	/** @var maniaControl $maniaControl * */
	private $maniaControl = null;
	private $recordCount = 0;

	/**
	 * Prepares the Plugin
	 *
	 * @param ManiaControl $maniaControl
	 * @return mixed
	 */
	public static function prepare(ManiaControl $maniaControl) {
		//Todo
	}

	/**
	 * Load the plugin
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @return bool
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		return;
		$this->initTables();
		//TODO for all modes, rank by records, points or accuracy / kill-death
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERJOINED, $this, 'handlePlayerConnect');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_ENDMAP, $this, 'handleEndMap');

		$maniaControl->settingManager->initSetting($this, self::SETTING_MIN_HITS_RATIO_RANKING, 100);
	}

	/**
	 * Unload the plugin and its resources
	 */
	public function unload() {
		// TODO: Implement unload() method.
	}

	/**
	 * Get plugin id
	 *
	 * @return int
	 */
	public static function getId() {
		return self::PLUGIN_ID;
	}

	/**
	 * Get Plugin Name
	 *
	 * @return string
	 */
	public static function getName() {
		return self::PLUGIN_NAME;
	}

	/**
	 * Get Plugin Version
	 *
	 * @return float
	 */
	public static function getVersion() {
		return self::PLUGIN_VERSION;
	}

	/**
	 * Get Plugin Author
	 *
	 * @return string
	 */
	public static function getAuthor() {
		return self::PLUGIN_AUTHOR;
	}

	/**
	 * Get Plugin Description
	 *
	 * @return string
	 */
	public static function getDescription() {
		// TODO: Implement getDescription() method.
	}

	/**
	 * Create necessary database tables
	 */
	private function initTables() {
		$mysqli = $this->maniaControl->database->mysqli;
		$query  = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_RANK . "` (
		           `PlayerIndex` mediumint(9) NOT NULL default 0,
		           `Rank` mediumint(9) NOT NULL default 0,
		           `Avg` float NOT NULL default 0,
		           KEY `PlayerIndex` (`PlayerIndex`),
		           UNIQUE `Rank` (`Rank`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Mania Control Serverranking';";
		$mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error, E_USER_ERROR);
		}
	}

	/**
	 * Resets and rebuilds the Ranking
	 */
	private function resetRanks() {
		$mysqli = $this->maniaControl->database->mysqli;

		// Erase old Average Data
		$mysqli->query('TRUNCATE TABLE ' . self::TABLE_RANK);

		//TODO setting minrank, maxrecs

		//$mapCnt  = count($this->maniaControl->mapManager->getMaps());
		//TODO other modes, records and points

		$hits            = $this->maniaControl->statisticManager->getStatsRanking(StatisticCollector::STAT_ON_HIT);
		$killDeathRatios = $this->maniaControl->statisticManager->getStatsRanking(StatisticManager::SPECIAL_STAT_KD_RATIO);
		$accuracies      = $this->maniaControl->statisticManager->getStatsRanking(StatisticManager::SPECIAL_STAT_LASER_ACC);

		$minHits = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MIN_HITS_RATIO_RANKING);

		$ranks = array();
		foreach($killDeathRatios as $player => $killDeathRatio) {
			//TODO setting
			if ($hits[$player] < $minHits || !isset($accuracies[$player])) {
				continue;
			}
			$ranks[$player] = $killDeathRatio * $accuracies[$player];
		}

		arsort($ranks);
		//TODO order desc / asc

		if (empty($ranks)) {
			return;
		}

		$this->recordCount = count($ranks);

		//Compute each player's new average score
		$query = "INSERT INTO " . self::TABLE_RANK . " VALUES ";
		$i     = 1;

		foreach($ranks as $player => $rankValue) {
			$query .= '(' . $player . ',' . $i . ',' . $rankValue . '),';
			$i++;
		}
		$query = substr($query, 0, strlen($query) - 1); // strip trailing ','

		$mysqli->query($query);
	}

	/**
	 * Handle PlayerConnect callback
	 *
	 * @param array $callback
	 */
	public function handlePlayerConnect(array $callback) {
		$login  = $callback[1][0];
		$player = $this->maniaControl->playerManager->getPlayer($login);
		if (!$player) {
			return;
		}

		$this->showRank($player);
		$this->showNextRank($player);
	}

	/**
	 * Shows Ranks on endMap
	 *
	 * @param array $callback
	 */
	public function handleEndMap(array $callback) {
		$this->resetRanks();

		foreach($this->maniaControl->playerManager->getPlayers() as $player) {
			$this->showRank($player);
			$this->showNextRank($player);
		}
		//TODO cb on rank builded
	}

	/**
	 * Shows the serverRank to a certain Player
	 *
	 * @param Player $player
	 */
	public function showRank(Player $player) {
		$rankObj = $this->getRank($player);

		if ($rankObj != null) {
			$message = '$0f3Your Server rank is $<$ff3' . $rankObj->rank . '$> / $<$fff' . $this->recordCount . '$> Ratio: $fff' . round($rankObj->avg, 2);
		} else {
			$minHits = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MIN_HITS_RATIO_RANKING);
			$message = '$0f3 You must make $<$fff' . $minHits . '$> Hits on this server before recieving a rank...';
		}
		$this->maniaControl->chat->sendChat($message, $player->login);
	}

	/**
	 * Gets A Rank As Object with properties Avg PlayerIndex and Rank
	 *
	 * @param Player $player
	 * @return Rank $rank
	 */
	private function getRank(Player $player) {
		//TODO setting global from db or local
		$mysqli = $this->maniaControl->database->mysqli;

		$result = $mysqli->query('SELECT * FROM ' . self::TABLE_RANK . ' WHERE PlayerIndex=' . $player->index);
		if ($result->num_rows > 0) {
			$row = $result->fetch_array();
			$result->free_result();
			return Rank::fromArray($row);
		} else {
			$result->free_result();
			return null;
		}
	}

	/**
	 * Get the Next Ranked Player
	 *
	 * @param Player $player
	 * @return Rank
	 */
	private function getNextRank(Player $player) {
		$mysqli     = $this->maniaControl->database->mysqli;
		$rankObject = $this->getRank($player);
		$nextRank   = $rankObject->rank - 1;

		$result = $mysqli->query('SELECT * FROM ' . self::TABLE_RANK . ' WHERE Rank=' . $nextRank);
		if ($result->num_rows > 0) {
			$row = $result->fetch_array();
			$result->free_result();
			return Rank::fromArray($row);
		} else {
			$result->free_result();
			return null;
		}
	}


	//TODO chatcommand showrank

	/**
	 * Shows which Player is next ranked to you
	 *
	 * @param Player $player
	 */
	public function showNextRank(Player $player) {
		//TODO chatcommand
		$rankObject = $this->getRank($player);

		if ($rankObject != null) {
			if ($rankObject->rank > 1) {
				$nextRank   = $this->getNextRank($player);
				$nextPlayer = $this->maniaControl->playerManager->getPlayerByIndex($nextRank->playerIndex);
				$message    = '$0f3The next better ranked player is $fff' . $nextPlayer->nickname;
			} else {
				$message = '$0f3No better ranked player :-)';
			}
			$this->maniaControl->chat->sendChat($message, $player->login);
		}
	}
}

class Rank extends AbstractStructure {
	public $playerIndex;
	public $rank;
	public $avg;
}