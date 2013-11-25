<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad class for style 'Icons64x64_1'
 *
 * @author steeffeen
 */
class Quad_Icons64x64_1 extends Quad {
	/**
	 * Constants
	 */
	const STYLE = 'Icons64x64_1';

	/**
	 * Construct Icons64x64_1 quad
	 */
	public function __construct() {
		parent::__construct();
		$this->setStyle(self::STYLE);
		array("3DStereo", "Add", "ArrowBlue", "ArrowDisabled", "ArrowDown", "ArrowFastNext", "ArrowFastPrev", "ArrowFirst", 
			"ArrowGreen", "ArrowLast", "ArrowNext", "ArrowPrev", "ArrowRed", "ArrowUp", "Browser", "Buddy", "ButtonLeagues", "Camera", 
			"CameraLocal", "Check", "ClipPause", "ClipPlay", "ClipRewind", "Close", "Empty", "Finish", "FinishGrey", "First", 
			"GenericButton", "Green", "IconLeaguesLadder", "IconPlayers", "IconPlayersLadder", "IconServers", "Inbox", "LvlGreen", 
			"LvlRed", "LvlYellow", "ManiaLinkNext", "ManiaLinkPrev", "Maximize", "MediaAudioDownloading", "MediaPlay", "MediaStop", 
			"MediaVideoDownloading", "NewMessage", "NotBuddy", "OfficialRace", "Opponents", "Outbox", "QuitRace", "RedHigh", "RedLow", 
			"Refresh", "RestartRace", "Save", "Second", "ShowDown", "ShowDown2", "ShowLeft", "ShowLeft2", "ShowRight", "ShowRight2", 
			"ShowUp", "ShowUp2", "SliderCursor", "SliderCursor2", "StateFavourite", "StatePrivate", "StateSuggested", "Sub", 
			"TagTypeBronze", "TagTypeGold", "TagTypeNadeo", "TagTypeNone", "TagTypeSilver", "Third", "ToolLeague1", "ToolRoot", 
			"ToolTree", "ToolUp", "TrackInfo", "TV", "YellowHigh", "YellowLow");
	}
}

?>
