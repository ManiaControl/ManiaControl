<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad class for 'MedalsBig' styles
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Quad_MedalsBig extends Quad
{

    /*
     * Constants
     */
    const STYLE                          = 'MedalsBig';
    const SUBSTYLE_MedalBronze           = 'MedalBronze';
    const SUBSTYLE_MedalGold             = 'MedalGold';
    const SUBSTYLE_MedalGoldPerspective  = 'MedalGoldPerspective';
    const SUBSTYLE_MedalNadeo            = 'MedalNadeo';
    const SUBSTYLE_MedalNadeoPerspective = 'MedalNadeoPerspective';
    const SUBSTYLE_MedalSilver           = 'MedalSilver';
    const SUBSTYLE_MedalSlot             = 'MedalSlot';

    /**
     * @var string $style Style
     */
    protected $style = self::STYLE;

}
