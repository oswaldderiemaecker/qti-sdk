<?php

use qtism\data\storage\xml\XmlDocument;
use qtism\runtime\rendering\markup\xhtml\XhtmlRenderingEngine;

require_once(dirname(__FILE__) . '/../../vendor/autoload.php');

/*
 * The goal of this script is to demonstrate that empty <qti:object> elements
 * will be rendered as a non self-closing tag for browser compatibility.
 */

$doc = new XmlDocument();
$doc->load(dirname(__FILE__) . '/../samples/rendering/empty_object.xml');

$renderer = new XhtmlRenderingEngine();

$rendering = $renderer->render($doc->getDocumentComponent());
$rendering->formatOutput = true;

echo $rendering->saveXML();