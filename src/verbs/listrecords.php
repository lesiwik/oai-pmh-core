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
 * \brief Response to Verb ListRecords
 *
 * Lists records according to conditions. If there are too many, a resumptionToken is generated.
 * - If a request comes with a resumptionToken and is still valid, read it and send back records.
 * - Otherwise, set up a query with conditions such as: 'metadataPrefix', 'from', 'until', 'set'.
 * Only 'metadataPrefix' is compulsory.  All conditions are accessible through global array variable <B>$args</B>  by keywords.
 */
namespace Fccn\Oaipmh;

debug_message("Debuging ". __FILE__) ;

// check max items for resumption token
if ($args['verb']=='ListRecords') {
    $maxItems = $config->repository->maxrecords; //MAXRECORDS;
} elseif ($args['verb']=='ListIdentifiers') {
    $maxItems = $config->repository->maxids; //MAXIDS;
} else {
    exit("Check ".__FILE__." ".__LINE__.", there is something wrong.");
}

//import source specific handler
require_once $corepath.'/sources/'.$config->repository->acqmethod.'/listrecords.php';

// end ListRecords
debug_message("***** Debug listrecord.php reached to the end. *******");
