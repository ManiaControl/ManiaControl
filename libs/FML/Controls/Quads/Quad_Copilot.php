<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad class for 'Copilot' styles
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Quad_Copilot extends Quad
{

    /*
     * Constants
     */
    const STYLE               = 'Copilot';
    const SUBSTYLE_Down       = 'Down';
    const SUBSTYLE_DownGood   = 'DownGood';
    const SUBSTYLE_DownWrong  = 'DownWrong';
    const SUBSTYLE_Left       = 'Left';
    const SUBSTYLE_LeftGood   = 'LeftGood';
    const SUBSTYLE_LeftWrong  = 'LeftWrong';
    const SUBSTYLE_Right      = 'Right';
    const SUBSTYLE_RightGood  = 'RightGood';
    const SUBSTYLE_RightWrong = 'RightWrong';
    const SUBSTYLE_Up         = 'Up';
    const SUBSTYLE_UpGood     = 'UpGood';
    const SUBSTYLE_UpWrong    = 'UpWrong';

    /**
     * @var string $style Style
     */
    protected $style = self::STYLE;

}
