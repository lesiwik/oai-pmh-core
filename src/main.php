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
  * \brief Main library file
  *
  * handles requests from the client implementations
  *
  * To initialize a client include this file and call Fccn\Oaipmh\execute_request()
  * with the path to the client configuration file.
  * For more information check https://github.com/fccn/oai-pmh-demo-client
  */

namespace Fccn\Oaipmh;

/**
 * global configuration
 */
$config = '';
/**
 * Path to the core
 */
$corepath = __DIR__;
/**
* An array for collecting errors which can be reported later. It will be checked before a new action is taken.
*/
$errors = array();

/**
 * Request arguments
 */
$args = array();

if(isset($_GET['verb']) && $_GET['verb']!='Identify' && $_GET[verb]!='ListMetadataFormats' && $_GET[verb]!='ListSets'){
if(!isset($_GET['metadataPrefix'])){
    $_GET['metadataPrefix']='oai_dc';
}
}

/**
 * Executes a OAI-PMH request
 */
function execute_request($path_to_config = '')
{

    global $config, $corepath, $args, $errors;

    if ($path_to_config == '' || !file_exists($path_to_config)) {
        exit("Configuration file not set");
    }

    //load configuration file
    require_once($corepath.'/libs/phprop.php');
    $config = Phprop::parse($path_to_config);

    //load utilities
    require_once($corepath . '/libs/oaidp-util.php');

    //load xml creation facilities
    require_once($corepath . '/libs/xml_creater.php');

    /**
    * Supported attributes associate to verbs.
    */
    $attribs = array('from', 'identifier', 'metadataPrefix', 'set', 'resumptionToken', 'until');
    if (in_array($_SERVER['REQUEST_METHOD'], array('GET','POST'))) {
//        $args = $_REQUEST;
        $args = $_GET;
    } else {
        $errors[] = oai_error('badRequestMethod', $_SERVER['REQUEST_METHOD']);
        oai_exit();
    }


    //debug_var_dump("config_PDO", $config->pdo);

    // Always using htmlentities() function to encodes the HTML entities submitted by others.
    // No one can be trusted.
    foreach ($args as $key => $val) {
        $checking = htmlspecialchars(stripslashes($val));
        if (!is_valid_attrb($checking)) {
            $errors[] = oai_error('badArgument', $checking);
        } else {
            $args[$key] = $checking;
        }
    }
    if (!empty($errors)) {
        oai_exit();
    }

    foreach ($attribs as $val) {
        unset($$val);
    }

    // // Create a PDO object
    // try {
    // $db = new PDO(str_replace(',',';',$config->pdo->dsn), $config->pdo->user, $config->pdo->pass);
    // } catch (PDOException $e) {
    // exit('Connection failed: ' . $e->getMessage());
    // }

    //-----

    // Default, there is no compression supported
    $compress = false;
    if (isset($config->repository->compression) && is_array($config->repository->compression)) {
        if (in_array('gzip', $config->repository->compression) && ini_get('output_buffering')) {
            $compress = true;
        }
    }

    if (isset($args['verb'])) {
        switch ($args['verb']) {

            case 'Identify':
                debug_message(">>>>>Identify");
                // we never use compression in Identify
                $compress = false;
                if (count($args)>1) {
                    foreach ($args as $key => $val) {
                        if (strcmp($key, "verb")!=0) {
                            $errors[] = oai_error('badArgument', $key, $val);
                        }
                    }
                }
                if (empty($errors)) {
                    include $corepath . '/verbs/identify.php';
                }
                break;

            case 'ListMetadataFormats':
                debug_message(">>>>>ListMDFormats");
                $checkList = array("ops"=>array("identifier"));
                checkArgs($args, $checkList);
                if (empty($errors)) {
                    include $corepath . '/verbs/listmetadataformats.php';
                }
                break;

            case 'ListSets':
                debug_message(">>>>>ListSets");
                if (isset($args['resumptionToken']) && count($args) > 2) {
                    $errors[] = oai_error('exclusiveArgument');
                }
                $checkList = array("ops"=>array("resumptionToken"));
                checkArgs($args, $checkList);
                if (empty($errors)) {
                    include $corepath . '/verbs/listsets.php';
                }
                break;

            case 'GetRecord':
                debug_message(">>>>>GetRecord");
                $checkList = array("required"=>array("metadataPrefix","identifier"));
                checkArgs($args, $checkList);
                if (empty($errors)) {
                    include $corepath . '/verbs/getrecord.php';
                }
                break;

            case 'ListIdentifiers':
            case 'ListRecords':
                debug_message(">>>>>ListRecords");
                if (isset($args['resumptionToken'])) {
                    if (count($args) > 2) {
                        $errors[] = oai_error('exclusiveArgument');
                    }
                    $checkList = array("ops"=>array("resumptionToken"));
                } else {
                    $checkList = array("required"=>array("metadataPrefix"),"ops"=>array("from","until","set"));
                }
                checkArgs($args, $checkList);
                if (empty($errors)) {
                    include $corepath . '/verbs/listrecords.php';
                }
                break;

            default:
                debug_message("BadVerb:". $args['verb']);
                // we never use compression with errors
                $compress = false;
                $errors[] = oai_error('badVerb', $args['verb']);
        } /*switch */
    } else {
        debug_message("no verb in request");
        $errors[] = oai_error('noVerb');
    }

    if (!empty($errors)) {
        oai_exit();
    }

    if ($compress) {
        ob_start('ob_gzhandler');
    }

    header(CONTENT_TYPE);

    if (isset($outputObj)) {
        //debug_var_dump('outputObj', $outputObj);
        $outputObj->display();
    } else {
        error_log("No output defined");
        http_err_exit();
    }

    if ($compress) {
        ob_end_flush();
    }
}
