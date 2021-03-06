<?php
namespace qtismtest\data\storage\xml\marshalling;

use qtismtest\QtiSmTestCase;
use qtism\data\storage\xml\marshalling\Marshaller;
use qtism\data\rules\ExitResponse;
use \DOMDocument;

class ExitResponseMarshallerTest extends QtiSmTestCase {

	public function testMarshall() {

		$component = new ExitResponse();
		$marshaller = $this->getMarshallerFactory('2.1.0')->createMarshaller($component);
		$element = $marshaller->marshall($component);
		
		$this->assertInstanceOf('\\DOMElement', $element);
		$this->assertEquals('exitResponse', $element->nodeName);
	}
	
	public function testUnmarshall() {
		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->loadXML('<exitResponse xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1"/>');
		$element = $dom->documentElement;
		
		$marshaller = $this->getMarshallerFactory('2.1.0')->createMarshaller($element);
		$component = $marshaller->unmarshall($element);
		
		$this->assertInstanceOf('qtism\\data\\rules\\ExitResponse', $component);
		$this->assertEquals('exitResponse', $component->getQtiClassName());
	}
}