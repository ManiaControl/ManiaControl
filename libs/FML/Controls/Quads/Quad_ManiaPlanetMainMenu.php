<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad class for 'ManiaPlanetMainMenu' styles
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Quad_ManiaPlanetMainMenu extends Quad
{

    /*
     * Constants
     */
    const STYLE                 = 'ManiaPlanetMainMenu';
    const SUBSTYLE_BottomBar    = 'BottomBar';
    const SUBSTYLE_Highlight    = 'Highlight';
    const SUBSTYLE_IconAdd      = 'IconAdd';
    const SUBSTYLE_IconHome     = 'IconHome';
    const SUBSTYLE_IconPlay     = 'IconPlay';
    const SUBSTYLE_IconQuit     = 'IconQuit';
    const SUBSTYLE_IconSettings = 'IconSettings';
    const SUBSTYLE_IconStore    = 'IconStore';
    const SUBSTYLE_MainBg       = 'MainBg';
    const SUBSTYLE_TitleBg      = 'TitleBg';
    const SUBSTYLE_TopBar       = 'TopBar';

    /**
     * @var string $style Style
     */
    protected $style = self::STYLE;

}
