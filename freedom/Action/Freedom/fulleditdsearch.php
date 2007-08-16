<?php
/**
 * Full Text Search document
 *
 * @author Anakeen 2007
 * @version $Id: fulleditdsearch.php,v 1.2 2007/08/16 10:12:27 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage GED
 */
 /**
 */

include_once("FDL/Class.Dir.php");  
include_once("FDL/Lib.Dir.php");
include_once("FDL/freedom_util.php");  


/**
 * Interface Fulltext Detailled  Search document 
 * @param Action &$action current action
 * @global keyword Http var : word to search in any values
 * @global famid Http var : restrict to this family identioficator
 * @global start Http var : page number 
 * @global dirid Http var : search identificator
 */
function fulleditdsearch(&$action) {

  $famid=GetHttpVars("famid",0);
  $action->parent->AddJsRef($action->GetParam("CORE_JSURL")."/DHTMLapi.js");
  $action->parent->AddJsRef($action->GetParam("CORE_JSURL")."/resizeimg.js");
  $action->parent->AddJsRef($action->GetParam("CORE_PUBURL")."/FDC/Layout/inserthtml.js");
  $action->parent->AddJsRef($action->GetParam("CORE_PUBURL")."/FREEDOM/Layout/editdsearch.js");
  $action->parent->AddJsRef($action->GetParam("CORE_STANDURL")."app=FDL&action=EDITJS");
  $action->parent->AddJsRef($action->GetParam("CORE_PUBURL")."/FDL/Layout/edittable.js");

  $action->parent->AddJsRef($action->GetParam("CORE_PUBURL")."/FREEDOM/Layout/fulleditdsearch.js");



  $dbaccess = $action->GetParam("FREEDOM_DB");
  if (! is_numeric($famid)) $famid=getFamIdFromName($dbaccess,$famid);
  if ($famid==0) $famid=7; // FILE family
  

  $action->lay->set("searchtitle",_("detailled search"));

  $tclassdoc=GetClassesDoc($dbaccess, $action->user->id,array(1,2),"TABLE");
  foreach ($tclassdoc as $k=>$cdoc) {
      $selectclass[$k]["idcdoc"]=$cdoc["initid"];
      $selectclass[$k]["classname"]=$cdoc["title"];
      $selectclass[$k]["famselect"]=($cdoc["initid"]==$famid)?"selected":"";
  }  
  $action->lay->SetBlockData("SELECTFAM", $selectclass); 
  


  if ($famid>0) {
    $search=createTmpDoc($dbaccess,16);
    
    $search->setValue("se_famid",$famid);
    $search->setValue("se_latest","yes");
    $search->lay=$action->lay;
    $search->editdsearch();

    $fdoc=new_doc($dbaccess,$famid);
    $action->lay->set("famicon",$fdoc->getIcon());
    $action->lay->set("famid",$fdoc->id);
  }

}
?>