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
 * \brief PDO specific implementation of the Response to Verb GetRecord for PDO
 *
 */
 namespace Fccn\Oaipmh;

require_once 'pdo-util.php';

$query = selectallRecords($metadataPrefix, '', $identifier);

debug_message("getRecord.php :: Query: $query") ;

$pdo = getDatasource();

$res = $pdo->query($query);

if ($res===false) {
    error_log(__FILE__.','.__LINE__);
    error_log("Query:".$query);
    error_log($pdo->errorInfo());
    $errors[] = oai_error('idDoesNotExist', '', $identifier);
}

if (!empty($errors)) {
    oai_exit();
}

$record = $res->fetch(\PDO::FETCH_ASSOC);
debug_var_dump('query result', $record);
if ($record===false) {
    debug_message(__FILE__.','.__LINE__);
    debug_message("getRecord.php :: record with id $identifier not found");
    $errors[] = oai_error('idDoesNotExist', '', $identifier);
}

if (!empty($errors)) {
    oai_exit();
}

//--

$record_id = $record[$config->pdo->identifier];
$identifier = get_global_id($record_id);

$datestamp = formatDatestamp($record[$config->pdo->datestamp]);
    // debug_var_dump('datestamp', $datestamp);

$setspec = $record[$config->pdo->setspec];


if (isset($record[$config->pdo->delspec]) && (get_bool($record[$config->pdo->delspec])) &&
    ($config->repository->deletedRecord == 'transient' || $config->repository->deletedRecord == 'persistent')) {
    $status_deleted = true;
} else {
    $status_deleted = false;
}

//-- should provide the following: $full_identifier,$record_id,$status_deleted,$datestamp,$setspec
