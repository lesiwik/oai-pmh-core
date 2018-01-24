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
 * \brief Utilities for the OAI Data Provider
 *
 * A collection of functions for OAI metadata.
 */

namespace Fccn\Oaipmh;

/**
 * setup Initial definitions
 */
define('SHOW_DEBUG', get_ini_boolean($config->debug->show_messages));
//debug_var_dump("$print_debug", $print_debug);

if (SHOW_DEBUG && !isset($config->debug->logfile)) {
    define('CONTENT_TYPE', 'Content-Type: text/plain');
} else {
    // If everything is running ok, you should use this
    define('CONTENT_TYPE', 'Content-Type: text/xml');
}

date_default_timezone_set('UTC');

/**
 * calculates the boolean value from a element in a ini file
 */
function get_ini_boolean($my_boolean)
{
    if ((int) $my_boolean > 0) {
        $my_boolean = true;
    } else {
        $my_lowered_boolean = strtolower($my_boolean);
        if ($my_lowered_boolean === "true" || $my_lowered_boolean === "on" || $my_lowered_boolean === "yes") {
            $my_boolean = true;
        } else {
            $my_boolean = false;
        }
    }

    return $my_boolean;
}

/** Dump information of a varible for debugging,
 * only works when $print_debug is true.
 * \param $var_name Type: string Name of variable is being debugded
 * \param $var Type: mix Any type of varibles used in PHP
 * \see $print_debug in oaidp-config.php
 */
function debug_var_dump($var_name, $var)
{
    if (SHOW_DEBUG) {
        log_to_file("##DEBUG[".strftime("%F %T")."] - Dumping \${$var_name}: ");
        ob_start();
        var_dump($var);
        log_to_file(ob_get_clean());
    }
}

/** Prints human-readable information about a variable for debugging,
 * only works when $print_debug is true.
 * \param $var_name Type: string Name of variable is being debugded
 * \param $var Type: mix Any type of varibles used in PHP
 * \see $print_debug in oaidp-config.php
 */
function debug_print_r($var_name, $var)
{
    if (SHOW_DEBUG) {
        log_to_file("##DEBUG[".strftime("%F %T")."] - Printing \${$var_name}:");
        ob_start();
        print_r($var);
        log_to_file(ob_get_clean());
    }
}

/** Prints a message for debugging,
 * only works when $print_debug is true.
 * PHP function print_r can be used to construct message with <i>return</i> parameter sets to true.
 * \param $msg Type: string Message needs to be shown
 * \see $print_debug in oaidp-config.php
 */
function debug_message($msg)
{
    if (!SHOW_DEBUG) {
        return;
    }
    log_to_file("##DEBUG[".strftime("%F %T")."] - ".$msg);
}

/**
 * Logs this message to a pre-defined file
 */
function log_to_file($msg)
{
    global $config;

    if (isset($config->debug->logfile)) {
        try {
            file_put_contents($config->debug->logfile, $msg."\n", FILE_APPEND);
        } catch (FileException $e) {
            error_log("caught exception ".$e->getMessage());
            echo "<p>$msg</p>";
        }
        #file_put_contents($config['debug']['file'], "##-----      #      ----------\n", FILE_APPEND);
    } else {
        echo "debug file not set... \n";
        echo $msg,"\n";
    }
}

/** Check if provided correct arguments for a request.
 *
 * Only number of parameters is checked.
 * metadataPrefix has to be checked before it is used.
 * set has to be checked before it is used.
 * resumptionToken has to be checked before it is used.
 * from and until can easily checked here because no extra information
 * is needed.
 */
function checkArgs($args, $checkList)
{
    global $errors,  $config, $corepath;
    //	$verb = $args['verb'];
    unset($args["verb"]);

    //debug_print_r('checkList',$checkList);
    //debug_print_r('args',$args);

    // "verb" has been checked before, no further check is needed
    if (isset($checkList['required'])) {
        for ($i = 0; $i < count($checkList["required"]); $i++) {
            //debug_message("Checking: par$i: ". $checkList['required'][$i] . " in ");
            //debug_var_dump("isset(\$args[\$checkList['required'][\$i]])",isset($args[$checkList['required'][$i]]));
            // echo "key exists". array_key_exists($checkList["required"][$i],$args)."\n";
            if (isset($args[$checkList['required'][$i]])==false) {
                // echo "caught\n";
                $errors[] = oai_error('missingArgument', $checkList["required"][$i]);
            } else {
                // if metadataPrefix is set, it is in required section
                if (isset($args['metadataPrefix'])) {
                    $metadataPrefix = $args['metadataPrefix'];
                    // Check if the format is supported, it has enough infor (an array), last if a handle has been defined.
                    require_once($corepath . '/schemas/metadataformats.php');
                    $schema = fetch_metadata_schema($metadataPrefix);
                    if (!isset($schema) || !(is_array($schema)
                        || !isset($schema->handler))) {
                        $errors[] = oai_error('cannotDisseminateFormat', 'metadataPrefix', $metadataPrefix);
                    }
                }
                unset($args[$checkList["required"][$i]]);
            }
        }
    }
    //debug_message('Before return');
    //debug_print_r('errors',$errors);
    if (!empty($errors)) {
        return;
    }

    // check to see if there is unwanted
    foreach ($args as $key => $val) {
        debug_message("checkArgs: $key");
        if (!in_array($key, $checkList["ops"])) {
            debug_message("Wrong\n".print_r($checkList['ops'], true));
            $errors[] = oai_error('badArgument', $key, $val);
        }
        switch ($key) {
            case 'from':
            case 'until':
                if (!checkDateFormat($val)) {
                    $errors[] = oai_error('badGranularity', $key, $val);
                }
            break;

            case 'resumptionToken':
            // only check for expairation
                if ((int)$val+$config->repository->token->validity < time()) {
                    $errors[] = oai_error('badResumptionToken');
                }
            break;
        }
    }
}

/** Validates an identifier. The pattern is: '/^[-a-z\.0-9]+$/i' which means
 * it accepts -, letters and numbers.
 * Used only by function <B>oai_error</B> code idDoesNotExist.
 * \param $url Type: string
 */
function is_valid_uri($url)
{
    return((bool)preg_match('/^[-a-z\.0-9]+$/i', $url));
}

/** Validates attributes come with the query.
 * It accepts letters, numbers, ':', '_', '.' and -.
 * Here there are few more match patterns than is_valid_uri(): ':_'.
 * \param $attrb Type: string
 */
 function is_valid_attrb($attrb)
 {
     return preg_match("/^[_a-zA-Z0-9\-\:\.]+$/", $attrb);
 }

/**
 * converts the variable to a boolean
 * 1, '1', 'yes', true, 'true' -> true
 * otherwise -> false
 */
function get_bool($val)
{
    //debug_var_dump('val', $val);
    if ($val === 1 || $val === "1" || $val === "yes" || $val === true || $val === 'true') {
        return true;
    }
    return false;
}

/** All datestamps used in this system are GMT even
 * return value from database has no TZ information
 */
function formatDatestamp($datestamp)
{
    return date("Y-m-d\TH:i:s\Z", strtotime($datestamp));
}

/** The database uses datastamp without time-zone information.
 * It needs to clean all time-zone informaion from time string and reformat it
 */
function checkDateFormat($date)
{
    $date = str_replace(array("T","Z"), " ", $date);
    debug_message("oaidp-util.php :: date: $date");
    $time_val = strtotime($date);
    debug_message("oaidp-util.php :: timeval: $time_val");
    if (!$time_val) {
        return false;
    }
    if (strstr($date, ":")) {
        return date("Y-m-d H:i:s", $time_val);
    } else {
        return date("Y-m-d", $time_val);
    }
}

/** Retrieve all defined 'setSpec' from configuraiton of $SETS.
 * It is used by ANDS_TPA::create_obj_node();
*/
function prepare_set_names()
{
    global $SETS;
    $n = count($SETS);
    $a = array_fill(0, $n, '');
    for ($i = 0; $i <$n; $i++) {
        $a[$i] = $SETS[$i]['setSpec'];
    }
    return $a;
}

/** Finish a request when there is an error: send back errors. */
function oai_exit()
{
    //	global $CONTENT_TYPE;
    header(CONTENT_TYPE);
    global $args,$errors,$compress;
    debug_var_dump("args_in_oaidputil", $args);
    debug_var_dump("errors_in_oaidputil", $errors);
    debug_var_dump("compress_in_oaidputil", $compress);
    $e = new ANDS_Error_XML($args, $errors);
    if ($compress) {
        ob_start('ob_gzhandler');
    }

    $e->display();

    if ($compress) {
        ob_end_flush();
    }

    exit();
}

/** Finish with a 500 error */
function http_err_exit()
{
    header("HTTP/1.1 500 Internal Server Error");
    echo '...';
    exit();
}

// ResumToken section
/** Generate a string based on the current Unix timestamp in microseconds for creating resumToken file name. */
function get_token()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((int)($usec*1000) + (int)($sec*1000));
}

/** Create a token file.
 * It has four parts which separated by '#': cursor, extension of query, metadataPrefix, filter.
 * Called by listrecords.php.
 */
function createResumToken($cursor, $extquery, $metadataPrefix, $filter)
{
    global $config;
    $token = get_token();
    /** Where token is saved and path is included */
    //define('TOKEN_PREFIX','/tmp/ANDS_DBPD-');
    $fp = fopen($config->repository->token->path.$token, 'w');
    if ($fp==false) {
        debug_message("Cannot write to ".$config->repository->token->path.$token. ". Writer permission needs to be changed.");
        exit("Cannot write token. Writer permission needs to be changed.");
    }
    $timeout = time() + (30 * 60); //set expiration to 30m in the future
    fputs($fp, "$cursor#");
    fputs($fp, "$extquery#");
    fputs($fp, "$metadataPrefix#");
    fputs($fp, "$filter#");
    fputs($fp, "$timeout#");
    fclose($fp);
    return $token;
}

/** Read a saved ResumToken */
function readResumToken($resumptionToken)
{
    $rtVal = false;
    $fp = fopen($resumptionToken, 'r');
    if ($fp!=false) {
        $filetext = fgets($fp, 255);
        $textparts = explode('#', $filetext);
        fclose($fp);
        #unlink ($resumptionToken);
        $rtVal = array((int)$textparts[0], $textparts[1], $textparts[2], $textparts[3]);
        //check token timeout
        if (time() > $textparts[4]) {
            unlink($resumptionToken);
            return false;
        }
    }
    return $rtVal;
}

/**
 * Removes header information from id for internal searches
 */
function get_record_id($id)
{
    global $config;
    if (isset($config->repository->delimiter)) {
        $id_arr = explode($config->repository->delimiter, $id);
        return array_pop($id_arr);
    }
}

/**
 * builds the global id for a record using the information in configuration file
 */
function get_global_id($record_id)
{
    global $config;
    return $config->repository->prefix.$config->repository->delimiter
    .$config->repository->identifier.$config->repository->delimiter
    .$record_id;
}
