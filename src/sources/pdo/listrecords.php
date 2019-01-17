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
 * \brief PDO implementation of response to Verb ListRecords
 *
 * Lists records according to conditions. If there are too many, a resumptionToken is generated.
 * - If a request comes with a resumptionToken and is still valid, read it and send back records.
 * - Otherwise, set up a query with conditions such as: 'metadataPrefix', 'from', 'until', 'set'.
 * Only 'metadataPrefix' is compulsory.  All conditions are accessible through global array variable <B>$args</B>  by keywords.
 */
namespace Fccn\Oaipmh;

require_once 'pdo-util.php';

// Resume previous session?
if (isset($args['resumptionToken'])) {
    debug_message("listRecords.php::entering resumption token");
    if (!file_exists($config->repository->token->path.$args['resumptionToken'])) {
        $errors[] = oai_error('badResumptionToken', '', $args['resumptionToken']);
    } else {
        $readings = readResumToken($config->repository->token->path.$args['resumptionToken']);
        if ($readings == false) {
            $errors[] = oai_error('badResumptionToken', '', $args['resumptionToken']);
        } else {
            debug_var_dump('readings', $readings);
            list($deliveredrecords, $extquery, $metadataPrefix, $filter) = $readings;
        }
    }
} else { // no, we start a new session
    $deliveredrecords = 0;
    //use extquery to limit results in case of mysql (No support for PDO::FETCH_ORI_ABS)
    if ($config->pdo->kind === 'mysql') {
        $extquery =  " LIMIT 0, $maxItems";
    } else {
        $extquery = '';
    }

    $metadataPrefix = $args['metadataPrefix'];

    $filter = '';

    if (isset($args['from'])) {
        $from = checkDateFormat($args['from']);
        $filter .= fromFilter($from);
    }

    if (isset($args['until'])) {
        $until = checkDateFormat($args['until']);
        $filter .= untilFilter($until);
    }

    if (isset($args['set'])) {
        if (is_array($config->repository->sets)) {
            foreach ($config->repository->sets as $set) {
                parse_str($set, $parsed_set);
                debug_var_dump("parsed_set", $parsed_set);
                debug_message("listRecords.php :: ".$parsed_set['setSpec']." == ".$args['set']);
                if ($parsed_set['setSpec'] == $args['set']) {
                    $filter .= setFilter($args['set']);
                    $found_set = true;
                    break;
                }
            }
            if (!isset($found_set)) {
                $errors[] = oai_error('noRecordsMatch');
            }
        } else {
            $errors[] = oai_error('noSetHierarchy');
        }
    }
}

if (!empty($errors)) {
    oai_exit();
}

require_once($corepath . '/schemas/metadataformats.php');

// Load the handler
//debug_var_dump("metadata_formats", $config['metadataformats']);
$schema = fetch_metadata_schema($metadataPrefix);
if (isset($schema) && isset($schema['handler'])) {
    include($schema['handler']);
} else {
    $errors[] = oai_error('cannotDisseminateFormat', 'metadataPrefix', $metadataPrefix);
}

if (!empty($errors)) {
    oai_exit();
}

//select all records
if (empty($errors)) {
    debug_message("entering select all query");
    $query = selectallRecords($schema['metadataPrefix'], $filter) . $extquery;
    $pdo = getDatasource('pdo');
    debug_message("####Query: $query") ;

    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

    $res = $pdo->prepare($query, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_SCROLL));
    $r = $res->execute();
    if ($r===false) {
        debug_message(__FILE__.','.__LINE__);
        debug_message("Query:".$query);
        error_log($pdo->errorInfo());
        $errors[] = oai_error('noRecordsMatch');
    } else {
        $r = $res->setFetchMode(\PDO::FETCH_ASSOC);
        if ($r===false) {
            exit("FetchMode is not supported");
        }
        $num_rows = rowCount(selectallRecords($schema['metadataPrefix'], $filter), $pdo);
        if ($num_rows==0) {
            debug_message("Cannot find records: ".$query);
            $errors[] = oai_error('noRecordsMatch');
        }
    }
}

if (!empty($errors)) {
    oai_exit();
}

$maxrec = min($num_rows - $deliveredrecords, $maxItems);

debug_message(">>>listrecords-checkpoint 01: maxrec=".$maxrec.", maxItems=".$maxItems.", deliveredRecords=".$deliveredrecords.", numrows=".$num_rows);

if ($num_rows - $deliveredrecords > $maxItems) {
    $cursor = (int)$deliveredrecords + $maxItems;
    //update extquery in case of mysql
    if ($config->pdo->kind === 'mysql') {
        $extquery = " LIMIT $cursor, $maxItems";
    }
    $restoken = createResumToken($cursor, $extquery, $metadataPrefix, $filter);
    $expirationdatetime = gmstrftime('%Y-%m-%dT%TZ', time()+$config->repository->token->validity);
}
// Last delivery, return empty ResumptionToken
elseif (isset($args['resumptionToken'])) {
    $restoken = $args['resumptionToken']; // just used as an indicator
    unset($expirationdatetime);
}

//DOES NOT WORK WITH MYSQL
if ($config->pdo->kind !== 'mysql' && isset($args['resumptionToken'])) {
    debug_message("Try to resume because a resumptionToken supplied. Fetching from row #$deliveredrecords") ;
    $record = $res->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_ABS, $deliveredrecords);
}
// Record counter
$countrec  = 0;



debug_message(">>>listrecords-checkpoint 02: from=$deliveredrecords | count=$countrec | max=$maxrec");

$outputObj = new ANDS_Response_XML($args);

// Publish a batch to $maxrec number of records
while ($countrec++ < $maxrec) {
    $record = $res->fetch(\PDO::FETCH_ASSOC);
    if ($record===false) {
        debug_message(__FILE__.",". __LINE__);
        error_log($pdo->errorInfo());
        exit();
    }

    // debug_var_dump('record', $record);

    //----

    $record_id = $record[$config->pdo->identifier];
    $identifier = get_global_id($record_id);

    // $config->repository->prefix.$config->repository->delimiter
    // .$config->repository->identifier.$config->repository->delimiter
    // .$record_id;

    debug_var_dump('identifier', $identifier);

    $datestamp = formatDatestamp($record[$config->pdo->datestamp]);
    // debug_var_dump('datestamp', $datestamp);

    //$setspec = $record[$config->pdo->setspec];
    $setspec = strtr($record[$config->pdo->setspec],[' '=>'']);

    // debug_var_dump('setspec', $setspec);

    // debug_var_dump('record', $record);
    if (isset($record[$config->pdo->delspec]) && (get_bool($record[$config->pdo->delspec])) &&
        ($config->repository->deletedRecord == 'transient' || $config->repository->deletedRecord == 'persistent')) {
        $status_deleted = true;
    } else {
        $status_deleted = false;
    }
    debug_var_dump('deleted01', isset($record[$config->pdo->delspec]));
    debug_var_dump('deleted02', get_bool($record[$config->pdo->delspec]));
    debug_var_dump('deleted03', $config->repository->deletedRecord);
    debug_var_dump('status_deleted', $status_deleted);

    //---

    if ($args['verb']=='ListRecords') {
        $cur_record = $outputObj->create_record();
        $cur_header = $outputObj->create_header($identifier, $datestamp, $setspec, $cur_record);
        // return the metadata record itself
        if (!$status_deleted) {
            //debug_var_dump('inc_record',$inc_record);
            create_metadata($outputObj, $cur_record, $identifier, $record_id, $setspec, $pdo);
        }
    } else { // for ListIdentifiers, only identifiers will be returned.
        $cur_header = $outputObj->create_header($identifier, $datestamp, $setspec);
    }
    if ($status_deleted) {
        $cur_header->setAttribute("status", "deleted");
    }
}

// ResumptionToken
if (isset($restoken)) {
    if (isset($expirationdatetime)) {
        $outputObj->create_resumpToken($restoken, $expirationdatetime, $num_rows, $cursor);
    } else {
        $outputObj->create_resumpToken('', null, $num_rows, $deliveredrecords);
    }
}
