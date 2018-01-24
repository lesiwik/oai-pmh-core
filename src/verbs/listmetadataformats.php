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
 * \brief Response to Verb ListMetadataFormats
 *
 * The information of supported metadata formats is saved in database and retrieved by calling function <B>idFormatQuery</B>.
 * \sa idFormatQuery
 */

namespace Fccn\Oaipmh;

debug_message("debugging ".__FILE__);

/**
 * Add a metadata format node to an ANDS_Response_XML
 * \param &$outputObj
 *	type: ANDS_Response_XML. The ANDS_Response_XML object for output.
 * \param $key
 * 	type string. The name of new node.
 * \param $val
 * 	type: array. Values accessable through keywords 'schema' and 'metadataNamespace'.
 *
 */
function addMetadataFormat(&$outputObj, $format, $schema)
{
    if (isset($schema)) {
        $cmf = $outputObj->add2_verbNode("metadataFormat");
        $outputObj->addChild($cmf, 'metadataPrefix', $schema['metadataPrefix']);
        $outputObj->addChild($cmf, 'schema', $schema['schema']);
        $outputObj->addChild($cmf, 'metadataNamespace', $schema['metadataNamespace']);
        return true;
    } else {
        error_log("listmetadataformats.php -> unknown metadata format: ".$format);
        return false;
    }
}

if (isset($args['identifier'])) {
    $identifier = $args['identifier'];
    $mf = array();

    //import source specific handler
    require_once $corepath.'/sources/'.$config->repository->acqmethod.'/listmetadataformats.php';
    //-- should provide metadata formats to $mf

    //check if any metadata format was detected
    if (empty($mf)) {
        $errors[] = oai_error('idDoesNotExist', '', $identifier);
    }
}

//break and clean up on error
if (!empty($errors)) {
    oai_exit();
}

$outputObj = new ANDS_Response_XML($args);

require_once($corepath . '/schemas/metadataformats.php');

if (isset($mf)) {
    foreach ($mf as $format) {
        if (!addMetadataFormat($outputObj, $format, fetch_metadata_schema($format))) {
            $errors[] = oai_error('noMetadataFormats');
            oai_exit();
        }
    }
} elseif (isset($config->repository->metadataformats) && is_array($config->repository->metadataformats)) {
    foreach ($config->repository->metadataformats as $format) {
        //debug_var_dump('metadata_schema', fetch_metadata_schema($format));
        if (!addMetadataFormat($outputObj, $format, fetch_metadata_schema($format))) {
            $errors[] = oai_error('noMetadataFormats');
            oai_exit();
        }
    }
} else { // a very unlikely event
    $errors[] = oai_error('noMetadataFormats');
    oai_exit();
}
