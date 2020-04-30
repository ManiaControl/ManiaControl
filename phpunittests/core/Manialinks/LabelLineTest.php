<?php

use FML\Controls\Frame;
use FML\Controls\Labels\Label_Text;
use ManiaControl\Manialinks\LabelLine;

/**
 * PHP Unit Test for Label Line Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class LabelLineTest extends PHPUnit_Framework_TestCase {
	public function testEntry(){
		$frame = new Frame();
		$labelLine = new LabelLine($frame);

		$labelLine->addLabelEntryText("ABC", 500, 50, "TestAction");
		$labelLine->addLabelEntryText("ABC", 500, 50, "TestAction2");
		$labelLine->setHorizontalAlign(Label_Text::RIGHT);
		$labelLine->setPrefix('$test');
		$labelLine->setStyle(Label_Text::STYLE_BgMainMenuTitleHeader);
		$labelLine->setTextColor('F09');
		$labelLine->setTextSize(500.2);
		$labelLine->setY(20);
		$labelLine->setZ(-20);

		$labelLine->render();

		$this->assertEquals(Label_Text::RIGHT, $labelLine->getHorizontalAlign());
		$this->assertEquals('$test', $labelLine->getPrefix());
		$this->assertEquals(Label_Text::STYLE_BgMainMenuTitleHeader, $labelLine->getStyle());
		$this->assertEquals('F09', $labelLine->getTextColor());
		$this->assertEquals(500.2, $labelLine->getTextSize(), "floatSize", 0.5);
		$this->assertEquals(20, $labelLine->getY());
		$this->assertEquals(-20, $labelLine->getZ());

		$this->assertArrayHasKey(0, $labelLine->getEntries());

		$firstEntry = $labelLine->getEntries()[0];

		$this->assertEquals("ABC", $firstEntry->getText());
		$this->assertEquals(500, $firstEntry->getX());
		$this->assertEquals(50, $firstEntry->getWidth());
		$this->assertEquals("TestAction",$firstEntry->getAction());
		$this->assertEquals(Label_Text::RIGHT, $firstEntry->getHorizontalAlign());
		$this->assertEquals('$test', $firstEntry->getTextPrefix());
		$this->assertEquals(Label_Text::STYLE_BgMainMenuTitleHeader, $firstEntry->getStyle());
		$this->assertEquals('F09', $firstEntry->getTextColor());
		$this->assertEquals(500.2, $firstEntry->getTextSize(), "floatSize", 0.5);
		$this->assertEquals(20, $firstEntry->getY());
		$this->assertEquals(-20, $firstEntry->getZ());

		$this->assertArrayHasKey(1, $labelLine->getEntries());

		$firstEntry = $labelLine->getEntries()[1];

		$this->assertEquals("TestAction2", $firstEntry->getAction());

	}
}
