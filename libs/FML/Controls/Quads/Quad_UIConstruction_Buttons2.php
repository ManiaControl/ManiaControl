<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad class for 'UIConstruction_Buttons2' styles
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Quad_UIConstruction_Buttons2 extends Quad
{

    /*
     * Constants
     */
    const STYLE                = 'UIConstruction_Buttons2';
    const SUBSTYLE_AirMapping  = 'AirMapping';
    const SUBSTYLE_BlockEditor = 'BlockEditor';
    const SUBSTYLE_Copy        = 'Copy';
    const SUBSTYLE_Cut         = 'Cut';
    const SUBSTYLE_GhostBlocks = 'GhostBlocks';
    const SUBSTYLE_KeysAdd     = 'KeysAdd';
    const SUBSTYLE_KeysCopy    = 'KeysCopy';
    const SUBSTYLE_KeysDelete  = 'KeysDelete';
    const SUBSTYLE_KeysPaste   = 'KeysPaste';
    const SUBSTYLE_New         = 'New';
    const SUBSTYLE_Open        = 'Open';
    const SUBSTYLE_Symmetry    = 'Symmetry';

    /**
     * @var string $style Style
     */
    protected $style = self::STYLE;

}
