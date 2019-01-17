<?php

/**
 *  Adapted from OAI Data Provider by Jianfeng Li
 */

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
 * \brief Response to Verb Identify
 *
 * Tell the world what the data provider is. Usually it is static once the provider has been set up.
 *
 * \see http://www.openarchives.org/OAI/2.0/guidelines-oai-identifier.htm for details
 */

namespace Fccn\Oaipmh;

debug_var_dump('identifty_config', $config);

//response
$identifyResponse = array(
    'repositoryName' => $config['repository']['name'],
    'baseURL' => $config['repository']['baseURL'],
    'protocolVersion' => $config['repository']['protocolVersion'],
    'adminEmail' => $config['repository']['adminEmail'],
    'earliestDatestamp' => $config['repository']['earliestDatestamp'],
    'deletedRecord' => $config['repository']['deletedRecord'],
    'granularity' => $config['repository']['granularity']
);
//base url
//if (isset($config['repository']['baseURL'])) {
//    $identifyResponse['baseURL'] = $config['repository']['baseURL'];
//} else {
//    $identifyResponse['baseURL'] = $_SERVER['SERVER_NAME'];
//}

//$adminEmail = $config['repository']['adminEmail'];
$identifyResponseBranding = $config['branding'];

if (isset($config['rights'])) {
    $identifyRights = $config['rights'];
}

$outputObj = new ANDS_Response_XML($args);

foreach ($identifyResponse as $key => $val) {
    $outputObj->add2_verbNode($key, $val);
}

//foreach ($adminEmail as $val) {
//    $outputObj->add2_verbNode("adminEmail", $val);
//}

if (isset($config['repository']['compression'])) {
    debug_var_dump('identifty_compression', $config['repository']['compression']);
    foreach ($config['repository']['compression'] as $val) {
        $outputObj->add2_verbNode("compression", $val);
    }
}

// A description MAY be included.
// Use this if you choose to comply with a specific format of unique identifiers
// for items.
// See http://www.openarchives.org/OAI/2.0/guidelines-oai-identifier.htm
// for details

// As they will not be changed, using string for simplicity.

//OAI Identifier description
$output = '';
if (get_ini_boolean($config['repository']['show_identifier']) && $config['repository']['identifier'] && $config['repository']['delimiter']) {
    $output .=
'  <description>
   <oai-identifier xmlns="http://www.openarchives.org/OAI/2.0/oai-identifier"
                   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                   xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/oai-identifier
                   http://www.openarchives.org/OAI/2.0/oai-identifier.xsd">
    <scheme>oai</scheme>
    <repositoryIdentifier>'.$config['repository']['identifier'].'</repositoryIdentifier>
    <delimiter>'.$config['repository']['delimiter'].'</delimiter>
    <sampleIdentifier>oai'.$config['repository']['delimiter'].$config['repository']['identifier'].$config['repository']['delimiter'].'sample</sampleIdentifier>
   </oai-identifier>
  </description>'."\n";
}

// If you want to provide branding information, adjust accordingly.
// Usage of friends container is OPTIONAL.
// see http://www.openarchives.org/OAI/2.0/guidelines-branding.htm
// for details

function wrapDom($a)
{
    $block = "";
    foreach ($a as $tag => $value) {
        $block .= "<" . $tag. ">" . $value . "</" . $tag . ">";
    }
    return $block;
}

// Repository branding
if (isset($identifyResponseBranding)) {
    $output .=
'  <description>
   <branding xmlns="http://www.openarchives.org/OAI/2.0/branding/"
             xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
             xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/branding/ http://www.openarchives.org/OAI/2.0/branding.xsd">
    <collectionIcon>' . wrapDom($identifyResponseBranding["collectionIcon"]) . '</collectionIcon>
    <metadataRendering
     metadataNamespace="http://www.openarchives.org/OAI/2.0/oai_dc/"
     mimeType="text/xsl">' . $identifyResponseBranding["xsl"] . '</metadataRendering>
    <metadataRendering
     metadataNamespace="' . $identifyResponseBranding["marc"] . '"
     mimeType="text/css">' . $identifyResponseBranding["marc_css"] . '</metadataRendering>
   </branding>
  </description>'."\n";
}

//rights expressions
if (isset($identifyRights)) {
    $output .=
    '<description>
		  <rightsManifest
		    xmlns="http://www.openarchives.org/OAI/2.0/rights/"
		    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
		    xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/rights/
		                        http://www.openarchives.org/OAI/2.0/rightsManifest.xsd"
		    appliesTo="http://www.openarchives.org/OAI/2.0/entity#metadata">
		    <rights>
		      <rightsDefinition>
		        <oai_dc:dc xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/"
		          xmlns:dc="http://purl.org/dc/elements/1.1/"
		          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
		          xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/oai_dc/
		                              http://www.openarchives.org/OAI/2.0/oai_dc.xsd">
		          <dc:title>'.$identifyRights['name'].'</dc:title>
		          <dc:date>'.$identifyRights['date'].'</dc:date>
		          <dc:creator>'.$identifyRights['creator'].'</dc:creator>
		          <dc:description>'.$identifyRights['description'].'</dc:description>
		          <dc:identifier>'.$identifyRights['identifier'].'</dc:identifier>
		        </oai_dc:dc>
		      </rightsDefinition>
		    </rights>
		    <rights>
		      <rightsReference ref="'.$identifyRights['reference'].'"/>
		    </rights>
		  </rightsManifest>
	</description>'."\n";
}

//toolkit description
// if (true) {
//   $output .=
// '  <description>
//      <toolkit xmlns="http://oai.dlib.vt.edu/OAI/metadata/toolkit"
//               xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
//               xsi:schemaLocation="http://oai.dlib.vt.edu/OAI/metadata/toolkit http://oai.dlib.vt.edu/OAI/metadata/toolkit.xsd">
//        <title>OAI-PMH PHP Connector</title>
//        <author>
//          <name>Rui Ribeiro</name><email>rui.ribeiro@fccn.pt</email>
//          <institution>FCCN|FCT</institution>
//        </author>
//        <version>' . $config['debug']['version'] . '</version>
//        <toolkitIcon>http://alcme.oclc.org/oaicat/oaicat_icon.gif</toolkitIcon>
//        <URL>http://www.oclc.org/research/software/oai/cat.shtm</URL>
//      </toolkit>
//    </description>' . "\n";
// }

if (strlen($output)>10) {
    $des = $outputObj->getDoc()->createDocumentFragment();
    $des->appendXML($output);
    $outputObj->verbNode->appendChild($des);
}
