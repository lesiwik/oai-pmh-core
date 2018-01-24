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
 * \brief Dublin Core PDO handler
 *
 * Defines functionalities for handling Dublin Core metadata (oai_dc) and database retrieval
 *
 */
namespace Fccn\Oaipmh;

function create_metadata($outputObj, $cur_record, $identifier, $record_id, $setspec, $pdo)
{
    global $config, $corepath;

    require_once($corepath.'/sources/pdo/record_oai_common.php');
    require_once($corepath.'/schemas/ands_dces.php');

    debug_message('****** In '.__FILE__.' function '.__FUNCTION__.' was called.');
    //get pre-established metadata table
    $res = prepare_metadata($record_id, $setspec, $pdo, 'oai_dc');

    // debug_message("record_dc :: query run successfull...");
    //create metadata node
    $metadata_node = $outputObj->create_metadata($cur_record);
    $obj_node = new ANDS_DCES($outputObj, $metadata_node);
    $obj_node->generate_metadata($identifier, $res);
}
