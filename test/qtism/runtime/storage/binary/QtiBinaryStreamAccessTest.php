<?php

use qtism\runtime\tests\AssessmentItemSession;
use qtism\runtime\tests\AssessmentItemSessionState;
use qtism\runtime\storage\common\AssessmentTestSeeker;
use qtism\data\storage\xml\XmlCompactAssessmentTestDocument;
use qtism\common\datatypes\Point;
use qtism\common\datatypes\DirectedPair;
use qtism\common\datatypes\Pair;
use qtism\common\Comparable;
use qtism\common\datatypes\Duration;
use qtism\runtime\common\RecordContainer;
use qtism\runtime\common\OrderedContainer;
use qtism\runtime\common\MultipleContainer;
use qtism\runtime\common\ResponseVariable;
use qtism\common\enums\BaseType;
use qtism\common\enums\Cardinality;
use qtism\runtime\common\OutcomeVariable;
use qtism\runtime\common\Container;
use qtism\runtime\common\Variable;
use qtism\runtime\storage\binary\BinaryStream;
use qtism\runtime\storage\binary\QtiBinaryStreamAccess;
use qtism\runtime\storage\binary\QtiBinaryStreamAccessException;

require_once (dirname(__FILE__) . '/../../../../QtiSmTestCase.php');

class QtiBinaryStreamAccessTest extends QtiSmTestCase {
	
    /**
     * @dataProvider readVariableValueProvider
     * 
     * @param Variable $variable
     * @param string $binary
     * @param mixed $expectedValue
     */
    public function testReadVariableValue(Variable $variable, $binary, $expectedValue) {
        $stream = new BinaryStream($binary);
        $stream->open();
        $access = new QtiBinaryStreamAccess($stream);
        $access->readVariableValue($variable);
        
        if (is_scalar($expectedValue) === true) {
            $this->assertEquals($expectedValue, $variable->getValue());
        }
        else if (is_null($expectedValue) === true) {
            $this->assertSame($expectedValue, $variable->getValue());
        }
        else if ($expectedValue instanceof RecordContainer) {
            $this->assertEquals($expectedValue->getCardinality(), $variable->getCardinality());
            $this->assertTrue($expectedValue->equals($variable->getValue()));
        }
        else if ($expectedValue instanceof Container) {
            $this->assertEquals($expectedValue->getCardinality(), $variable->getCardinality());
            $this->assertEquals($expectedValue->getBaseType(), $variable->getBaseType());
            $this->assertTrue($expectedValue->equals($variable->getValue()));
        }
        else if ($expectedValue instanceof Comparable) {
            // Duration, Point, Pair, ...
            $this->assertTrue($expectedValue->equals($variable->getValue()));
        }
        else {
            // can't happen.
            $this->assertTrue(false);
        }
    }
    
    public function readVariableValueProvider() {
        $returnValue = array();
        
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::INTEGER, 45), "\x01", null);
        $returnValue[] = array(new ResponseVariable('VAR', Cardinality::SINGLE, BaseType::INTEGER), "\x00" . "\x01" . pack('l', 45), 45);
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::INTEGER), "\x00" . "\x00" . pack('S', 3) . "\x00" . pack('l', 0) . "\x00" . pack('l', -20) . "\x00" . pack('l', 65000), new MultipleContainer(BaseType::INTEGER, array(0, -20, 65000)));
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::INTEGER), "\x00" . "\x00" . pack('S', 3) . "\x00" . pack('l', 0) . "\x01" . "\x00" . pack('l', 65000), new MultipleContainer(BaseType::INTEGER, array(0, null, 65000)));
        $returnValue[] = array(new ResponseVariable('VAR', Cardinality::ORDERED, BaseType::INTEGER), "\x00" . "\x00" . pack('S', 1) . "\x00" . pack('l', 1337), new OrderedContainer(BaseType::INTEGER, array(1337)));
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::INTEGER), "\x00" . "\x00" . pack('S', 0), new MultipleContainer(BaseType::INTEGER));
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::INTEGER, new OrderedContainer(BaseType::INTEGER, array(1))), "\x01", null);
        
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::FLOAT, 45.5), "\x01", null);
        $returnValue[] = array(new ResponseVariable('VAR', Cardinality::SINGLE, BaseType::FLOAT), "\x00" . "\x01" . pack('d', 45.5), 45.5);
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::FLOAT), "\x00" . "\x00" . pack('S', 3) . "\x00" . pack('d', 0.0) . "\x00" . pack('d', -20.666) . "\x00" . pack('d', 65000.56), new MultipleContainer(BaseType::FLOAT, array(0.0, -20.666, 65000.56)));
        $returnValue[] = array(new ResponseVariable('VAR', Cardinality::ORDERED, BaseType::FLOAT), "\x00" . "\x00" . pack('S', 1) . "\x00" . pack('d', 1337.666), new OrderedContainer(BaseType::FLOAT, array(1337.666)));
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::FLOAT), "\x00" . "\x00" . pack('S', 0), new MultipleContainer(BaseType::FLOAT));
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::FLOAT, new OrderedContainer(BaseType::FLOAT, array(0.0))), "\x01", null);
        
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::BOOLEAN, true), "\x01", null);
        $returnValue[] = array(new ResponseVariable('VAR', Cardinality::SINGLE, BaseType::BOOLEAN), "\x00" . "\x01" . "\x01", true);
        $returnValue[] = array(new ResponseVariable('VAR', Cardinality::SINGLE, BaseType::BOOLEAN), "\x00" . "\x01" . "\x00", false);
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::BOOLEAN), "\x00" . "\x00" . pack('S', 3) . "\x00\x00\x00\x01\x00\x00", new MultipleContainer(BaseType::BOOLEAN, array(false, true, false)));
        $returnValue[] = array(new ResponseVariable('VAR', Cardinality::ORDERED, BaseType::BOOLEAN), "\x00" . "\x00" . pack('S', 1) . "\x00\x01", new OrderedContainer(BaseType::BOOLEAN, array(true)));
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::BOOLEAN), "\x00" . "\x00" . pack('S', 0), new MultipleContainer(BaseType::BOOLEAN));
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::BOOLEAN, new OrderedContainer(BaseType::BOOLEAN, array(true))), "\x01", null);
        
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::STRING, 'String!'), "\x01", null);
        $returnValue[] = array(new ResponseVariable('VAR', Cardinality::SINGLE, BaseType::STRING), "\x00" . "\x01" . pack('S', 0) . '', '');
        $returnValue[] = array(new ResponseVariable('VAR', Cardinality::SINGLE, BaseType::STRING), "\x00" . "\x01" . pack('S', 7) . 'String!', 'String!');
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::STRING), "\x00" . "\x00" . pack('S', 3) . "\x00" . pack('S', 3) . 'ABC' . "\x00" . pack('S', 0) . '' . "\x00" . pack('S', 7) . 'String!', new MultipleContainer(BaseType::STRING, array('ABC', '', 'String!')));
        $returnValue[] = array(new ResponseVariable('VAR', Cardinality::ORDERED, BaseType::STRING), "\x00" . "\x00" . pack('S', 1) . "\x00" . pack('S', 7) . 'String!', new OrderedContainer(BaseType::STRING, array('String!')));
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::STRING), "\x00" . "\x00" . pack('S', 0), new MultipleContainer(BaseType::STRING));
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::STRING, new OrderedContainer(BaseType::STRING, array('pouet'))), "\x01", null);
        
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::IDENTIFIER, 'Identifier'), "\x01", null);
        $returnValue[] = array(new ResponseVariable('VAR', Cardinality::SINGLE, BaseType::IDENTIFIER), "\x00" . "\x01" . pack('S', 1) . 'A', 'A');
        $returnValue[] = array(new ResponseVariable('VAR', Cardinality::SINGLE, BaseType::IDENTIFIER), "\x00" . "\x01" . pack('S', 10) . 'Identifier', 'Identifier');
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::IDENTIFIER), "\x00" . "\x00" . pack('S', 3) . "\x00" . pack('S', 3) . 'Q01' . "\x00" . pack('S', 1) . 'A' . "\x00" . pack('S', 3) . 'Q02', new MultipleContainer(BaseType::IDENTIFIER, array('Q01', 'A', 'Q02')));
        $returnValue[] = array(new ResponseVariable('VAR', Cardinality::ORDERED, BaseType::IDENTIFIER), "\x00" . "\x00" . pack('S', 1) . "\x00" . pack('S', 10) . 'Identifier', new OrderedContainer(BaseType::IDENTIFIER, array('Identifier')));
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::IDENTIFIER), "\x00" . "\x00" . pack('S', 0), new MultipleContainer(BaseType::IDENTIFIER));
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::IDENTIFIER, new OrderedContainer(BaseType::IDENTIFIER, array('OUTCOMEX'))), "\x01", null);
        
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::DURATION, new Duration('PT1S')), "\x01", null);
        $returnValue[] = array(new ResponseVariable('VAR', Cardinality::SINGLE, BaseType::DURATION), "\x00" . "\x01" . pack('S', 4) . 'PT1S', new Duration('PT1S'));
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::DURATION), "\x00" . "\x00" . pack('S', 3) . "\x00" . pack('S', 4) . 'PT0S' . "\x00" . pack('S', 4) . 'PT1S' . "\x00" . pack('S', 4) . 'PT2S', new MultipleContainer(BaseType::DURATION, array(new Duration('PT0S'), new Duration('PT1S'), new Duration('PT2S'))));
        $returnValue[] = array(new ResponseVariable('VAR', Cardinality::ORDERED, BaseType::DURATION), "\x00" . "\x00" . pack('S', 1) . "\x00" . pack('S', 6) . 'PT2M2S', new OrderedContainer(BaseType::DURATION, array(new Duration('PT2M2S'))));
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::DURATION), "\x00" . "\x00" . pack('S', 0), new MultipleContainer(BaseType::DURATION));
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::DURATION, new OrderedContainer(BaseType::DURATION, array(new Duration('PT1S')))), "\x01", null);
        
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::PAIR, new Pair('A', 'B')), "\x01", null);
        $returnValue[] = array(new ResponseVariable('VAR', Cardinality::SINGLE, BaseType::PAIR), "\x00" . "\x01" . pack('S', 1) . 'A' . pack('S', 1) . 'B', new Pair('A', 'B'));
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::PAIR), "\x00" . "\x00" . pack('S', 3) . "\x00" . pack('S', 1) . 'A' . pack('S', 1) . 'B' . "\x00" . pack('S', 1) . 'C' . pack('S', 1) . 'D' . "\x00" . pack('S', 1) . 'E' . pack('S', 1) . 'F', new MultipleContainer(BaseType::PAIR, array(new Pair('A', 'B'), new Pair('C', 'D'), new Pair('E', 'F'))));
        $returnValue[] = array(new ResponseVariable('VAR', Cardinality::ORDERED, BaseType::PAIR), "\x00" . "\x00" . pack('S', 1) . "\x00" . pack('S', 2) . 'P1' . pack('S', 2) . 'P2', new OrderedContainer(BaseType::PAIR, array(new Pair('P1', 'P2'))));
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::PAIR), "\x00" . "\x00" . pack('S', 0), new MultipleContainer(BaseType::PAIR));
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::PAIR, new OrderedContainer(BaseType::PAIR, array(new Pair('my', 'pair')))), "\x01", null);
        
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::DIRECTED_PAIR, new DirectedPair('A', 'B')), "\x01", null);
        $returnValue[] = array(new ResponseVariable('VAR', Cardinality::SINGLE, BaseType::DIRECTED_PAIR), "\x00" . "\x01" . pack('S', 1) . 'A' . pack('S', 1) . 'B', new DirectedPair('A', 'B'));
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::DIRECTED_PAIR), "\x00" . "\x00" . pack('S', 3) . "\x00" . pack('S', 1) . 'A' . pack('S', 1) . 'B' . "\x00" . pack('S', 1) . 'C' . pack('S', 1) . 'D' . "\x00" . pack('S', 1) . 'E' . pack('S', 1) . 'F', new MultipleContainer(BaseType::DIRECTED_PAIR, array(new DirectedPair('A', 'B'), new DirectedPair('C', 'D'), new DirectedPair('E', 'F'))));
        $returnValue[] = array(new ResponseVariable('VAR', Cardinality::ORDERED, BaseType::DIRECTED_PAIR), "\x00" . "\x00" . pack('S', 1) . "\x00" . pack('S', 2) . 'P1' . pack('S', 2) . 'P2', new OrderedContainer(BaseType::DIRECTED_PAIR, array(new DirectedPair('P1', 'P2'))));
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::DIRECTED_PAIR), "\x00" . "\x00" . pack('S', 0), new MultipleContainer(BaseType::DIRECTED_PAIR));
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::DIRECTED_PAIR, new OrderedContainer(BaseType::DIRECTED_PAIR, array(new DirectedPair('my', 'pair')))), "\x01", null);
        
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::POINT, new Point(0, 1)), "\x01", null);
        $returnValue[] = array(new ResponseVariable('VAR', Cardinality::SINGLE, BaseType::POINT), "\x00" . "\x01" . pack('S', 0) . pack('S', 0), new Point(0, 0));
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::POINT), "\x00" . "\x00" . pack('S', 3) . "\x00" . pack('S', 4) . pack('S', 3) . "\x01" . "\x00" . pack('S', 2) . pack('S', 1), new MultipleContainer(BaseType::POINT, array(new Point(4, 3), null, new Point(2, 1))));
        $returnValue[] = array(new ResponseVariable('VAR', Cardinality::ORDERED, BaseType::POINT), "\x00" . "\x00" . pack('S', 1) . "\x00" . pack('S', 6) . pack('S', 1234), new OrderedContainer(BaseType::POINT, array(new Point(6, 1234))));
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::POINT), "\x00" . "\x00" . pack('S', 0), new MultipleContainer(BaseType::POINT));
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::POINT, new OrderedContainer(BaseType::POINT, array(new Point(1, 1)))), "\x01", null);
        
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::INT_OR_IDENTIFIER, 45), "\x01", null);
        $returnValue[] = array(new ResponseVariable('VAR', Cardinality::SINGLE, BaseType::INT_OR_IDENTIFIER), "\x00" . "\x01" . "\x01" . pack('l', 45), 45);
        $returnValue[] = array(new ResponseVariable('VAR', Cardinality::SINGLE, BaseType::INT_OR_IDENTIFIER), "\x00" . "\x01" . "\x00" . pack('S', 10) . 'Identifier', 'Identifier');
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::INT_OR_IDENTIFIER), "\x00" . "\x00" . pack('S', 3) . "\x00" . "\x01" . pack('l', 0) . "\x00" . "\x01" . pack('l', -20) . "\x00" . "\x01" . pack('l', 65000), new MultipleContainer(BaseType::INT_OR_IDENTIFIER, array(0, -20, 65000)));
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::INT_OR_IDENTIFIER), "\x00" . "\x00" . pack('S', 3) . "\x00" . "\x00" . pack('S', 1) . 'A' . "\x00" . "\x00" . pack('S', 1) . 'B' . "\x00" . "\x00" . pack('S', 1) . 'C', new MultipleContainer(BaseType::INT_OR_IDENTIFIER, array('A', 'B', 'C')));
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::INT_OR_IDENTIFIER), "\x00" . "\x00" . pack('S', 3) . "\x00" . "\x00" . pack('S', 1) . 'A' . "\x00" . "\x01" . pack('l', 1337) . "\x00" . "\x00" . pack('S', 1) . 'C', new MultipleContainer(BaseType::INT_OR_IDENTIFIER, array('A', 1337, 'C')));
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::INT_OR_IDENTIFIER), "\x00" . "\x00" . pack('S', 3) . "\x00" . "\x01" . pack('l', 0) . "\x01" . "\x00" . "\x01" . pack('l', 65000), new MultipleContainer(BaseType::INT_OR_IDENTIFIER, array(0, null, 65000)));
        $returnValue[] = array(new ResponseVariable('VAR', Cardinality::ORDERED, BaseType::INT_OR_IDENTIFIER), "\x00" . "\x00" . pack('S', 1) . "\x00" . "\x01" . pack('l', 1337), new OrderedContainer(BaseType::INT_OR_IDENTIFIER, array(1337)));
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::INT_OR_IDENTIFIER), "\x00" . "\x00" . pack('S', 0), new MultipleContainer(BaseType::INT_OR_IDENTIFIER));
        $returnValue[] = array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::INT_OR_IDENTIFIER, new OrderedContainer(BaseType::INT_OR_IDENTIFIER, array(1))), "\x01", null);
        
        // Records
        $returnValue[] = array(new ResponseVariable('VAR', Cardinality::RECORD), "\x00" . "\x00" . pack('S', 0), new RecordContainer());
        $returnValue[] = array(new ResponseVariable('VAR', Cardinality::RECORD), "\x00" . "\x00" . pack('S', 1) . "\x00" . pack('S', 4) . 'key1' . "\x02" . pack('l', 1337), new RecordContainer(array('key1' => 1337)));
        $returnValue[] = array(new ResponseVariable('VAR', Cardinality::RECORD), "\x00" . "\x00" . pack('S', 2) . "\x00" . pack('S', 4) . 'key1' . "\x02" . pack('l', 1337) . "\x00" . pack('S', 4) . 'key2' . "\x04" . pack('S', 7) . 'String!', new RecordContainer(array('key1' => 1337, 'key2' => 'String!')));
        $returnValue[] = array(new ResponseVariable('VAR', Cardinality::RECORD), "\x00" . "\x00" . pack('S', 3) . "\x00" . pack('S', 4) . 'key1' . "\x02" . pack('l', 1337) . "\x01" . pack('S', 4) . 'key2' . "\x00" . pack('S', 4) . 'key3' . "\x04" . pack('S', 7) . 'String!', new RecordContainer(array('key1' => 1337, 'key2' => null, 'key3' => 'String!')));
        
        return $returnValue;
    }
    
    /**
     * @dataProvider writeVariableValueProvider
     * 
     * @param Variable $variable
     */
    public function testWriteVariableValue(Variable $variable) {
        $stream = new BinaryStream();
        $stream->open();
        $access = new QtiBinaryStreamAccess($stream);
        
        // Write the variable value.
        $access->writeVariableValue($variable);
        $stream->rewind();
        
        $testVariable = clone $variable;
        // Reset the value of $testVariable.
        $testVariable->setValue(null);
        
        // Read what we just wrote.
        $access->readVariableValue($testVariable);
        
        $originalValue = $variable->getValue();
        $readValue = $testVariable->getValue();
        
        // Compare.
        if (is_null($originalValue) === true) {
            $this->assertSame($originalValue, $readValue);
        }
        else if (is_scalar($originalValue) === true) {
            $this->assertEquals($originalValue, $readValue);
        }
        else if ($originalValue instanceof RecordContainer) {
            $this->assertEquals($originalValue->getCardinality(), $readValue->getCardinality());
            $this->assertTrue($readValue->equals($originalValue));
        }
        else if ($originalValue instanceof Container) {
            // MULTIPLE or ORDERED container.
            $this->assertEquals($originalValue->getCardinality(), $readValue->getCardinality());
            $this->assertEquals($readValue->getBaseType(), $readValue->getBaseType());
            $this->assertTrue($readValue->equals($originalValue), $originalValue . " != " . $readValue);
        }
        else if ($originalValue instanceof Comparable) {
            // Complex QTI Runtime object.
            $this->assertTrue($readValue->equals($originalValue));
        }
        else {
            // Unknown datatype.
            $this->assertTrue(false);
        }
    }
    
    public function writeVariableValueProvider() {
        return array(
            array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::INTEGER, 26)),
            array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::INTEGER, -34455)),
            array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::INTEGER)),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::INTEGER)),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::INTEGER)),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::INTEGER, new MultipleContainer(BaseType::INTEGER, array(-2147483648)))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::INTEGER, new OrderedContainer(BaseType::INTEGER, array(2147483647)))),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::INTEGER, new MultipleContainer(BaseType::INTEGER, array(0, -1, 1, -200000, 200000)))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::INTEGER, new OrderedContainer(BaseType::INTEGER, array(0, -1, 1, -200000, 200000)))),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::INTEGER, new MultipleContainer(BaseType::INTEGER, array(null)))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::INTEGER, new OrderedContainer(BaseType::INTEGER, array(null)))),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::INTEGER, new MultipleContainer(BaseType::INTEGER, array(0, null, 1, null, 200000)))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::INTEGER, new OrderedContainer(BaseType::INTEGER, array(0, null, 1, null, 200000)))),
                        
            array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::FLOAT, 26.1)),
            array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::FLOAT, -34455.0)),
            array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::FLOAT)),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::FLOAT)),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::FLOAT)),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::FLOAT, new MultipleContainer(BaseType::FLOAT, array(-21474.654)))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::FLOAT, new OrderedContainer(BaseType::FLOAT, array(21474.3)))),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::FLOAT, new MultipleContainer(BaseType::FLOAT, array(0.0, -1.1, 1.1, -200000.005, 200000.005)))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::FLOAT, new OrderedContainer(BaseType::FLOAT, array(0.0, -1.1, 1.1, -200000.005, 200000.005)))),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::FLOAT, new MultipleContainer(BaseType::FLOAT, array(null)))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::FLOAT, new OrderedContainer(BaseType::FLOAT, array(null)))),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::FLOAT, new MultipleContainer(BaseType::FLOAT, array(0.0, null, 1.1, null, 200000.005)))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::FLOAT, new OrderedContainer(BaseType::FLOAT, array(0.0, null, 1.1, null, 200000.005)))),
                        
            array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::BOOLEAN, true)),
            array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::BOOLEAN, false)),
            array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::BOOLEAN)),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::BOOLEAN)),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::BOOLEAN)),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::BOOLEAN, new MultipleContainer(BaseType::BOOLEAN, array(true)))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::BOOLEAN, new OrderedContainer(BaseType::BOOLEAN, array(false)))),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::BOOLEAN, new MultipleContainer(BaseType::BOOLEAN, array(false, true, false, true, true)))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::BOOLEAN, new OrderedContainer(BaseType::BOOLEAN, array(false, true, false, true, false)))),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::BOOLEAN, new MultipleContainer(BaseType::BOOLEAN, array(null)))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::BOOLEAN, new OrderedContainer(BaseType::BOOLEAN, array(null)))),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::BOOLEAN, new MultipleContainer(BaseType::BOOLEAN, array(false, null, true, null, false)))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::BOOLEAN, new OrderedContainer(BaseType::BOOLEAN, array(false, null, true, null, false)))),
                        
            array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::IDENTIFIER, 'identifier')),
            array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::IDENTIFIER, 'non-identifier')),
            array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::IDENTIFIER)),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::IDENTIFIER)),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::IDENTIFIER)),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::IDENTIFIER, new MultipleContainer(BaseType::IDENTIFIER, array('identifier')))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::IDENTIFIER, new OrderedContainer(BaseType::IDENTIFIER, array('identifier')))),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::IDENTIFIER, new MultipleContainer(BaseType::IDENTIFIER, array('identifier1', 'identifier2', 'identifier3', 'identifier4', 'identifier5')))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::IDENTIFIER, new OrderedContainer(BaseType::IDENTIFIER, array('identifier1', 'identifier2', 'identifier3', 'X-Y-Z', 'identifier4')))),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::IDENTIFIER, new MultipleContainer(BaseType::IDENTIFIER, array(null)))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::IDENTIFIER, new OrderedContainer(BaseType::IDENTIFIER, array(null)))),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::IDENTIFIER, new MultipleContainer(BaseType::IDENTIFIER, array('identifier1', null, 'identifier2', null, 'identifier3')))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::IDENTIFIER, new OrderedContainer(BaseType::IDENTIFIER, array('identifier1', null, 'identifier2', null, 'identifier3')))),
                        
            array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::URI, 'http://www.my.uri')),
            array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::URI, 'http://www.my.uri')),
            array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::URI)),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::URI)),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::URI)),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::URI, new MultipleContainer(BaseType::URI, array('http://www.my.uri')))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::URI, new OrderedContainer(BaseType::URI, array('http://www.my.uri')))),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::URI, new MultipleContainer(BaseType::URI, array('http://www.my.uri1', 'http://www.my.uri2', 'http://www.my.uri3', 'http://www.my.uri4', 'http://www.my.uri6')))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::URI, new OrderedContainer(BaseType::URI, array('http://www.my.uri1', 'http://www.my.uri2', 'http://www.my.uri3', 'http://www.my.uri4', 'http://www.my.uri5')))),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::URI, new MultipleContainer(BaseType::URI, array(null)))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::URI, new OrderedContainer(BaseType::URI, array(null)))),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::URI, new MultipleContainer(BaseType::URI, array('http://www.my.uri1', null, 'http://www.my.uri2', null, 'http://www.my.uri3')))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::URI, new OrderedContainer(BaseType::URI, array('http://www.my.uri1', null, 'http://www.my.uri2', null, 'http://www.my.uri3')))),
                        
            array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::DURATION, new Duration('P3DT2H1S'))),
            array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::DURATION)),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::DURATION)),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::DURATION)),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::DURATION, new MultipleContainer(BaseType::DURATION, array(new Duration('PT2S'))))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::DURATION, new OrderedContainer(BaseType::DURATION, array(new Duration('P2YT2S'))))),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::DURATION, new MultipleContainer(BaseType::DURATION, array(new Duration('PT1S'), new Duration('PT2S'), new Duration('PT3S'), new Duration('PT4S'), new Duration('PT5S'))))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::DURATION, new OrderedContainer(BaseType::DURATION, array(new Duration('PT1S'), new Duration('PT2S'), new Duration('PT3S'), new Duration('PT4S'), new Duration('PT5S'))))),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::DURATION, new MultipleContainer(BaseType::DURATION, array(null)))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::DURATION, new OrderedContainer(BaseType::DURATION, array(null)))),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::DURATION, new MultipleContainer(BaseType::DURATION, array(new Duration('P4D'), null, new Duration('P10D'), null, new Duration('P20D'))))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::DURATION, new OrderedContainer(BaseType::DURATION, array(new Duration('P4D'), null, new Duration('P10D'), null, new Duration('P20D'))))),
                        
            array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::PAIR, new Pair('A', 'B'))),
            array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::PAIR)),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::PAIR)),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::PAIR)),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::PAIR, new MultipleContainer(BaseType::PAIR, array(new Pair('A', 'B'))))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::PAIR, new OrderedContainer(BaseType::PAIR, array(new Pair('A', 'B'))))),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::PAIR, new MultipleContainer(BaseType::PAIR, array(new Pair('A', 'B'), new Pair('C', 'D'), new Pair('E', 'F'), new Pair('G', 'H'), new Pair('I', 'J'))))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::PAIR, new OrderedContainer(BaseType::PAIR, array(new Pair('A', 'B'), new Pair('C', 'D'), new Pair('E', 'F'), new Pair('G', 'H'), new Pair('I', 'J'))))),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::PAIR, new MultipleContainer(BaseType::PAIR, array(null)))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::PAIR, new OrderedContainer(BaseType::PAIR, array(null)))),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::PAIR, new MultipleContainer(BaseType::PAIR, array(new Pair('A', 'B'), null, new Pair('C', 'D'), null, new Pair('E', 'F'))))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::PAIR, new OrderedContainer(BaseType::PAIR, array(new Pair('A', 'B'), null, new Pair('D', 'E'), null, new Pair('F', 'G'))))),
                        
            array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::DIRECTED_PAIR, new DirectedPair('A', 'B'))),
            array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::DIRECTED_PAIR)),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::DIRECTED_PAIR)),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::DIRECTED_PAIR)),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::DIRECTED_PAIR, new MultipleContainer(BaseType::DIRECTED_PAIR, array(new DirectedPair('A', 'B'))))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::DIRECTED_PAIR, new OrderedContainer(BaseType::DIRECTED_PAIR, array(new DirectedPair('A', 'B'))))),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::DIRECTED_PAIR, new MultipleContainer(BaseType::DIRECTED_PAIR, array(new DirectedPair('A', 'B'), new DirectedPair('C', 'D'), new DirectedPair('E', 'F'), new DirectedPair('G', 'H'), new DirectedPair('I', 'J'))))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::DIRECTED_PAIR, new OrderedContainer(BaseType::DIRECTED_PAIR, array(new DirectedPair('A', 'B'), new DirectedPair('C', 'D'), new DirectedPair('E', 'F'), new DirectedPair('G', 'H'), new DirectedPair('I', 'J'))))),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::DIRECTED_PAIR, new MultipleContainer(BaseType::DIRECTED_PAIR, array(null)))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::DIRECTED_PAIR, new OrderedContainer(BaseType::DIRECTED_PAIR, array(null)))),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::DIRECTED_PAIR, new MultipleContainer(BaseType::DIRECTED_PAIR, array(new DirectedPair('A', 'B'), null, new DirectedPair('C', 'D'), null, new DirectedPair('E', 'F'))))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::DIRECTED_PAIR, new OrderedContainer(BaseType::DIRECTED_PAIR, array(new DirectedPair('A', 'B'), null, new DirectedPair('D', 'E'), null, new DirectedPair('F', 'G'))))),
                        
            array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::POINT, new Point(50, 50))),
            array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::POINT)),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::POINT)),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::POINT)),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::POINT, new MultipleContainer(BaseType::POINT, array(new Point(50, 50))))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::POINT, new OrderedContainer(BaseType::POINT, array(new Point(50, 50))))),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::POINT, new MultipleContainer(BaseType::POINT, array(new Point(50, 50), new Point(0, 0), new Point(100, 50), new Point(150, 3), new Point(50, 50))))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::POINT, new OrderedContainer(BaseType::POINT, array(new Point(50, 50), new Point(0, 35), new Point(30, 50), new Point(40, 55), new Point(0, 0))))),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::POINT, new MultipleContainer(BaseType::POINT, array(null)))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::POINT, new OrderedContainer(BaseType::POINT, array(null)))),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::POINT, new MultipleContainer(BaseType::POINT, array(new Point(30, 50), null, new Point(20, 50), null, new Point(45, 32))))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::POINT, new OrderedContainer(BaseType::POINT, array(new Point(20, 11), null, new Point(36, 43), null, new Point(50, 44))))),
                        
            array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::INT_OR_IDENTIFIER, 26)),
            array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::INT_OR_IDENTIFIER, 'Q01')),
            array(new OutcomeVariable('VAR', Cardinality::SINGLE, BaseType::INT_OR_IDENTIFIER)),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::INT_OR_IDENTIFIER)),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::INT_OR_IDENTIFIER)),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::INT_OR_IDENTIFIER, new MultipleContainer(BaseType::INT_OR_IDENTIFIER, array(-2147483648)))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::INT_OR_IDENTIFIER, new OrderedContainer(BaseType::INT_OR_IDENTIFIER, array('Section1')))),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::INT_OR_IDENTIFIER, new MultipleContainer(BaseType::INT_OR_IDENTIFIER, array(0, 'Q01', 'Q02', -200000, 200000)))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::INT_OR_IDENTIFIER, new OrderedContainer(BaseType::INT_OR_IDENTIFIER, array(0, -1, 1, -200000, 'Q05')))),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::INT_OR_IDENTIFIER, new MultipleContainer(BaseType::INT_OR_IDENTIFIER, array(null)))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::INT_OR_IDENTIFIER, new OrderedContainer(BaseType::INT_OR_IDENTIFIER, array(null)))),
            array(new OutcomeVariable('VAR', Cardinality::MULTIPLE, BaseType::INT_OR_IDENTIFIER, new MultipleContainer(BaseType::INT_OR_IDENTIFIER, array(0, null, 1, null, 200000)))),
            array(new OutcomeVariable('VAR', Cardinality::ORDERED, BaseType::INT_OR_IDENTIFIER, new OrderedContainer(BaseType::INT_OR_IDENTIFIER, array(0, null, 'Q01', null, 200000)))),
                        
            array(new OutcomeVariable('VAR', Cardinality::RECORD)),
            array(new OutcomeVariable('VAR', Cardinality::RECORD, -1, new RecordContainer(array('key1' => null)))),
            array(new OutcomeVariable('Var', Cardinality::RECORD, -1, new RecordContainer(array('key1' => new Duration('PT1S'), 'key2' => 25.5, 'key3' => 2, 'key4' => 'String!', 'key5' => null, 'key6' => true))))
        );
    }
    
    public function testReadAssessmentItemSession() {
        $doc = new XmlCompactAssessmentTestDocument();
        $doc->load(self::samplesDir() . 'custom/runtime/itemsubset.xml');
        
        $position = pack('S', 0); // Q01
        $state = "\x01"; // INTERACTING
        $numAttempts = "\x02"; // 2
        $duration = pack('S', 4) . 'PT0S'; // 0 seconds recorded yet.
        $completionStatus = pack('S', 10) . 'incomplete';
        $timeReference = pack('l', 1378302030); //  Wednesday, September 4th 2013, 13:40:30 (GMT)
        $varCount = "\x02"; // 2 variables (SCORE & RESPONSE).
        
        $score = "\x01" . pack('S', 8) . "\x00" . "\x01" . pack('d', 1.0);
        $response = "\x00" . pack('S', 0) . "\x00" . "\x01" . pack('S', 7) . 'ChoiceA';
        
        $bin = implode('', array($position, $state, $numAttempts, $duration, $completionStatus, $timeReference, $varCount, $score, $response));
        $stream = new BinaryStream($bin);
        $stream->open();
        $access = new QtiBinaryStreamAccess($stream);
        $seeker = new AssessmentTestSeeker($doc, array('assessmentItemRef', 'outcomeDeclaration', 'responseDeclaration'));
        
        $session = $access->readAssessmentItemSession($seeker);
        
        $this->assertEquals('Q01', $session->getAssessmentItemRef()->getIdentifier());
        $this->assertEquals(AssessmentItemSessionState::INTERACTING, $session->getState());
        $this->assertEquals(2, $session['numAttempts']);
        $this->assertEquals('PT0S', $session['duration']->__toString());
        $this->assertEquals('incomplete', $session['completionStatus']);
        $this->assertInstanceOf('qtism\\runtime\\common\\OutcomeVariable', $session->getVariable('scoring'));
        $this->assertInternalType('float', $session['scoring']);
        $this->assertEquals(1.0, $session['scoring']);
        $this->assertInstanceOf('qtism\\runtime\\common\\ResponseVariable', $session->getVariable('RESPONSE'));
        $this->assertEquals(BaseType::IDENTIFIER, $session->getVariable('RESPONSE')->getBaseType());
        $this->assertInternalType('string', $session['RESPONSE']);
        $this->assertEquals('ChoiceA', $session['RESPONSE']);
    }
    
    public function testWriteAssessmentItemSession() {
        $doc = new XmlCompactAssessmentTestDocument();
        $doc->load(self::samplesDir() . 'custom/runtime/itemsubset.xml');
        
        $seeker = new AssessmentTestSeeker($doc, array('assessmentItemRef', 'outcomeDeclaration', 'responseDeclaration'));
        $stream = new BinaryStream();
        $stream->open();
        $access = new QtiBinaryStreamAccess($stream);
        
        $session = new AssessmentItemSession($doc->getComponentByIdentifier('Q02'));
        $session->beginItemSession();
        
        $access->writeAssessmentItemSession($seeker, $session);
        
        $stream->rewind();
        $session = $access->readAssessmentItemSession($seeker);
        $this->assertEquals(AssessmentItemSessionState::INITIAL, $session->getState());
        $this->assertEquals('PT0S', $session['duration']->__toString());
        $this->assertEquals(0, $session['numAttempts']);
        $this->assertEquals('not_attempted', $session['completionStatus']);
        $this->assertEquals(0.0, $session['SCORE']);
        $this->assertTrue($session['RESPONSE']->equals(new MultipleContainer(BaseType::PAIR)));
    }
}