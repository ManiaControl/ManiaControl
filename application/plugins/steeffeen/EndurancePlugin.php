<?php

namespace steeffeen;

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\Models\RecordCallback;
use ManiaControl\ManiaControl;
use ManiaControl\Plugins\Plugin;

/**
 * Plugin for the TM Game Mode 'Endurance' by TGYoshi
 *
 * @author steeffeen
 */
class EndurancePlugin implements CallbackListener, Plugin {
	/*
	 * Constants
	 */
	const ID            = 25;
	const VERSION       = 0.2;
	const NAME          = 'Endurance Plugin';
	const AUTHOR        = 'steeffeen';
	const CB_CHECKPOINT = 'Endurance.Checkpoint';

	/**
	 * Private Properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

	/**
	 * @see \ManiaControl\Plugins\Plugin::prepare()
	 */
	public static function prepare(ManiaControl $maniaControl) {
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getId()
	 */
	public static function getId() {
		return self::ID;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getName()
	 */
	public static function getName() {
		return self::NAME;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getVersion()
	 */
	public static function getVersion() {
		return self::VERSION;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getAuthor()
	 */
	public static function getAuthor() {
		return self::AUTHOR;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getDescription()
	 */
	public static function getDescription() {
		return "Plugin enabling Support for the TM Game Mode 'Endurance' by TGYoshi.";
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::load()
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Register for callbacks
		$this->maniaControl->callbackManager->registerScriptCallbackListener(self::CB_CHECKPOINT, $this, 'handleEnduranceCheckpointCallback');

		return true;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::unload()
	 */
	public function unload() {
	}

	/**
	 * Handle Endurance Checkpoint Callback
	 *
	 * @param array $callback
	 */
	public function handleEnduranceCheckpointCallback(array $callback) {
		$callbackData = json_decode($callback[1]);
		$player       = $this->maniaControl->playerManager->getPlayer($callbackData->Login);
		if (!$player) {
			// Invalid player
			return;
		}

		// Build callback
		$enduranceCallback              = new RecordCallback();
		$enduranceCallback->rawCallback = $callback;
		$enduranceCallback->setPlayer($player);
		$enduranceCallback->isEndLap      = $callbackData->EndLap;
		$enduranceCallback->isEndRace     = $callbackData->EndRace;
		$enduranceCallback->time          = $callbackData->Time;
		$enduranceCallback->lapTime       = $callbackData->LapTime;
		$enduranceCallback->checkpoint    = $callbackData->Checkpoint;
		$enduranceCallback->lapCheckpoint = $callbackData->CheckpointInLap;

		if ($enduranceCallback->isEndLap) {
			$enduranceCallback->name = $enduranceCallback::LAPFINISH;
		} else if (($enduranceCallback->isEndRace)) {
			$enduranceCallback->name = $enduranceCallback::FINISH;
		} else {
			$enduranceCallback->name = $enduranceCallback::CHECKPOINT;
		}

		$this->maniaControl->callbackManager->triggerCallback($enduranceCallback);
	}
}
