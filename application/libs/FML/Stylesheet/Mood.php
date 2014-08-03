<?php

namespace FML\Stylesheet;

	// Warning: The mood class isn't fully supported yet!
	// Missing attributes: LDir1..

/**
 * Class representing a Stylesheet Mood
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Mood {
	/*
	 * Protected properties
	 */
	protected $tagName = 'mood';
	protected $lAmbient_LinearRgb = null;
	protected $cloudsRgbMinLinear = null;
	protected $cloudsRgbMaxLinear = null;
	protected $lDir0_LinearRgb = null;
	protected $lDir0_Intens = 1.;
	protected $lDir0_DirPhi = 0.;
	protected $lDir0_DirTheta = 0.;
	protected $lBall_LinearRgb = null;
	protected $lBall_Intensity = 1.;
	protected $lBall_Radius = 0.;
	protected $fogColorSrgb = null;
	protected $selfIllumColor = null;
	protected $skyGradientV_Scale = 1.;
	protected $skyGradientKeys = array();

	/**
	 * Create a new Mood object
	 *
	 * @return static
	 */
	public static function create() {
		return new static();
	}

	/**
	 * Set ambient color in which the Elements reflect the light
	 *
	 * @param float $red   Red color value
	 * @param float $green Green color value
	 * @param float $blue  Blue color value
	 * @return static
	 */
	public function setLightAmbientColor($red, $green, $blue) {
		$this->lAmbient_LinearRgb = floatval($red) . ' ' . floatval($green) . ' ' . floatval($blue);
		return $this;
	}

	/**
	 * Set minimum value for the background color range
	 *
	 * @param float $red   Red color value
	 * @param float $green Green color value
	 * @param float $blue  Blue color value
	 * @return static
	 */
	public function setCloudsColorMin($red, $green, $blue) {
		$this->cloudsRgbMinLinear = floatval($red) . ' ' . floatval($green) . ' ' . floatval($blue);
		return $this;
	}

	/**
	 * Set maximum value for the background color range
	 *
	 * @param float $red   Red color value
	 * @param float $green Green color value
	 * @param float $blue  Blue color value
	 * @return static
	 */
	public function setCloudsColorMax($red, $green, $blue) {
		$this->cloudsRgbMaxLinear = floatval($red) . ' ' . floatval($green) . ' ' . floatval($blue);
		return $this;
	}

	/**
	 * Set RGB color of light source 0
	 *
	 * @param float $red   Red color value
	 * @param float $green Green color value
	 * @param float $blue  Blue color value
	 * @return static
	 */
	public function setLight0Color($red, $green, $blue) {
		$this->lDir0_LinearRgb = floatval($red) . ' ' . floatval($green) . ' ' . floatval($blue);
		return $this;
	}

	/**
	 * Set intensity of light source 0
	 *
	 * @param float $intensity Light intensity
	 * @return static
	 */
	public function setLight0Intensity($intensity) {
		$this->lDir0_Intens = (float)$intensity;
		return $this;
	}

	/**
	 * Set phi angle of light source 0
	 *
	 * @param float $phiAngle Phi angle
	 * @return static
	 */
	public function setLight0PhiAngle($phiAngle) {
		$this->lDir0_DirPhi = (float)$phiAngle;
		return $this;
	}

	/**
	 * Set theta angle of light source 0
	 *
	 * @param float $thetaAngle Theta angle
	 * @return static
	 */
	public function setLight0ThetaAngle($thetaAngle) {
		$this->lDir0_DirTheta = (float)$thetaAngle;
		return $this;
	}

	/**
	 * Set light ball color
	 *
	 * @param float $red   Red color value
	 * @param float $green Green color value
	 * @param float $blue  Blue color value
	 * @return static
	 */
	public function setLightBallColor($red, $green, $blue) {
		$this->lBall_LinearRgb = floatval($red) . ' ' . floatval($green) . ' ' . floatval($blue);
		return $this;
	}

	/**
	 * Set light ball intensity
	 *
	 * @param float $intensity Light ball intensity
	 * @return static
	 */
	public function setLightBallIntensity($intensity) {
		$this->lBall_Intensity = (float)$intensity;
		return $this;
	}

	/**
	 * Set light ball radius
	 *
	 * @param float $radius Light ball radius
	 * @return static
	 */
	public function setLightBallRadius($radius) {
		$this->lBall_Radius = (float)$radius;
		return $this;
	}

	/**
	 * Set fog color
	 *
	 * @param float $red   Red color value
	 * @param float $green Green color value
	 * @param float $blue  Blue color value
	 * @return static
	 */
	public function setFogColor($red, $green, $blue) {
		$this->fogColorSrgb = floatval($red) . ' ' . floatval($green) . ' ' . floatval($blue);
		return $this;
	}

	/**
	 * Set self illumination color
	 *
	 * @param float $red   Red color value
	 * @param float $green Green color value
	 * @param float $blue  Blue color value
	 * @return static
	 */
	public function setSelfIllumColor($red, $green, $blue) {
		$this->selfIllumColor = floatval($red) . ' ' . floatval($green) . ' ' . floatval($blue);
		return $this;
	}

	/**
	 * Set sky gradient scale
	 *
	 * @param float $scale Gradient scale
	 * @return static
	 */
	public function setSkyGradientScale($scale) {
		$this->skyGradientV_Scale = (float)$scale;
		return $this;
	}

	/**
	 * Add a sky gradient key
	 *
	 * @param float  $gradientX Scale value
	 * @param string $color     Gradient color
	 * @return static
	 */
	public function addSkyGradientKey($gradientX, $color) {
		$gradientX   = (float)$gradientX;
		$color       = (string)$color;
		$gradientKey = array('x' => $gradientX, 'color' => $color);
		array_push($this->skyGradientKeys, $gradientKey);
		return $this;
	}

	/**
	 * Remove all sky gradient keys
	 *
	 * @return static
	 */
	public function removeSkyGradientKeys() {
		$this->skyGradientKeys = array();
		return $this;
	}

	/**
	 * Render the Mood XML element
	 *
	 * @param \DOMDocument $domDocument DOMDocument for which the Mood XML element should be rendered
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
		if ($this->lBall_Intensity) {
			$moodXml->setAttribute('LBall_Intens', $this->lBall_Intensity);
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
