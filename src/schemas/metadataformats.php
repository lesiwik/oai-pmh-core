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
 * \brief auxiliary functions to fill in metadata schema
 *
 * @author Paulo Costa <paulo.costa@fccn.pt>
 *
 */
namespace Fccn\Oaipmh;

function fetch_metadata_schema($format = '')
{
    global $config, $corepath;

    $schemas = array('oai_dc' =>
        array('metadataPrefix' => 'oai_dc',
            'schema' => 'http://www.openarchives.org/OAI/2.0/oai_dc.xsd',
            'metadataNamespace' => 'http://www.openarchives.org/OAI/2.0/oai_dc/',
            'record_prefix' => 'dc',
            'record_namespace' => 'http://purl.org/dc/elements/1.1/',
            'handler' => $corepath.'/sources/'.$config->repository->acqmethod.'/record_dc.php'
         ), 'oai_lom' =>
        array('metadataPrefix' => 'oai_lom',
            'schema' => 'http://ltsc.ieee.org/xsd/lomv1.0/lom.xsd',
            'metadataNamespace' => 'http://ltsc.ieee.org/xsd/LOM',
            'record_prefix' => 'lom',
            'record_namespace' => 'http://ltsc.ieee.org/xsd/LOM',
            'handler' => $corepath.'/sources/'.$config->repository->acqmethod.'/record_lom.php'
         )
        //add other metadata schemas here
    );

    if ($format != '') {
        return $schemas[$format];
    } else {
        return $schemas;
    }
}
