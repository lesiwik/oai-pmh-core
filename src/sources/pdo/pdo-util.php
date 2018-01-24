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
 * \brief Utilities for the PDO datasource
 *
 * A collection of functions used.
 */
namespace Fccn\Oaipmh;

/**
 * Gets the connection for a PDO datasource
 */
function getDatasource()
{
    global $config;
    debug_message("datasource.php :: creating pdo connection...");
    try {
        //create PDO connection
        $db = new \PDO(str_replace(',', ';', $config -> pdo -> dsn), $config -> pdo -> user, $config -> pdo -> pass);
        return $db;
    } catch (\PDOException $e) {
        error_log("datasource.php :: Could not connect to PDO datasource.".$e->getMessage());
        http_err_exit();
    }
}

// Here are a couple of queries which might need to be adjusted to
// your needs. Normally, if you have correctly named the columns above,
// this does not need to be done.

/** this function should generate a query which will return
 * all records
 * the useless condition id_column = id_column is just there to ease
 * further extensions to the query, please leave it as it is.
 */
function selectallRecords($metadPrefix = "oai_dc", $filter, $id = '')
{
    global $config;
    if (!isset($config["pdo"][$metadPrefix]["listAll"])) {
        //fall to default oai metadata prefix
        $metadPrefix = $config["pdo"]["oai_default"];
    }

    if (isset($config["pdo"][$metadPrefix]["listAll"])) {
        $query = "SELECT * FROM (".$config["pdo"][$metadPrefix]["listAll"] . ") oai_records WHERE " . $config->pdo->identifier . " = " . $config->pdo->identifier . $filter;
        if ($id != '') {
            $query .= " AND ".$config->pdo->identifier." ='".get_record_id($id)."'";
        }

        return $query;
    }
    return null;
}

/** filter for until, appends to the end of SQL query */
function untilFilter($until)
{
    global $config;
    return ' AND '.$config->pdo->datestamp." <= '$until'";
}

/** filter for from , appends to the end of SQL query */
function fromFilter($from)
{
    global $config;
    return ' AND '.$config->pdo->datestamp." >= '$from'";
}

/** filter for sets,  appends to the end of SQL query */
function setFilter($set)
{
    global $config;
    return ' AND '.$config->pdo->setspec." = '$set'";
}

/** for accurately to assess how many records satisfy conditions for all DBs */
function rowCount($query, $pdo)
{
    $n = 0;
    $sql = "SELECT COUNT(*) FROM (". $query . ") tmpTable ";
    debug_message('>>> rowCount query: '.$sql);
    if ($res = $pdo->query($sql)) {
        $n = $res->fetchColumn();
    }
    return $n;
}

/** A worker function for processing an error when a query was executed
 * \param $query string, original query
 * \param $e PDOException, the PDOException object
*/
function process_pdo_error($query, $e)
{
    echo $query.' was failed\n';
    echo $e->getMessage();
}

/** When query return no result, throw an Exception of Not found.
 * \param $pdo PDO
 * \param $query string
 * \return $res PDOStatement
 */
function exec_pdo_query($pdo, $query)
{
    $res = $pdo->query($query);
    if ($res===false) {
        throw new Exception($query.":\nIt found nothing.\n");
    } else {
        return $res;
    }
}
