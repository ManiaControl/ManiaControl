<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad class for style 'UIConstruction_Buttons'
 *
 * @author steeffeen
 */
class Quad_UIConstruction_Buttons extends Quad {
	/**
	 * Constants
	 */
	const STYLE = 'UIConstruction_Buttons';

	/**
	 * Construct UIConstruction_Buttons quad
	 */
	public function __construct() {
		parent::__construct();
		$this->setStyle(self::STYLE);
		array("ActionMaker", "Add", "Author", "AuthorTime", "BgEditors", "BgIcons", "BgMain", "BgTools", "BlockEditor", "Camera", 
			"Challenge", "CopyPaste", "DecalEditor", "Delete", "Directory", "Down", "Drive", "Erase", "Help", "Item", "Left", 
			"MacroBlockEditor", "MediaTracker", "ObjectEditor", "OffZone", "Options", "Paint", "Pick", "Plugins", "Quit", "Redo", 
			"Reload", "Right", "Save", "SaveAs", "ScriptEditor", "SpotModelClearUnused", "SpotModelDuplicate", "SpotModelNew", 
			"SpotModelRename", "Square", "Stats", "Sub", "TerrainEditor", "TestSm", "Text", "Tools", "Underground", "Undo", "Up", 
			"Validate", "Validate_Step1", "Validate_Step2", "Validate_Step3");
	}
}

?>
