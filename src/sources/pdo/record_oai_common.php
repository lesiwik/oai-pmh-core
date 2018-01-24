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

/** \file
 * \brief common handler for oai metadata
 *
 * Defines common functionalities for oai metadata handling and database retrieval
 *
 * @author Paulo Costa <paulo.costa@fccn.pt>
 *
 */
namespace Fccn\Oaipmh;

function prepare_metadata($record_id, $setspec, $pdo, $meta_format)
{
    global $config; //, $corepath;

    // require_once($corepath.'/schemas/ands_dces.php');

    debug_message('****** In '.__FILE__.' function '.__FUNCTION__.' was called.');

    //set metadataformat to fallback to
    $fallback_mformat = $config['pdo']['oai_default'];

    //run query to obtain a pre-established metadata table
    if (isset($config['pdo'][$meta_format][$setspec]['fetch'])) {
        //query is specific to set
        // debug_message("record_dc :: query specific to $setspec");
        $query = $config['pdo'][$meta_format][$setspec]['fetch']." WHERE ".$config['pdo'][$meta_format][$setspec]['identifier']." = '$record_id'";
    } elseif (isset($config['pdo'][$fallback_mformat][$setspec]['fetch'])) {
        //check fallback metadataformat
        $query = $config['pdo'][$fallback_mformat][$setspec]['fetch']." WHERE ".$config['pdo'][$fallback_mformat][$setspec]['identifier']." = '$record_id'";
    } elseif (isset($config['pdo'][$meta_format]['all']['fetch'])) {
        //run a general query
        // debug_message("record_dc :: general query");
        $query = $config['pdo'][$meta_format]['all']['fetch']." WHERE ".$config['pdo'][$meta_format]['all']['identifier']." = '$record_id'";
    } elseif (isset($config['pdo'][$fallback_mformat]['all']['fetch'])) {
        //general query with fallback meta_format
        $query = $config['pdo'][$fallback_mformat]['all']['fetch']." WHERE ".$config['pdo'][$fallback_mformat]['all']['identifier']." = '$record_id'";
    } else {
        error_log("record_oai_common.php :: no fetch query for set=$setspec");
        exit("Unable to process request");
    }

    debug_message("pdo/record_oai_common.php :: Query: $query") ;

    //run query
    $res = $pdo->prepare($query);
    $r = $res->execute();
    if ($r===false) {
        debug_message(__FILE__.','.__LINE__);
        debug_message("Query:".$query);
        error_log('error_info', $pdo->errorInfo());
        //exit();
        $errors[] = oai_error('noRecordsMatch');
    }

    if (!empty($errors)) {
        oai_exit();
    }

    return $res;

    // debug_message("record_dc :: query run successfull...");
    //create metadata node
    // $metadata_node = $outputObj->create_metadata($cur_record);
    // $obj_node = new ANDS_DCES($outputObj, $metadata_node, $res);
    // $obj_node->generate_metadata($identifier,$res);
}
