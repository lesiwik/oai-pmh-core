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
 * \brief classes related to generating LOM (Learning Object Metadata) elements
 *
 * @author Paulo Costa <paulo.costa@fccn.pt>
 *
 * Learning Object Metadata Element Set - Version 1.0
 *
 *
 *  DB_RECORD	|	DC_ELEMENT	| M	|  LOM Element
 * -------------|---------------|---|-------------------------------------------
 *  contributor | contributor	|	|  LifeCycle.Contribute.Entity	and
 * 				|				|	|  LifeCycle.Contribute.Role = <contrib>
 *  coverage	|  coverage		|	|  General.Coverage
 *  creator		|  creator		| M	|  LifeCycle.Contribute.Entity when
 * 	 			| 				|	|  LifeCycle.Contribute.Role = "Author"
 *  date_		| date			|	|  LifeCycle.Contribute.Date when
 * 	 			| 				|	|  LifeCycle.Contribute.Role = "Publisher"
 *  description	| description	| M	|  General.Description
 *  format_		| format		|	|  Technical.Format
 *  identifier	| identifier	|	|  General.Identifier.Entry
 *  language	| language		| M	|  General.Language
 *  publisher	| publisher		|	|  LifeCycle.Contribute.Entity when
 * 	  			|				|	|  LifeCycle.Contribute.Role = "Publisher"
 *  relation 	| relation		|	|  Relation.Resource.Description
 *  rights		| rights		|	|  Rights.Description
 *  source		| source		|	|  Relation.Resource when
 * 	  			|				|	|  Relation.Kind = "IsBasedOn" or
 * 	  			|				| M |  Technical.Location
 *  subject		| subject		| M	|  General.Keyword	or
 * 	  			|				|	|  Classification with
 * 	  			|				|	|  Classification.purpose = "Discipline"
 *  title		| title			| M	|  General.Title
 *  type_		| type			|	|  Educational.LearningResourceType**
 *  thumbnail	| 			 	| M	|  Technical.Thumbnail
 *
 *
 *
 * ** Educational.LearningResourceType must follow the LOM value space, in this case it should be "lecture"
 */
namespace Fccn\Oaipmh;

require_once('ands_oai.php');

class ANDS_LOM extends ANDS_OAI
{
    protected $def_catalog;

    /**
     * Constructor
     * The first two parameters are used by its parent class ANDS_OAI. The third is its own private property.
     *
     * \param $ands_response_doc ANDS_Response_XML. A XML Doc acts as the parent node.
     * \param $metadata_node DOMElement. The meta node which all subsequent nodes will be added to.
     */
    public function __construct($ands_response_doc, $metadata_node, $def_catalog)
    {
        debug_message('****** In '.__FILE__.' function '.__FUNCTION__.' was called.');
        parent::__construct($ands_response_doc, $metadata_node);
        //set default catalog for identifier
        $this->def_catalog = $def_catalog;
    }

    /**
     * Override create registry objects to use oai_lom as holder
     */
    protected function create_regObjects()
    {
        $this->working_node = $this->oai_pmh->addChild($this->working_node, 'lom:lom');
        $this->working_node->setAttribute('xmlns:lom', "http://ltsc.ieee.org/xsd/LOM");
        $this->working_node->setAttribute('xmlns:dc', "http://purl.org/dc/elements/1.1/");
    }

    /**
     * Generates metadata xml
     */
    public function generate_metadata($lom_identifier, $res)
    {
        debug_message('****** In '.__FILE__.' function '.__FUNCTION__.' was called.');
        $lom_data = new LOM_DataHolder();
        $lom_data->setIdentifiers($lom_identifier, $this->def_catalog);
        //debug_var_dump('lom_data->lomdata', $lom_data->lomdata['general']);
        //debug_var_dump('result', $res->fetch(PDO::FETCH_OBJ));
        $res->setFetchMode(\PDO::FETCH_CLASS, LOM_DataHolder::getKlass());

        while ($row = $res->fetch()) {
            //  debug_var_dump('lom_data', $lom_data);
            // debug_var_dump('query row', $row);
            $lom_data->combine($row);
        }
        $res->closeCursor();
        //debug_var_dump('lom_data', $lom_data);
        //write metadata to XML
        $this->write_xml_meta($lom_data->to_array());
    }

    /**
     * Creates a generic lom:element node
     */
    protected function create_lom_node($top_node, $tag, $value = '')
    {
        $tag_atts = explode('?', $tag);
        $name = array_shift($tag_atts);
        $node = '';
        if (empty($value)) {
            debug_message('tag ::'.$name.':: no value');
            $node = $this->addChild($top_node, "lom:".$name);
        } else {
            debug_message('tag ::'.$name.':: value ::'.$value);
            $node = $this->addChild($top_node, "lom:".$name, utf8_encode($value));
        }
        if (!empty($tag_atts)) {
            foreach ($tag_atts as $composite_att) {
                $att = explode('=', $composite_att);
                debug_message("setting attribute: $att[0] = $att[1] to tag: $name ");
                $node->setAttribute($att[0], $att[1]);
            }
        }
        return $node;
    }

    protected function write_xml_meta($lom_data, $top_node = '')
    {
        debug_message('****** In '.__FILE__.' function '.__FUNCTION__.' was called.');
        if ($top_node == '') {
            $top_node = $this->working_node;
        }
        // debug_var_dump('lom_data', $lom_data);
        foreach ($lom_data as $tag => $value) {
            //debug_message("processing: $tag, with $value");
            if (is_array($value)) {
                if ($this->is_assoc($value)) {
                    //create new top node
                    debug_message("creating regular node :: $tag");
                    $new_top_node = $this->create_lom_node($top_node, $tag);
                    $this->write_xml_meta($value, $new_top_node);
                } else {//is a numerical array
                    //repeat $tag on all values
                    foreach ($value as $subval) {
                        if (is_array($subval)) {
                            debug_message("creating repetition node :: $tag as array");
                            $new_top_node = $this->create_lom_node($top_node, $tag);
                            $this->write_xml_meta($subval, $new_top_node);
                        } else {
                            debug_message("creating repetition node :: $tag as simple");
                            $this->create_lom_node($top_node, $tag, $subval);
                        }
                    }
                }
            } else {
                //debug_message('writing xml ::'.$tag.':: for ::'.$value);
                //debug_var_dump('top_node', $top_node);
                $this->create_lom_node($top_node, $tag, $value);
            }
        }
    }


    /**
     * Checks if array is associative
     */
    protected function is_assoc($array)
    {
        return (bool)count(array_filter(array_keys($array), 'is_string'));
    }
} // end of class ANDS_DCES

class LOM_DataHolder
{

/**
 *
 *        LOM data			| M	|   DB record	| ext param
 * -------------------------|---|---------------|------------
 *--general        			|	| 		-		|	-
 * 	|--identifier  			|	|		-		|	-
 * 	|  |-entry				|  	|		-		| identifier
 * 	|  |-catalog			|  	|		-		| catalog
 *  |--title  				| M	|	  title		|   -
 * 	|--description			| M	|  description	|   -
 *  |--keyword				| M	|	subject		|   -
 *  |--language     		| M	|   language	|   -
 *  |--coverage			 	|	|   coverage	|	-
 *--lifeCycle				|	|		-		|	-
 * 	|--contribute   		| M	|		-		|	-
 * 	|  |-role				| M	|		-		|	-
 * 	|  | |-source			| M	|		-		|	"LOMv1.0"
 *  |  | |-value    		| M	|		-		|	"author"
 *  |  |-entity   			| M	|	creator		|	-
 * 	|--contribute   		| 	|		-		|	-
 * 	|  |-role				| 	|		-		|	-
 * 	|  | |-source			| 	|		-		|	"LOMv1.0"
 *  |  | |-value    		| 	|		-		|	"pubilsher"
 *  |  |-entity   			| 	|	publisher	|	-
 *  |  |-date   			| 	|	date_		|	-
 * 	|--contribute   		| 	|		-		|	-
 * 	|  |-role				| 	|		-		|	-
 * 	|  | |-source			| 	|		-		|	"LOMv1.0"
 *  |  | |-value    		| 	|		-		|	"editor"
 *  |  |-entity   			| 	|  contributor	|	-
 *--educational				| 	|		-		|	-
 * 	|--learningResourceType	|	|		-		|	-
 *  |  |-source				| 	|		-		|	"LOMv1.0"
 *  |  |-value				| 	|		-		|	"lecture"
 *--technical	 			|	|		-		|	-
 *  |--location				| M	|	source		|	-
 *  |--thumbnail			| M	|  thumbnail	|	-
 *  |--format				|   |   format_		|   -
 *--rigths					|   |   			|   -
 * 	|--description			|   |   	 		|   -
 * 	   |-string				|   |   rights 		|   -
 *--classification  		|	|		-		|	-
 *--metaMetadata  			|	|		-		|	-
 *  |--identifier  			|	|		-		|	-
 * 	|  |-entry				|  	|		-		| identifier
 * 	|  |-catalog			|  	|		-		| catalog
 *--relation 	 			|	|		-		|	-
 *  |--kind		  			|	|		-		| "ispartof"
 * 	|--resource				|  	|		-		|	-
 * 	|  |-identifier			|  	|   relation	|	-
 *  |  |-catalog			|  	|		-		|  catalog
 *
 *
 */

    //LOM dataset
    //public $lomdata;

    //identifiers
    protected $identifier;
    protected $catalog;

    //db datasets
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
    protected $thumbnail = array();

    /**
    * Retuns the fully qualified class name
    */
    public static function getKlass()
    {
        $obj = new LOM_DataHolder();
        return get_class($obj);
    }

    /**
     * sets the identifier and catalog
     */
    public function setIdentifiers($identifier, $catalog)
    {
        $this->identifier = $identifier;
        $this->catalog = $catalog;
    }

    /**
     * Sets general data with an identifier and catalog
     */
    // function setLomdata($identifier,$catalog){
    // $this->identifier = $identifier;
    // $this->lomdata = array(
    // 'general' => array(
    // 'identifier' => array(
    // 'catalog' => $catalog,
    // 'entry' => $identifier
    // )
    // ),//general
    // 'lifeCycle' => array(),
    // 'educational' => array(),
    // 'technical'	=> array(),
    // 'rights' => array(),
    // 'classification' => array(),
    // 'metaMetadata' => array(),
    // 'relation'	=> array()
    // );
//
    // }

    /**
     * Combines this object with another LOM_DataHolder object
     */
    public function combine($another)
    {
        debug_message(__CLASS__.'.'.__FUNCTION__." LLOM_DataHolder class is ".LOM_DataHolder::getKlass());
        debug_message(__CLASS__.'.'.__FUNCTION__." another is ".get_class($another));
        if (is_a($another, LOM_DataHolder::getKlass())) {
            debug_message('entering function '.__FUNCTION__.' in '.__CLASS__);
            // debug_var_dump("another", $another);
            //identifier
            if (empty($this->identifier)) {
                debug_message("LOM_DataHolder::identifier should not be empty!!");
                $this->identifier = $another->identifier;
            } elseif (strcmp(substr($this->identifier, - strlen($another->identifier)), $another->identifier) !== 0) {
                // debug_var_dump('comparison', strcmp(substr($this->identifier, - strlen($another->identifier)), $another->identifier));
                // debug_message("found a different identifier for this element ".$this->identifier." != ".$another->identifier);
                error_log("found a different identifier for this element ".$this->identifier." != ".$another->identifier);
                return;
            }
            debug_message("Identity ok. Adding elements");
            //continue with other db elements
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
            $this->addElement('thumbnail', $another);
        }
    }

    /**
     * adds a db element only if it is new
     */
    private function addElement($elem, $to_add)
    {
        //debug_var_dump("to_add->$elem", $to_add->$elem);
        if (isset($to_add->$elem) && !empty($to_add->$elem) && $to_add->$elem != ' ') {//&& !empty(str_replace(' ', '', $to_add->$elem)) ){
            $elem_to_add = str_replace('&', '-and-', $to_add->$elem); //replace amperstamps for -and-
            if (!in_array($elem_to_add, $this->$elem)) {
                array_push($this->$elem, $elem_to_add);
                //debug_var_dump("this->$elem", $this->$elem);
            }
        }
    }



    /**
     * puts db information in LOM format as an array
     */
    public function to_array()
    {
        debug_message('entering function '.__FUNCTION__.' in '.__CLASS__);
        //lomdata
        $lomdata = array(
            'general' => array(
                'identifier' => array(
                    'catalog' => $this->catalog,
                    'entry' => $this->identifier
                )
            ),//general
            'lifeCycle' => array(),
            'educational' => array(),
            'technical'    => array(),
            'rights' => array(),
            'classification' => array(),
            'metaMetadata' => array(),
            'relation'    => array()
        );
        //-----general.title (M)
        if (empty($this->title)) {
            array_push($this->title, "Untitled");
        }
        $lang = 'pt';
        $lomdata['general']['title'] = array("string?language=$lang" => $this->title[0]);
        //-----general.keyword (M)
        //initialize keyword
        //keywords from subject have the form: Language::keyword1$$Language::keyword2 ..
        $lomdata['general']['keyword'] = array();
        foreach ($this->subject as $elem) {
            $kw_elements = explode('$$', $elem);
            foreach ($kw_elements as $kw_elem) {
                $lang_kw = explode('::', $kw_elem);
                $kw = array_pop($lang_kw);
                if (empty($lang_kw)) {
                    $lang = 'pt';
                } else {
                    $lang = array_pop($lang_kw);
                }
                //add keyword
                if (strlen($kw) > 2) {
                    debug_message('adding keyword: '.$kw.' (length='.strlen($kw).') ; language '.$lang);
                    array_push($lomdata['general']['keyword'], array("string?language=$lang" =>$kw));
                }
            }
        }
        //-----general.description (M)
        //debug_var_dump('this->description', $this->description);
        //debug_var_dump('this', $this);
        if (empty($this->description)) {
            //repeat title in description if empty
            $_title = "";
            if (is_array($this->title)) {
                $_title = end($this->title);
            }
            array_push($this->description, $_title);
        }
        //initialize description
        $lomdata['general']['description'] = array();
        foreach ($this->description as $elem) {
            $lang = 'pt';
            array_push($lomdata['general']['description'], array("string?language=$lang" =>$elem));
            //add keywords from description
            if (strlen($elem) > 2) {
                array_push(
                    $lomdata['general']['keyword'],
                    array("string?language=pt" => implode(', ', array_keys($this->extractCommonWords($elem))))
                );
            }
            //debug_var_dump('lomdata->general->keyword', $lomdata['general']['keyword']);
        }
        //if after all this keyword is still empty....
        if (empty($lomdata['general']['keyword'])) {
            if (is_array($this->type_) && $this->type_[0] == 'collection') {
                array_push($lomdata['general']['keyword'], array("string?language=pt" =>'canal'));
            } else {
                array_push($lomdata['general']['keyword'], array("string?language=pt" =>'video'));
            }
        }
        //-----general.language (M)
        if (empty($this->language)) {
            array_push($this->language, "pt");
        }
        //initialize language
        $lomdata['general']['language'] = array();
        foreach ($this->language as $elem) {
            array_push($lomdata['general']['language'], $elem);
        }
        //-----general.coverage
        if (!empty($this->coverage)) {
            $lomdata['general']['coverage'] = array();
            foreach ($this->coverage as $elem) {
                $lang = 'pt';
                array_push($lomdata['general']['coverage'], array("string?language=$lang" =>$elem));
            }
        }
        //-----lifeCycle.contribute (M)
        if (empty($this->creator) && empty($this->publisher) && empty($this->contributor)) {
            //set unknown creator
            array_push($this->creator, "Unknown");
        }
        //intialize contribute
        $lomdata['lifeCycle']['contribute'] = array();
        //---lifeCycle.contribute -> creator
        if (!empty($this->creator)) {
            foreach ($this->creator as $elem) {
                array_push(
                    $lomdata['lifeCycle']['contribute'],
                    array(
                    'role' => array(
                        'source' => 'LOMv1.0',
                        'value' => 'author'),
                    'entity' => $this->asVcard($elem) )
                );
            }
        }
        //---lifeCycle.contribute -> publisher
        if (!empty($this->publisher)) {
            $date = '';
            if (!empty($this->date_)) {
                $date = $this->date_[0];
            }
            foreach ($this->publisher as $elem) {
                array_push(
                    $lomdata['lifeCycle']['contribute'],
                    array(
                    'role' => array(
                        'source' => 'LOMv1.0',
                        'value' => 'publisher'),
                    'entity' => $this->asVcard($elem),
                    'date' => array('dateTime' => $date) )
                );
            }
        }
        //---lifeCycle.contribute -> editor
        if (!empty($this->contributor)) {
            foreach ($this->contributor as $elem) {
                array_push(
                    $lomdata['lifeCycle']['contribute'],
                    array(
                    'role' => array(
                        'source' => 'LOMv1.0',
                        'value' => 'editor'),
                    'entity' => $this->asVcard($elem) )
                );
            }
        }
        //-----educational.learningResourceType
        $lomdata['educational']['learningResourceType'] =
            array(
            'source' => 'LOMv1.0',
            'value' => 'lecture');
        //-----technical.location (M)
        if (empty($this->source)) {
            array_push($this->source, "undefined");
        }
        //intialize location
        $lomdata['technical']['location'] = array();
        foreach ($this->source as $elem) {
            array_push($lomdata['technical']['location'], $elem);
        }
        //-----technical.thumbnail (M)
        if (empty($this->thumbnail)) {
            array_push($this->thumbnail, 'none');
        }
        $lomdata['technical']['thumbnail'] = $this->thumbnail[0];
        //-----technical.format
        if (!empty($this->format_)) {
            //initialize format
            $lomdata['technical']['format'] = array();
            foreach ($this->format_ as $elem) {
                array_push($lomdata['technical']['format'], $elem);
            }
        }
        //-----rights.description
        if (!empty($this->rights)) {
            //initialize description
            $lomdata['rights']['description'] = array();
            foreach ($this->rights as $elem) {
                $lang = 'pt';
                array_push($lomdata['rights']['description'], array("string?language=$lang" => $elem));
            }
        }
        //metaMetadata.identifier
        $lomdata['metaMetadata']['identifier'] = array(
            'catalog' => $lomdata['general']['identifier']['catalog'],
            'entry' => $lomdata['general']['identifier']['entry']
        );
        //relation
        if (!empty($this->relation)) {
            foreach ($this->relation as $elem) {
                array_push($lomdata['relation'], array(
                    'kind' => array(
                        'source' => 'LOMv1.0',
                        'value' => 'ispartof'),
                    'resource' => array(
                        'description' => array("string?language=en" => $elem)
                    )
                ));
            }
        }

        return $lomdata;
    }

    //turns the elements into a string formatted for VCard
    private function asVcard($name)
    {
        $name = preg_replace('/\s\s+/i', '', $name); //remove extra white spaces
        $name = preg_replace('/[^A-Za-z0-9\x{002D}\x{002E}\x{00C0}-\x{00FF} -]/', '', $name); //remove special chars
        $name = trim($name); // trim
        $nm_expl = explode(' ', $name);
        $head = '';
        if (in_array($nm_expl[0], array('Prof.', 'Professor', 'Dr.', 'Doutor' ))) {
            $head = array_shift($nm_expl);
        }
        $lastnm = array_pop($nm_expl);
        $n = $lastnm.';'.implode(' ', $nm_expl).';'.$head.';';
        return "BEGIN:VCARD\nVERSION:4.0\nN:$n\nFN:$name\nEND:VCARD";
    }

    //TODO include this in keyword generation
    private function extractCommonWords($string)
    {
        debug_message('entering function '.__FUNCTION__.' in '.__CLASS__);
        $stopWords = array('i','a','about','an','and','-and-','are','as','at','be','by','com','de','en','for','from','how','in','is','it','la','of','on','or','that','the','this','to','was','what','when','where','who','will','with','und','the',
                          'de', 'a', 'o', 'que', 'e', 'do', 'da', 'em', 'um', 'para', 'é', 'com', 'não', 'uma', 'os', 'no', 'se', 'na', 'por', 'mais', 'as', 'dos', 'como', 'mas', 'foi', 'ao', 'ele', 'das', 'tem', 'à', 'seu', 'sua',
                          'ou', 'ser', 'quando', 'muito', 'há', 'nos', 'já', 'está', 'eu', 'também', 'só', 'pelo', 'pela', 'até', 'isso', 'ela', 'entre', 'era', 'depois', 'sem', 'mesmo', 'aos', 'ter', 'seus', 'quem', 'nas', 'me',
                          'esse', 'eles', 'estão', 'você', 'tinha', 'foram', 'essa', 'num', 'nem', 'suas', 'meu', 'às', 'minha', 'têm', 'numa', 'pelos', 'elas', 'havia', 'seja', 'qual', 'será', 'nós', 'tenho', 'lhe', 'deles', 'essas',
                          'esses', 'pelas', 'este', 'fosse', 'dele', 'tu', 'te', 'vocês', 'vos', 'lhes', 'meus', 'minhas','teu', 'tua', 'teus', 'tuas', 'nosso', 'nossa', 'nossos', 'nossas', 'dela', 'delas', 'esta', 'estes', 'estas',
                          'aquele', 'aquela', 'aqueles', 'aquelas', 'isto', 'aquilo', 'estou', 'está', 'estamos', 'estão', 'estive', 'esteve', 'estivemos', 'estiveram', 'estava', 'estávamos', 'estavam', 'estivera', 'estivéramos', 'esteja',
                          'estejamos', 'estejam', 'estivesse', 'estivéssemos', 'estivessem', 'estiver', 'estivermos', 'estiverem', 'hei', 'há', 'havemos', 'hão', 'houve', 'houvemos', 'houveram', 'houvera', 'houvéramos', 'haja', 'hajamos',
                          'hajam', 'houvesse', 'houvéssemos', 'houvessem', 'houver', 'houvermos', 'houverem', 'houverei', 'houverá', 'houveremos', 'houverão', 'houveria', 'houveríamos', 'houveriam', 'sou', 'somos', 'são', 'era', 'éramos',
                          'eram', 'fui', 'foi', 'fomos', 'foram', 'fora', 'fôramos', 'seja', 'sejamos', 'sejam', 'fosse', 'fôssemos', 'fossem', 'for', 'formos', 'forem', 'serei', 'será', 'seremos', 'serão', 'seria', 'seríamos', 'seriam',
                          'tenho', 'tem', 'temos', 'tém', 'tinha', 'tínhamos', 'tinham', 'tive', 'teve', 'tivemos', 'tiveram', 'tivera', 'tivéramos', 'tenha', 'tenhamos', 'tenham', 'tivesse', 'tivéssemos', 'tivessem', 'tiver', 'tivermos',
                          'tiverem', 'terei', 'terá', 'teremos', 'terão', 'teria', 'teríamos', 'teriam');
        //debug_var_dump('string0', $string);
        //$string = utf8_encode($string);
        //debug_var_dump('string1', $string);
        $string = preg_replace('/\s\s+/i', '', $string); // replace whitespace
        //debug_var_dump('string2', $string);
        $string = trim($string); // trim the string
        //debug_var_dump('string3', $string);
        $string = preg_replace('/[^A-Za-z0-9\x{002D}\x{002E}\x{00C0}-\x{00FF} -]/', '', $string); // only take alphanumerical characters, but keep the spaces and dashes too…
      // debug_var_dump('string4', $string);
      $string = strtolower($string); // make it lowercase
      // debug_var_dump('string5', $string);

      // preg_match_all('/\b.*?\b/U', $string, $matchWords);
        // $matchWords = $matchWords[0];
        // debug_var_dump('matchwords', $matchWords);

        $wordCountArr = array();

        foreach (explode(' ', $string) as $word) {
            if ($word !== '' && !in_array(strtolower($word), $stopWords) && strlen($word) >= 3) {
                if (array_key_exists($word, $wordCountArr)) {
                    $wordCountArr[$word]++;
                } else {
                    $wordCountArr[$word] =  1;
                }
                //debug_var_dump('wordCountArr', $wordCountArr);
            }
        }

        // foreach ( $matchWords as $key=>$item ) {
        // if ( $item == '' || in_array(strtolower($item), $stopWords) || strlen($item) <= 3 ) {
        // unset($matchWords[$key]);
        // }
        // }
        // $wordCountArr = array();
        // if ( is_array($matchWords) ) {
        // foreach ( $matchWords as $key => $val ) {
        // $val = strtolower($val);
        // if ( isset($wordCountArr[$val]) ) {
        // $wordCountArr[$val]++;
        // } else {
        // $wordCountArr[$val] = 1;
        // }
        // }
        // }
        arsort($wordCountArr);
        //debug_var_dump('wordCountArr', $wordCountArr);
        $wordCountArr = array_slice($wordCountArr, 0, 10);
        return $wordCountArr;
    }
} // end of class LOM_DataHolder
