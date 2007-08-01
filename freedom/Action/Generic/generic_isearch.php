<?php
/**
 * Searches of referenced documents
 *
 * @author Anakeen 2000 
 * @version $Id: generic_isearch.php,v 1.12 2007/08/01 14:04:29 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage 
 */
 /**
 */




include_once("FDL/Class.DocSearch.php");
include_once("FDL/freedom_util.php");  
include_once("GENERIC/generic_util.php");


include_once("FDL/Class.DocRel.php");



// -----------------------------------
function generic_isearch(&$action) {
  // -----------------------------------
   

  // Get all the params      
  $docid=GetHttpVars("id"); // id doc to search
  $famid=GetHttpVars("famid",0); // restriction of search
  $viewone=GetHttpVars("viewone"); // 
  $generic=(GetHttpVars("generic")=="Y"); // 
  
  $dbaccess = $action->GetParam("FREEDOM_DB");

  if (($famid !== 0) && (! is_numeric($famid))) {
    $famid=getFamIdFromName($dbaccess,$famid);  
  }
  if ($docid == "") $action->exitError(_("related search aborted : no parameter found"));


  $doc = new_Doc($dbaccess, $docid);

  $sdoc = createTmpDoc($dbaccess,38); //new Special Seraches
  $sdoc->setValue("ba_title", sprintf(_("related documents of %s"),$doc->title ));
  $sdoc->setValue("se_phpfile","fdlsearches.php");
  $sdoc->setValue("se_phpfunc","relateddoc");
  $sdoc->setValue("se_phparg","$docid,$famid");


  $sdoc->Add();  
  
  setHttpVar("dirid",$sdoc->id);
  if ($generic) {
    include_once("GENERIC/generic_list.php");  
    generic_list($action);
    //    redirect($action,"GENERIC","GENERIC_LIST&dirid=".$sdoc->id."&famid=$famid&catg=0");
  } else {
    include_once("FREEDOM/freedom_view.php");  
    $action->parent->name="FREEDOM";
    freedom_view($action, false);
    //    redirect($action,"FREEDOM","FREEDOM_VIEW&viewone=$viewone&dirid=".$sdoc->id);
  }
  // 
  
  
}


?>