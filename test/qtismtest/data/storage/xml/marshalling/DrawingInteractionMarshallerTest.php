<?php
namespace qtismtest\data\storage\xml\marshalling;

use qtismtest\QtiSmTestCase;
use qtism\data\content\FlowStaticCollection;
use qtism\data\content\TextRun;
use qtism\data\content\InlineStaticCollection;
use qtism\data\content\interactions\Prompt;
use qtism\data\content\interactions\DrawingInteraction;
use qtism\data\content\xhtml\Object;
use \DOMDocument;

class DrawingInteractionMarshallerTest extends QtiSmTestCase {

	public function testMarshall() {
	    $object = new Object('my-canvas.png', 'image/png');
	    $drawingInteraction = new DrawingInteraction('RESPONSE', $object, 'my-drawings', 'draw-it');
	    $prompt = new Prompt();
	    $prompt->setContent(new FlowStaticCollection(array(new TextRun('Prompt...'))));
	    $drawingInteraction->setPrompt($prompt);
	    
        $element = $this->getMarshallerFactory('2.1.0')->createMarshaller($drawingInteraction)->marshall($drawingInteraction);
        
        $dom = new DOMDocument('1.0', 'UTF-8');
        $element = $dom->importNode($element, true);
        $this->assertEquals('<drawingInteraction id="my-drawings" class="draw-it" responseIdentifier="RESPONSE"><prompt>Prompt...</prompt><object data="my-canvas.png" type="image/png"/></drawingInteraction>', $dom->saveXML($element));
	}
	
	public function testUnmarshall() {
        $element = $this->createDOMElement('
            <drawingInteraction id="my-drawings" class="draw-it" responseIdentifier="RESPONSE">
                <prompt>Prompt...</prompt>
                <object data="my-canvas.png" type="image/png"/>
            </drawingInteraction>
        ');
        
        $component = $this->getMarshallerFactory('2.1.0')->createMarshaller($element)->unmarshall($element);
        $this->assertInstanceOf('qtism\\data\\content\\interactions\\DrawingInteraction', $component);
        $this->assertEquals('my-drawings', $component->getId());
        $this->assertEquals('draw-it', $component->getClass());
        $this->assertEquals('RESPONSE', $component->getResponseIdentifier());
        
        $object = $component->getObject();
        $this->assertEquals('my-canvas.png', $object->getData());
        $this->assertEquals('image/png', $object->getType());
        
        $promptContent = $component->getPrompt()->getContent();
        $this->assertEquals('Prompt...', $promptContent[0]->getContent());
	}
}