<?php
/**
 * Display doucment explorer
 *
 * @author Anakeen 2006
 * @version $Id: ws_renamefile.php,v 1.1 2006/11/13 16:01:26 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage 
 */
 /**
 */



include_once("FDL/Lib.Dir.php");
include_once("WORKSPACE/Lib.WsFtCommon.php");


/**
 * Rename file name of document
 * @param Action &$action current action
 * @global id Http var : document id 
 * @global newname Http var : new name for the file
 */
function ws_renamefile(&$action) {
  header('Content-type: text/xml; charset=utf-8'); 
  $action->lay->setEncoding("utf-8");

  $mb=microtime();
  $docid = GetHttpVars("id");
  $newname = GetHttpVars("newname");
  $dbaccess = $action->GetParam("FREEDOM_DB");


  $action->lay->set("warning","");
  if ($err) $action->lay->set("warning",$err);

  $doc=new_doc($dbaccess,$docid);

  $f=$doc->getValue("sfi_file");
  
  if (ereg ("(.*)\|(.*)", $f, $reg)) {
    $vf = newFreeVaultFile($dbaccess);
    $vid=$reg[2];
    $vf->Rename($vid,utf8_decode($newname));
    $doc->addComment(sprintf(_("Rename file as %s"),utf8_decode($newname)));
    $doc->postModify();
    $err=$doc->modify();
  }

  if ($err != "") {    
    $action->lay->set("warning",utf8_encode($err));
    $action->lay->set("CODE","KO");
  } else {  
    $action->lay->set("CODE","OK");
  }
  $action->lay->set("count",1);
  $action->lay->set("delay",microtime_diff(microtime(),$mb));					

}