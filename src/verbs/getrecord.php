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
 * \brief Response to Verb GetRecord
 *
 * Retrieve a record based its identifier.
 *
 * Local variables <B>$metadataPrefix</B> and <B>$identifier</B> need to be provided through global array variable <B>$args</B>
 * by their indexes 'metadataPrefix' and 'identifier'.
 * The reset of information will be extracted from database based those two parameters.
 */
namespace Fccn\Oaipmh;

debug_message("Debuging". __FILE__) ;

$metadataPrefix = $args['metadataPrefix'];
// myhandler is a php file which will be included to generate metadata node.
// $inc_record  = $METADATAFORMATS[$metadataPrefix]['myhandler'];

//get handler for the metadata schema
$schema = fetch_metadata_schema($metadataPrefix);
if (isset($schema) && isset($schema['handler'])) {
    $inc_record = $schema['handler'];
} else {
    $errors[] = oai_error('cannotDisseminateFormat', 'metadataPrefix', $metadataPrefix);
}

$identifier = $args['identifier'];

//import source specific handler
require_once $corepath .'/sources/'. $config->repository->acqmethod. '/getrecord.php';
//-- should acquire the following: $full_identifier,$record_id,$status_deleted,$datestamp,$setspec

$outputObj = new ANDS_Response_XML($args);
$cur_record = $outputObj->create_record();
$cur_header = $outputObj->create_header($identifier, $datestamp, $setspec, $cur_record);

// return the metadata record itself
if (!$status_deleted) {
    include($inc_record); // where the metadata node is generated.
    create_metadata($outputObj, $cur_record, $identifier, $record_id, $setspec, $pdo);
} else {
    $cur_header->setAttribute("status", "deleted");
}
