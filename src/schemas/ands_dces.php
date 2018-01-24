<?php
/**
* MIT License
*
* Copyright (c) 2018 FCT | FCCN - Computação Científica Nacional
*
* Permission is hereby granted, free of charge, to any person obtaining a copy
* of this software and associated documentation files (the "Software"), to deal
* in the Software without restriction, including without limitation the rights
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
*
* The above copyright notice and this permission notice shall be included in all
* copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
* SOFTWARE.
*/

/**
 * \file
 * \brief classes related to generating DublinCore elements
 *
 *
 * Generate DublinCore Metadata Element Set - Version 1.1
 *
 *
 * 	DC_ELEMENT		|  DB_RECORD/Field	|	HTML Tag
 * -----------------|-------------------|-----------------
 * - contributor	|  contributor		|  <dc:contributor>
 * - coverage		|  coverage			|  <dc:coverage>
 * - creator		|  creator			|  <dc:creator>
 * - date			|  date_			|  <dc:date>
 * - description	|  description		|  <dc:description>
 * - format			|  format_			|  <dc:format>
 * - identifier		|  identifier		|  <dc:identifier>
 * - language		|  language			|  <dc:language>
 * - publisher		|  publisher		|  <dc:publisher>
 * - relation		|  relation			|  <dc:relation>
 * - rights			|  rights			|  <dc:rights>
 * - source			|  source			|  <dc:source>
 * - subject		|  subject			|  <dc:subject>
 * - title			|  title			|  <dc:title>
 * - type			|  type_			|  <dc:type>
 *
 */
namespace Fccn\Oaipmh;

require_once('ands_oai.php');

class ANDS_DCES extends ANDS_OAI
{

    /**
     * Constructor
     * The first two parameters are used by its parent class ANDS_RIFCS. The third is its own private property.
     *
     * \param $ands_response_doc ANDS_Response_XML. A XML Doc acts as the parent node.
     * \param $metadata_node DOMElement. The meta node which all subsequent nodes will be added to.
     */
    public function __construct($ands_response_doc, $metadata_node)
    {
        parent::__construct($ands_response_doc, $metadata_node);
    }

    /**
     * Override create registry objects to use oai_dc as holder
     */
    protected function create_regObjects()
    {
        $this->working_node = $this->oai_pmh->addChild($this->working_node, 'oai_dc:dc');
        $this->working_node->setAttribute('xmlns:oai_dc', "http://www.openarchives.org/OAI/2.0/oai_dc/");
        $this->working_node->setAttribute('xmlns:dc', "http://purl.org/dc/elements/1.1/");
        $this->working_node->setAttribute('xmlns:xsi', "http://www.w3.org/2001/XMLSchema-instance");
        $this->working_node->setAttribute('xsi:schemaLocation', 'http://www.openarchives.org/OAI/2.0/oai_dc/ http://www.openarchives.org/OAI/2.0/oai_dc.xsd');
    }

    /**
     * Creates a generic dc:element node
     */
    protected function create_dc_node($name, $value = '')
    {
        if (empty($value)) {
            return;
        }
        $this->addChild($this->working_node, "dc:".$name, utf8_encode($value));
    }

    public function generate_metadata($dc_identifier, $res)
    {
        debug_message("ands_dces :: generating metadata...");

        $dc_data = new DC_DataHolder();
        $dc_data->setIdentifier($dc_identifier);
        //debug_var_dump('result', $res->fetch(PDO::FETCH_OBJ));
        $res->setFetchMode(\PDO::FETCH_CLASS, DC_DataHolder::getKlass());

        while ($row = $res->fetch()) {
            //debug_var_dump('row', $row);
            $dc_data->combine($row);
        }

        $res->closeCursor();

        // debug_var_dump('dc_data', $dc_data);

        //write metadata to XML
        foreach ($dc_data->to_array() as $tag => $value) {
            if ($tag == 'identifier') {
                $this->create_dc_node($tag, $value);
            } else {
                foreach ($value as $subval) {
                    $this->create_dc_node($tag, $subval);
                }
            }
        }
    }
} // end of class ANDS_DCES

class DC_DataHolder
{

/**
    * \parameters
    *
    * identifier		|  <dc:identifier>
    *
    * contributor		|  <dc:contributor>
    * coverage			|  <dc:coverage>
    * creator			|  <dc:creator>
    * date_				|  <dc:date>
    * description		|  <dc:description>
    * format_			|  <dc:format>
    * language			|  <dc:language>
    * publisher			|  <dc:publisher>
    * relation			|  <dc:relation>
    * rights			|  <dc:rights>
    * source			|  <dc:source>
    * subject			|  <dc:subject>
    * title				|  <dc:title>
    * type_
 */

    protected $identifier;

    protected $contributor = array();
    protected $coverage = array();
    protected $creator = array();
    protected $date_ = array();
    protected $description = array();
    protected $format_ = array();
    protected $language = array();
    protected $publisher = array();
    protected $relation = array();
    protected $rights = array();
    protected $source = array();
    protected $subject = array();
    protected $title = array();
    protected $type_ = array();

    /**
    * Retuns the fully qualified class name
    */
    public static function getKlass()
    {
        $obj = new DC_DataHolder();
        return get_class($obj);
    }

    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * Combines this object with another DC_DataHolder object
     */
    public function combine($another)
    {
        if (is_a($another, DC_DataHolder::getKlass())) {
            // debug_message("entering combine...");
            // debug_var_dump("another", $another);
            //identifier
            if (empty($this->identifier)) {
                debug_message("DC_DataHolder::identifier should not be empty!!");
                $this->identifier = $another->identifier;
            } elseif (strcmp(substr($this->identifier, - strlen($another->identifier)), $another->identifier) !== 0) {
                debug_var_dump('comparison', strcmp(substr($this->identifier, - strlen($another->identifier)), $another->identifier));
                error_log("found a different identifier for this element ".$this->identifier." != ".$another->identifier);
                return;
            }
            //continue with other elements
            $this->addElement('contributor', $another);
            $this->addElement('coverage', $another);
            $this->addElement('creator', $another);
            $this->addElement('date_', $another);
            $this->addElement('description', $another);
            $this->addElement('format_', $another);
            $this->addElement('language', $another);
            $this->addElement('publisher', $another);
            $this->addElement('relation', $another);
            $this->addElement('rights', $another);
            $this->addElement('source', $another);
            $this->addElement('subject', $another);
            $this->addElement('title', $another);
            $this->addElement('type_', $another);
        }
    }

    /**
     * adds an element only if it is new
     */
    private function addElement($elem, $to_add)
    {
        //debug_var_dump($elem, $this->$elem);
        if (isset($to_add->$elem) && !empty($to_add->$elem)) {
            $elem_to_add = str_replace('&', '-and-', $to_add->$elem); //replace amperstamps for -and-
            if (!in_array($elem_to_add, $this->$elem)) {
                array_push($this->$elem, $elem_to_add);
            }
        }
    }

    public function to_array()
    {
        return array('identifier' => $this->identifier,
            'contributor' => $this->contributor ,
            'coverage' => $this->coverage ,
            'creator' => $this->creator ,
            'date' => $this->date_,
            'description' => $this->description ,
            'format' => $this->format_ ,
            'language' => $this->language ,
            'publisher' => $this->publisher ,
            'relation' => $this->relation ,
            'rights' => $this->rights ,
            'source' => $this->source ,
            'subject' => $this->subject ,
            'title' => $this->title ,
            'type' => $this->type_
        );
    }
} // end of class DC_DataHolder
