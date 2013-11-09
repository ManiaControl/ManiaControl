<?php

namespace mControl;

/**
 * mControl Karma Plugin
 *
 * @author : steeffeen
 */
class Plugin_Karma {
	/**
	 * Constants
	 */
	const VERSION = '1.0';
	const MLID_KARMA = 'KarmaPlugin.MLID';
	const TABLE_KARMA = 'ic_karma';

	/**
	 * Private properties
	 */
	private $mControl = null;

	private $config = null;

	private $sendManialinkRequested = -1;

	/**
	 * Construct plugin
	 *
	 * @param object $mControl        	
	 */
	public function __construct($mControl) {
		$this->mControl = $mControl;
		
		// Load config
		$this->config = Tools::loadConfig('karma.plugin.xml');
		if (!Tools::toBool($this->config->enabled)) return;
		
		// Init database
		$this->initDatabase();
		
		// Register for callbacks
		$this->iControl->callbacks->registerCallbackHandler(Callbacks::CB_IC_ONINIT, $this, 'handleOnInitCallback');
		$this->iControl->callbacks->registerCallbackHandler(Callbacks::CB_IC_BEGINMAP, $this, 'handleBeginMapCallback');
		$this->iControl->callbacks->registerCallbackHandler(Callbacks::CB_MP_PLAYERCONNECT, $this, 'handlePlayerConnectCallback');
		$this->iControl->callbacks->registerCallbackHandler(Callbacks::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 
				'handleManialinkPageAnswerCallback');
		
		error_log('Karma Pugin v' . self::VERSION . ' ready!');
	}

	/**
	 * Repetitive actions
	 */
	public function loop() {
		if ($this->sendManialinkRequested > 0 && $this->sendManialinkRequested <= time()) {
			$this->sendManialinkRequested = -1;
			
			// Send manialink to all players
			$players = $this->iControl->server->getPlayers();
			foreach ($players as $player) {
				$login = $player['Login'];
				$manialink = $this->buildManialink($login);
				if (!$manialink) {
					// Skip and retry
					$this->sendManialinkRequested = time() + 5;
					continue;
				}
				Tools::sendManialinkPage($this->iControl->client, $manialink->asXml(), $login);
			}
		}
	}

	/**
	 * Handle OnInit mControl callback
	 *
	 * @param array $callback        	
	 */
	public function handleOnInitCallback($callback) {
		// Send manialink to all players once
		$this->sendManialinkRequested = time() + 3;
	}

	/**
	 * Handle mControl BeginMap callback
	 *
	 * @param array $callback        	
	 */
	public function handleBeginMapCallback($callback) {
		// Send manialink to all players once
		$this->sendManialinkRequested = time() + 2;
	}

	/**
	 * Handle PlayerConnect callback
	 *
	 * @param array $callback        	
	 */
	public function handlePlayerConnectCallback($callback) {
		$login = $callback[1][0];
		$manialink = $this->buildManialink($login);
		if (!$manialink) return;
		Tools::sendManialinkPage($this->iControl->client, $manialink->asXml(), $login);
	}

	/**
	 * Create necessary tables
	 */
	private function initDatabase() {
		$query = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_KARMA . "` (
			`index` int(11) NOT NULL AUTO_INCREMENT,
			`mapIndex` int(11) NOT NULL,
			`playerIndex` int(11) NOT NULL,
			`vote` tinyint(1) NOT NULL,
			`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (`index`),
			UNIQUE KEY `player_map_vote` (`mapIndex`, `playerIndex`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Save players map votes' AUTO_INCREMENT=1;";
		$result = $this->iControl->database->query($query);
		if ($this->iControl->database->mysqli->error) {
			trigger_error('MySQL Error on creating karma table. ' . $this->iControl->database->mysqli->error, E_USER_ERROR);
		}
	}

	/**
	 * Handle ManialinkPageAnswer callback
	 *
	 * @param array $callback        	
	 */
	public function handleManialinkPageAnswerCallback($callback) {
		$action = $callback[1][2];
		if (substr($action, 0, strlen(self::MLID_KARMA)) !== self::MLID_KARMA) return;
		
		// Get vote
		$action = substr($action, -4);
		$vote = null;
		switch ($action) {
			case '.pos':
				{
					$vote = 1;
					break;
				}
			case '.neu':
				{
					$vote = 0;
					break;
				}
			case '.neg':
				{
					$vote = -1;
					break;
				}
			default:
				{
					return;
				}
		}
		
		// Save vote
		$login = $callback[1][1];
		$playerIndex = $this->iControl->database->getPlayerIndex($login);
		$map = $this->iControl->server->getMap();
		$mapIndex = $this->iControl->database->getMapIndex($map['UId']);
		$query = "INSERT INTO `" . self::TABLE_KARMA . "` (
			`mapIndex`,
			`playerIndex`,
			`vote`
			) VALUES (
			" . $mapIndex . ",
			" . $playerIndex . ",
			" . $vote . "
			) ON DUPLICATE KEY UPDATE
			`vote` = VALUES(`vote`);";
		$result = $this->iControl->database->query($query);
		if (!$result) return;
		
		// Send success message
		$this->iControl->chat->sendSuccess('Vote successfully updated!', $login);
		
		// Send updated manialink
		$this->sendManialinkRequested = time() + 1;
	}

	/**
	 * Build karma voting manialink xml for the given login
	 */
	private function buildManialink($login) {
		// Get config
		$title = (string) $this->config->title;
		$pos_x = (float) $this->config->pos_x;
		$pos_y = (float) $this->config->pos_y;
		
		$mysqli = $this->iControl->database->mysqli;
		
		// Get indezes
		$playerIndex = $this->iControl->database->getPlayerIndex($login);
		if ($playerIndex === null) return null;
		$map = $this->iControl->server->getMap();
		if (!$map) return null;
		$mapIndex = $this->iControl->database->getMapIndex($map['UId']);
		if ($mapIndex === null) return null;
		
		// Get votings
		$query = "SELECT
			(SELECT `vote` FROM `" .
				 self::TABLE_KARMA . "` WHERE `mapIndex` = " . $mapIndex . " AND `playerIndex` = " . $playerIndex . ") as `playerVote`,
			(SELECT COUNT(`vote`) FROM `" .
				 self::TABLE_KARMA . "` WHERE `mapIndex` = " . $mapIndex . " AND `vote` = 1) AS `positiveVotes`,
			(SELECT COUNT(`vote`) FROM `" .
				 self::TABLE_KARMA . "` WHERE `mapIndex` = " . $mapIndex . " AND `vote` = 0) AS `neutralVotes`,
			(SELECT COUNT(`vote`) FROM `" .
				 self::TABLE_KARMA . "` WHERE `mapIndex` = " . $mapIndex . " AND `vote` = -1) AS `negativeVotes`
			FROM `" . self::TABLE_KARMA . "`;";
		$result = $mysqli->query($query);
		if ($mysqli->error) {
			trigger_error('MySQL ERROR: ' . $mysqli->error);
		}
		$votes = $result->fetch_assoc();
		if (!$votes) {
			$votes = array('playerVote' => null, 'positiveVotes' => 0, 'neutralVotes' => 0, 'negativeVotes' => 0);
		}
		
		// Build manialink
		$xml = Tools::newManialinkXml(self::MLID_KARMA);
		
		$frameXml = $xml->addChild('frame');
		$frameXml->addAttribute('posn', $pos_x . ' ' . $pos_y);
		
		// Title
		$labelXml = $frameXml->addChild('label');
		Tools::addAlignment($labelXml);
		$labelXml->addAttribute('posn', '0 4.5 -1');
		$labelXml->addAttribute('sizen', '22 0');
		$labelXml->addAttribute('style', 'TextTitle1');
		$labelXml->addAttribute('textsize', '1');
		$labelXml->addAttribute('text', $title);
		
		// Background
		$quadXml = $frameXml->addChild('quad');
		Tools::addAlignment($quadXml);
		$quadXml->addAttribute('sizen', '23 15 -2');
		$quadXml->addAttribute('style', 'Bgs1InRace');
		$quadXml->addAttribute('substyle', 'BgTitleShadow');
		
		// Votes
		for ($i = 1; $i >= -1; $i--) {
			$x = $i * 7.;
			
			// Vote button
			$quadXml = $frameXml->addChild('quad');
			Tools::addAlignment($quadXml);
			$quadXml->addAttribute('posn', $x . ' 0 0');
			$quadXml->addAttribute('sizen', '6 6');
			$quadXml->addAttribute('style', 'Icons64x64_1');
			
			// Vote count
			$labelXml = $frameXml->addChild('label');
			Tools::addAlignment($labelXml);
			$labelXml->addAttribute('posn', $x . ' -4.5 0');
			$labelXml->addAttribute('style', 'TextTitle1');
			$labelXml->addAttribute('textsize', '2');
			
			if ((string) $i === $votes['playerVote']) {
				// Player vote X
				$voteQuadXml = $frameXml->addChild('quad');
				Tools::addAlignment($voteQuadXml);
				$voteQuadXml->addAttribute('posn', $x . ' 0 1');
				$voteQuadXml->addAttribute('sizen', '6 6');
				$voteQuadXml->addAttribute('style', 'Icons64x64_1');
				$voteQuadXml->addAttribute('substyle', 'Close');
			}
			
			switch ($i) {
				case 1:
					{
						// Positive
						$quadXml->addAttribute('substyle', 'LvlGreen');
						$quadXml->addAttribute('action', self::MLID_KARMA . '.pos');
						$labelXml->addAttribute('text', $votes['positiveVotes']);
						break;
					}
				case 0:
					{
						// Neutral
						$quadXml->addAttribute('substyle', 'LvlYellow');
						$quadXml->addAttribute('action', self::MLID_KARMA . '.neu');
						$labelXml->addAttribute('text', $votes['neutralVotes']);
						break;
					}
				case -1:
					{
						// Negative
						$quadXml->addAttribute('substyle', 'LvlRed');
						$quadXml->addAttribute('action', self::MLID_KARMA . '.neg');
						$labelXml->addAttribute('text', $votes['negativeVotes']);
						break;
					}
			}
		}
		
		return $xml;
	}
}

?>
