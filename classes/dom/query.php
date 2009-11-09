<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Kohana 3 port of the Zend Framework Dom Query
 * library. More information to follow...
 * 
 * Original code copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * 
 * Adapted for Kohana 3 by Sam de Freyssinet <sam.defreyssinet@kohanaphp.com>
 *
 * @package Dom Query
 * @copyright (c) 2009 maison de Freyssinet
 * @license ISC License http://www.opensource.org/licenses/isc-license.txt
 */
class Dom_Query {

	const DOC_XML   = 'application/xml';
	const DOC_HTML  = 'text/html';
	const DOC_XHTML = 'application/xhtml+xml';

	protected $_document;

	protected $_doc_type;

	public function __construct($document = NULL)
	{
		if (NULL !== $document)
			$this->set_document($document);
	}

	public function set_document($document)
	{
		if (0 == strlen($document))
			return $this;

		if ('<?xml' == substr(trim($document), 0, 5))
			return $this->set_xml_document($document);

		if (strstr($document, 'DTD XHTML'))
			return $this->set_xhtml_document($document);

		return $this->set_html_document($document);
	}

	public function set_html_document($document)
	{
		$this->_document = (string) $document;
		$this->_doc_type = Dom_Query::DOC_HTML;
		return $this;
	}

	public function set_xml_document($document)
	{
		$this->_document = (string) $document;
		$this->_doc_type = Dom_Query::DOC_XML;
		return $this;
	}

	public function set_xhtml_document($document)
	{
		$this->_document = (string) $document;
		$this->_doc_type = Dom_Query::DOC_XHTML;
		return $this;
	}

	public function get_document()
	{
		return $this->_document;
	}

	public function get_doc_type()
	{
		return $this->_doc_type;
	}

	public function query($query)
	{
		if (NULL === $this->_document)
			throw new Dom_Query_Exception('Cannot query empty document');

		$xpath_query = Dom_Query_Parser::instance($query);
		return $this->_xpath_query($xpath_query, $query);
	}

	protected function _xpath_query($xpath_query, $query = NULL)
	{
		$dom_document = new DOMDocument;
		$type = $this->get_doc_type();

		// Could suppress DOM errors when loading the document
		// however you should be using POSH.
		switch ($type)
		{
			case Dom_Query::DOC_XML :
				$success = $dom_document->loadXML($document);
				break;
			case Dom_Query::DOC_XHTML :
			case Dom_Query::DOC_HTML :
			default :
				$success = $dom_document->loadHTML($document);
				break;
		}

		if ( ! $success)
			throw new Dom_Query_Exception('Failed to load the document using document type :type', array(':type' => $type));

		$node_list = $this->_get_node_list($dom_document, $xpath_query);
		return new Dom_Query_Result($query, $xpath_query, $dom_document, $node_list);
	}

	protected function _get_node_list(DOMDocument $dom_document, $xpath_query)
	{
		$xpath = new DOMXPath($dom_document);
		$xpath_query = (string) $xpath_query;

		if (preg_match_all('|\[contains\((@[a-z0-9_-]+),\s?\' |i', $xpath_query, $matches))
		{
			foreach ($matches[1] as $attribute)
			{
				$query_string = sprintf('//*[%s]', $attribute);
				$attribute_name = substr($attribute, 1);
				$nodes = $xpath->query($query_string);
				foreach ($nodes as $node)
				{
					$attribute = $node->attributes->getNamedItem($attribute_name);
					$attribute->value = sprintf(' %s ', $attribute->value);
				}
			}
		}

		return $xpath->query($xpath_query);
	}
}