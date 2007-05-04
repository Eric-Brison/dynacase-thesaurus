<?php
/**
 * List document of a category
 *
 * @author Anakeen 2000 
 * @version $Id: generic_tab.php,v 1.25 2007/05/04 16:11:40 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage 
 */
 /**
 */




include_once("FDL/Class.DocSearch.php");
include_once("FDL/Lib.Dir.php");

include_once("FDL/freedom_util.php");  
include_once("GENERIC/generic_util.php");
include_once("GENERIC/generic_list.php");





// -----------------------------------
function generic_tab(&$action) {

  

  // Get all the params      
  $keyword=GetHttpVars("keyword"); // keyword to search
  $dirid=GetHttpVars("catg",0); // folder where search
  $tab=GetHttpVars("tab", 1); // tab index
  

  $dbaccess = $action->GetParam("FREEDOM_DB");
  
  $famid = getDefFam($action);
  $emptyfld=false;
  if ($famid == "0") $action->exitError(_("cookies seem to be blocked"));

  $fdoc = new DocFam($dbaccess,$famid);
  if ($dirid == 0) {
    $dirid=getDefU($action,"GENE_PREFSEARCH");
  }
  if ($dirid == 0) {
    if ($fdoc->cfldid > 0) {
      $dirid=$fdoc->cfldid;
    } else {
      $dirid=$fdoc->dfldid;
    }
  }

  // hightlight the selected part (ABC, DEF, ...)
  $tabletter=array("", "ABC","DEF", "GHI","JKL","MNO","PQRS","TUV","WXYZ");



  $dir = new_Doc($dbaccess, $dirid);

  // control open
  if ($dir->defDoctype=='S') $aclctrl="execute";
  else $aclctrl="open";
  if (($err=$dir->Control($aclctrl)) != "") $action->exitError($err);

  if (($dir->defDoctype=='S')&&($tab==0)) {
    // parmeters for redirect in case of parametrizable search
    setHttpVar("dirid",$dir->initid );
    setHttpVar("sapp",$action->getParam("APPNAME","GENERIC"));
    setHttpVar("saction",urlencode("GENERIC_LIST&famid=$famid&onglet=Y"));
    setHttpVar("sid","dirid");
    setHttpVar("id",$dir->initid);
    
  } else {
    $sdoc = createDoc($dbaccess,5,false); // new DocSearch


    $sdoc->doctype = 'T';// it is a temporary document (will be delete after)


    if ($dir->id == $fdoc->dfldid)   {
      $sdoc->title = sprintf(_("%s all "),$tabletter[$tab] );
      $sdirid=0; // search in all DB
    }  else {
      $sdoc->title = sprintf("%s %s ",$tabletter[$tab],$dir->title );
      $sdirid=$dir->id;
    }



 

    $sdoc->Add();


    //    $sqlfilter[]= "locked != -1";

    $wkdoc=createDoc($dbaccess,$famid,false);
    $sqlfilter[]= "doctype='".$wkdoc->defDoctype."'";
    //    $sqlfilter[] = "usefor != 'D'";

    if ($tabletter[$tab]!="") $sqlfilter[]="title ~* '^[".$tabletter[$tab]."].*'";


    $only=(getInherit($action,$famid)=="N");
    if (($famid!="") && (! is_numeric($famid))) $famid=getFamIdFromName($dbaccess,$famid);
    

    $query = getSqlSearchDoc($dbaccess,$sdirid,($only)?-(abs($famid)):abs($famid),$sqlfilter);


    $sdoc->AddQuery($query);
  

    setHttpVar("tab", $tab);
    if (! $emptyfld) setHttpVar("dirid",$sdoc->id );
    setHttpVar("catg",$dirid );

  }


  generic_list($action);
  //  redirect($action,GetHttpVars("app"),"GENERIC_LIST&tab=$tab&dirid=".$sdoc->id."&catg=$dirid");
  
  
}


?>