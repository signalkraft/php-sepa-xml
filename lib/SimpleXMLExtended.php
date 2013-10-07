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
    setlocale(LC_CTYPE, 'en_US.UTF-8');
    $node->appendChild($no->createCDATASection(iconv('UTF-8', 'ASCII//TRANSLIT', $cdata_text)));
  } 
}