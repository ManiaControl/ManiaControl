<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad class for style 'Icons128x128_1'
 *
 * @author steeffeen
 */
class Quad_Icons128x128_1 extends Quad {
	/**
	 * Constants
	 */
	const STYLE = 'Icons128x128_1';

	/**
	 * Construct Icons128x128_1 quad
	 */
	public function __construct() {
		parent::__construct();
		$this->setStyle(self::STYLE);
		array("Advanced", "Back", "BackFocusable", "Beginner", "Browse", "Buddies", "Challenge", "ChallengeAuthor", "Coppers", 
			"Create", "Credits", "Custom", "CustomStars", "Default", "Download", "Easy", "Editor", "Event", "Extreme", "Forever", 
			"GhostEditor", "Hard", "Hotseat", "Inputs", "Invite", "LadderPoints", "Lan", "Launch", "Load", "LoadTrack", "Manialink", 
			"ManiaZones", "MedalCount", "MediaTracker", "Medium", "Multiplayer", "Nations", "NewTrack", "Options", "Padlock", "Paint", 
			"Platform", "PlayerPage", "Profile", "ProfileAdvanced", "ProfileVehicle", "Puzzle", "Quit", "Race", "Rankings", "Replay", 
			"Save", "ServersAll", "ServersFavorites", "ServersSuggested", "Share", "ShareBlink", "SkillPoints", "Solo", "Statistics", 
			"Stunts", "United", "Upload", "Vehicles");
	}
}

?>
