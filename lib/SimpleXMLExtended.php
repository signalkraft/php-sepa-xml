<?php


/**
 * See http://stackoverflow.com/a/7148112
 */
class SimpleXMLExtended extends SimpleXMLElement
{
  public function addCData($cdata_text)
  {
    $node= dom_import_simplexml($this); 
    $no = $node->ownerDocument; 
    $node->appendChild($no->createCDATASection(htmlentities(utf8_encode($cdata_text)))); 
  } 
}
