<?php

namespace FML\Stylesheet;

// Warning: The mood class isn't fully supported yet!
// Missing attributes: LDir1..

/**
 * Class representing a Stylesheets Mood
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Mood {
	/*
	 * Protected Properties
	 */
	protected $tagName = 'mood';
	protected $lAmbient_LinearRgb = '';
	protected $cloudsRgbMinLinear = '';
	protected $cloudsRgbMaxLinear = '';
	protected $lDir0_LinearRgb = '';
	protected $lDir0_Intens = 1.;
	protected $lDir0_DirPhi = 0.;
	protected $lDir0_DirTheta = 0.;
	protected $lBall_LinearRgb = '';
	protected $lBall_Intensity = 1.;
	protected $lBall_Radius = 0.;
	protected $fogColorSrgb = '';
	protected $selfIllumColor = '';
	protected $skyGradientV_Scale = 1.;
	protected $skyGradientKeys = array();

	/**
	 * Create a new Mood Object
	 *
	 * @return \FML\Elements\Mood
	 */
	public static function create() {
		$mood = new Mood();
		return $mood;
	}

	/**
	 * Construct a new Mood Object
	 */
	public function __construct() {
	}

	/**
	 * Set Ambient Color in which the Elements reflect the Light
	 *
	 * @param float $red Red Color Value
	 * @param float $green Green Color Value
	 * @param float $blue Blue Color Value
	 * @return \FML\Stylesheet\Mood
	 */
	public function setLightAmbientColor($red, $green, $blue) {
		$red = (float) $red;
		$green = (float) $green;
		$blue = (float) $blue;
		$this->lAmbient_LinearRgb = "{$red} {$green} {$blue}";
		return $this;
	}

	/**
	 * Set Minimum Value for the Background Color Range
	 *
	 * @param float $red Red Color Value
	 * @param float $green Green Color Value
	 * @param float $blue Blue Color Value
	 * @return \FML\Stylesheet\Mood
	 */
	public function setCloudsColorMin($red, $green, $blue) {
		$red = (float) $red;
		$green = (float) $green;
		$blue = (float) $blue;
		$this->cloudsRgbMinLinear = "{$red} {$green} {$blue}";
		return $this;
	}

	/**
	 * Set Maximum Value for the Background Color Range
	 *
	 * @param float $red Red Color Value
	 * @param float $green Green Color Value
	 * @param float $blue Blue Color Value
	 * @return \FML\Stylesheet\Mood
	 */
	public function setCloudsColorMax($red, $green, $blue) {
		$red = (float) $red;
		$green = (float) $green;
		$blue = (float) $blue;
		$this->cloudsRgbMaxLinear = "{$red} {$green} {$blue}";
		return $this;
	}

	/**
	 * Set RGB Color of Light Source 0
	 *
	 * @param float $red Red Color Value
	 * @param float $green Green Color Value
	 * @param float $blue Blue Color Value
	 * @return \FML\Stylesheet\Mood
	 */
	public function setLight0Color($red, $green, $blue) {
		$red = (float) $red;
		$green = (float) $green;
		$blue = (float) $blue;
		$this->lDir0_LinearRgb = "{$red} {$green} {$blue}";
		return $this;
	}

	/**
	 * Set Intensity of Light Source 0
	 *
	 * @param float $intensity Light Intensity
	 * @return \FML\Stylesheet\Mood
	 */
	public function setLight0Intensity($intensity) {
		$this->lDir0_Intens = (float) $intensity;
		return $this;
	}

	/**
	 * Set Phi-Angle of Light Source 0
	 *
	 * @param float $phiAngle Phi-Angle
	 * @return \FML\Stylesheet\Mood
	 */
	public function setLight0PhiAngle($phiAngle) {
		$this->lDir0_DirPhi = (float) $phiAngle;
		return $this;
	}

	/**
	 * Set Theta-Angle of Light Source 0
	 * 
	 * @param float $thetaAngle Theta-Angle
	 * @return \FML\Stylesheet\Mood
	 */
	public function setLight0ThetaAngle($thetaAngle) {
		$this->lDir0_DirTheta = (float) $thetaAngle;
		return $this;
	}

	/**
	 * Set Light Ball Color
	 *
	 * @param float $red Red Color Value
	 * @param float $green Green Color Value
	 * @param float $blue Blue Color Value
	 * @return \FML\Stylesheet\Mood
	 */
	public function setLightBallColor($red, $green, $blue) {
		$red = (float) $red;
		$green = (float) $green;
		$blue = (float) $blue;
		$this->lBall_LinearRgb = "{$red} {$green} {$blue}";
		return $this;
	}

	/**
	 * Set Light Ball Intensity
	 *
	 * @param float $intensity Light Ball Intensity
	 * @return \FML\Stylesheet\Mood
	 */
	public function setLightBallIntensity($intensity) {
		$this->lBall_Intens = (float) $intensity;
		return $this;
	}

	/**
	 * Set Light Ball Radius
	 *
	 * @param float $radius Light Ball Radius
	 * @return \FML\Stylesheet\Mood
	 */
	public function setLightBallRadius($radius) {
		$this->lBall_Radius = (float) $radius;
		return $this;
	}

	/**
	 * Set Fog Color
	 *
	 * @param float $red Red Color Value
	 * @param float $green Green Color Value
	 * @param float $blue Blue Color Value
	 * @return \FML\Stylesheet\Mood
	 */
	public function setFogColor($red, $green, $blue) {
		$red = (float) $red;
		$green = (float) $green;
		$blue = (float) $blue;
		$this->fogColorSrgb = "{$red} {$green} {$blue}";
		return $this;
	}

	/**
	 * Set Self Illumination Color
	 *
	 * @param float $red Red Color Value
	 * @param float $green Green Color Value
	 * @param float $blue Blue Color Value
	 * @return \FML\Stylesheet\Mood
	 */
	public function setSelfIllumColor($red, $green, $blue) {
		$red = (float) $red;
		$green = (float) $green;
		$blue = (float) $blue;
		$this->selfIllumColor = "{$red} {$green} {$blue}";
		return $this;
	}

	/**
	 * Set Sky Gradient Scale
	 *
	 * @param float $vScale Gradient Scale Scale
	 * @return \FML\Stylesheet\Mood
	 */
	public function setSkyGradientScale($scale) {
		$this->skyGradientV_Scale = (float) $scale;
		return $this;
	}

	/**
	 * Add a Key for the SkyGradient
	 *
	 * @param float $x Scale Value
	 * @param string $color Gradient Color
	 * @return \FML\Stylesheet\Mood
	 */
	public function addSkyGradientKey($x, $color) {
		$x = (float) $x;
		$color = (string) $color;
		$gradientKey = array('x' => $x, 'color' => $color);
		array_push($this->skyGradientKeys, $gradientKey);
		return $this;
	}

	/**
	 * Remove all SkyGradient Keys
	 *
	 * @return \FML\Stylesheet\Mood
	 */
	public function removeSkyGradientKeys() {
		$this->skyGradientKeys = array();
		return $this;
	}

	/**
	 * Render the Mood XML Element
	 *
	 * @param \DOMDocument $domDocument DomDocument for which the Mood XML Element should be rendered
	 * @return \DOMElement
	 */
	public function render(\DOMDocument $domDocument) {
		$moodXml = $domDocument->createElement($this->tagName);
		if ($this->lAmbient_LinearRgb) {
			$moodXml->setAttribute('LAmbient_LinearRgb', $this->lAmbient_LinearRgb);
		}
		if ($this->cloudsRgbMinLinear) {
			$moodXml->setAttribute('CloudsRgbMinLinear', $this->cloudsRgbMinLinear);
		}
		if ($this->cloudsRgbMaxLinear) {
			$moodXml->setAttribute('CloudsRgbMaxLinear', $this->cloudsRgbMaxLinear);
		}
		if ($this->lDir0_LinearRgb) {
			$moodXml->setAttribute('LDir0_LinearRgb', $this->lDir0_LinearRgb);
		}
		if ($this->lDir0_Intens) {
			$moodXml->setAttribute('LDir0_Intens', $this->lDir0_Intens);
		}
		if ($this->lDir0_DirPhi) {
			$moodXml->setAttribute('LDir0_DirPhi', $this->lDir0_DirPhi);
		}
		if ($this->lDir0_DirTheta) {
			$moodXml->setAttribute('LDir0_DirTheta', $this->lDir0_DirTheta);
		}
		if ($this->lBall_LinearRgb) {
			$moodXml->setAttribute('LBall_LinearRgb', $this->lBall_LinearRgb);
		}
		if ($this->lBall_Intens) {
			$moodXml->setAttribute('LBall_Intens', $this->lBall_Intens);
		}
		if ($this->lBall_Radius) {
			$moodXml->setAttribute('LBall_Radius', $this->lBall_Radius);
		}
		if ($this->fogColorSrgb) {
			$moodXml->setAttribute('FogColorSrgb', $this->fogColorSrgb);
		}
		if ($this->selfIllumColor) {
			$moodXml->setAttribute('SelfIllumColor', $this->selfIllumColor);
		}
		if ($this->skyGradientV_Scale) {
			$moodXml->setAttribute('SkyGradientV_Scale', $this->skyGradientV_Scale);
		}
		if ($this->skyGradientKeys) {
			$skyGradientXml = $domDocument->createElement('skygradient');
			$moodXml->appendChild($skyGradientXml);
			foreach ($this->skyGradientKeys as $gradientKey) {
				$keyXml = $domDocument->createElement('key');
				$skyGradientXml->appendChild($keyXml);
				$keyXml->setAttribute('x', $gradientKey['x']);
				$keyXml->setAttribute('color', $gradientKey['color']);
			}
		}
		return $moodXml;
	}
}
