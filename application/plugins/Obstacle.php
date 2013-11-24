<?php
use ManiaControl\ManiaControl;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Players\Player;
use ManiaControl\Plugins\Plugin;

/**
 * ManiaControl Obstacle Plugin
 *
 * @author steeffeen
 */
class ObstaclePlugin extends Plugin {
	/**
	 * Constants
	 */
	const CB_JUMPTO = 'Obstacle.JumpTo';
	const VERSION = '1.0';

	/**
	 * Create new obstacle plugin
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		
		// Plugin details
		$this->name = 'Obstacle Plugin';
		$this->version = self::VERSION;
		$this->author = 'steeffeen';
		$this->description = 'Plugin offering various Commands for the ShootMania Obstacle Game Mode.';
		
		// Register for jump command
		$this->maniaControl->commandManager->registerCommandListener('jumpto', $this, 'command_JumpTo');
	}

	/**
	 * Handle JumpTo command
	 *
	 * @param array $chatCallback        	
	 * @return bool
	 */
	public function command_JumpTo(array $chatCallback, Player $player) {
		// $rightLevel =
		if (!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_OPERATOR)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return false;
		}
		// Send jump callback
		$params = explode(' ', $chatCallback[1][2], 2);
		$param = $player->login . ";" . $params[1] . ";";
		if (!$this->maniaControl->client->query('TriggerModeScriptEvent', self::CB_JUMPTO, $param)) {
			trigger_error("Couldn't send jump callback for '{$player->login}'. " . $this->maniaControl->getClientErrorText());
		}
		return true;
	}
}

?>
