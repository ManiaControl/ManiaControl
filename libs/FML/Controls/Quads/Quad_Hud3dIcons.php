<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad class for 'Hud3dIcons' styles
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Quad_Hud3dIcons extends Quad
{

    /*
     * Constants
     */
    const STYLE                  = 'Hud3dIcons';
    const SUBSTYLE_Cross         = 'Cross';
    const SUBSTYLE_CrossTargeted = 'CrossTargeted';
    const SUBSTYLE_Player1       = 'Player1';
    const SUBSTYLE_Player2       = 'Player2';
    const SUBSTYLE_Player3       = 'Player3';
    const SUBSTYLE_PointA        = 'PointA';
    const SUBSTYLE_PointB        = 'PointB';
    const SUBSTYLE_PointC        = 'PointC';

    /**
     * @var string $style Style
     */
    protected $style = self::STYLE;

}
