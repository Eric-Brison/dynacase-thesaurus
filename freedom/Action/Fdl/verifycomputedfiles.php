<?php
/**
 * Specific menu for family
 *
 * @author Anakeen 2000 
 * @version $Id: verifycomputedfiles.php,v 1.1 2007/11/26 15:06:41 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage 
 */
 /**
 */


include_once("FDL/Class.Doc.php");
/**
 * Edit an attribute inline
 * @param Action &$action current action
 * @global docid Http var : document identificator to see
 * @global attrid Http var : the id of attribute to edit
 */
function verifycomputedfiles(&$action) {
  $docid = GetHttpVars("id");
  $attrid = GetHttpVars("attrid");
  $dbaccess = $action->GetParam("FREEDOM_DB");


  header('Content-type: text/xml; charset=utf-8'); 
  $action->lay->setEncoding("utf-8");
 
  $mb=microtime();

  $action->lay->set("CODE","OK");
  $action->lay->set("warning","");
  $action->lay->set("modjsft",$modjsft);
 

  
  $doc = new_Doc($dbaccess, $docid);
  if (! $doc->isAffected()) $err=sprintf(_("cannot see unknow reference %s"),$docid);
  if ($err == "") {
    $action->lay->set("docid",$doc->id);
    $files=$doc->GetFilesProperties();
  }

  if ($err != "")   $action->lay->set("CODE","KO");
  $action->lay->set("warning",utf8_encode($err));
  $action->lay->set("delay",microtime_diff(microtime(),$mb));


  $action->lay->setBlockData("FILES",$files);
  $action->lay->set("count",count($files));
  $action->lay->set("docid",$doc->id);

}


?>