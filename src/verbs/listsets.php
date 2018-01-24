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
 * \brief Response to Verb ListSets
 *
 * Lists what sets are available to records in the system.
 */

namespace Fccn\Oaipmh;

debug_message("Entering listsets.php");

// Here the size of sets is small, no resumptionToken is taken care.
if (is_array($config->repository->sets)) {
    $outputObj = new ANDS_Response_XML($args);
    foreach ($config->repository->sets as $set) {
        parse_str($set, $parsed_set);
        debug_var_dump('parsed_set_in_listsets', $parsed_set);
        $setNode = $outputObj->add2_verbNode("set");
        foreach ($parsed_set as $key => $val) {
            if ($key=='setDescription') {
                $desNode = $outputObj->addChild($setNode, $key);
                $des = $outputObj->doc->createDocumentFragment();
                $des->appendXML($val);
                $desNode->appendChild($des);
            } elseif ($key=='setSpec') {
                $outputObj->addChild($setNode, $key, $val);
            }
            //ignore the rest of the keys
        }
    }
} else {
    $errors[] = oai_error('noSetHierarchy');
    oai_exit();
}
