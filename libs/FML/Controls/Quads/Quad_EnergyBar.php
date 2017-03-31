<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad class for 'EnergyBar' styles
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Quad_EnergyBar extends Quad
{

    /*
     * Constants
     */
    const STYLE                     = 'EnergyBar';
    const SUBSTYLE_BgText           = 'BgText';
    const SUBSTYLE_EnergyBar        = 'EnergyBar';
    const SUBSTYLE_EnergyBar_0_25   = 'EnergyBar_0.25';
    const SUBSTYLE_EnergyBar_Thin   = 'EnergyBar_Thin';
    const SUBSTYLE_HeaderGaugeLeft  = 'HeaderGaugeLeft';
    const SUBSTYLE_HeaderGaugeRight = 'HeaderGaugeRight';

    /**
     * @var string $style Style
     */
    protected $style = self::STYLE;

}
