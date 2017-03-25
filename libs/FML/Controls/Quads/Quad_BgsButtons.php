<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad class for 'BgsButtons' styles
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Quad_BgsButtons extends Quad
{

    /*
     * Constants
     */
    const STYLE                           = 'BgsButtons';
    const SUBSTYLE_BgButtonLarge          = 'BgButtonLarge';
    const SUBSTYLE_BgButtonMedium         = 'BgButtonMedium';
    const SUBSTYLE_BgButtonMediumSelector = 'BgButtonMediumSelector';
    const SUBSTYLE_BgButtonMediumSpecial  = 'BgButtonMediumSpecial';
    const SUBSTYLE_BgButtonSmall          = 'BgButtonSmall';
    const SUBSTYLE_BgButtonSmall2         = 'BgButtonSmall2';
    const SUBSTYLE_BgButtonXSmall         = 'BgButtonXSmall';

    /**
     * @var string $style Style
     */
    protected $style = self::STYLE;

}
