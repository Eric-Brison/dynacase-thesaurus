<?php
/**
 * Display edition interface
 *
 * @author Anakeen 2000 
 * @version $Id: generic_edit.php,v 1.70 2008/07/10 14:51:23 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage 
 */
 /**
 */




include_once("FDL/Class.Doc.php");

include_once("FDL/family_help.php");
include_once("Class.QueryDb.php");
include_once("GENERIC/generic_util.php"); 

/**
 * Edit a document
 * @param Action &$action current action
 * @global id Http var : document identificator to see
 * @global zone Http var : if set, special edit with special zone 
 * @global rzone Http var : if set, to return view with special zone 
 * @global rtarget Http var : if set, to return result in another window (the window will be closed)
 * @global vid Http var : if set, edit represention describe in view control (can be use only if doc has controlled view)
 * @global mskid Http var : is set special mask applied for edition
 */
function generic_edit(&$action) {
  // -----------------------------------

  // Get All Parameters
  $docid = trim(GetHttpVars("id",0));        // document to edit
  $classid = GetHttpVars("classid",getDefFam($action)); // use when new doc or change class
  $dirid = GetHttpVars("dirid",0); // directory to place doc if new doc
  $usefor = GetHttpVars("usefor"); // default values for a document
  $zonebodycard = GetHttpVars("zone"); // define view action
  $rzone = GetHttpVars("rzone"); // special zone when finish edition
  $rvid = GetHttpVars("rvid"); // special zone when finish edition
  $rtarget = GetHttpVars("rtarget","_self"); // special zone when finish edition return target

  if ($docid==0) setHttpVar("classid",$classid);
  $vid = GetHttpVars("vid"); // special controlled view
  $mskid = GetHttpVars("mskid"); // special mask

  $action->lay->Set("vid", $vid);
  $action->lay->Set("ezone", $zonebodycard); // use for return in case of constraint
  $action->lay->Set("rzone", $rzone);
  $action->lay->Set("rvid", $rvid);
  $action->lay->Set("rtarget", $rtarget);
  $action->lay->Set("SELFTARGET",($rtarget=="_self"));
  // Set the globals elements
  $dbaccess = $action->GetParam("FREEDOM_DB");
  $action->parent->AddJsRef($action->GetParam("CORE_PUBURL")."/FDL/Layout/popupdoc.js");
  $action->parent->AddJsRef($action->GetParam("CORE_PUBURL")."/FDL/Layout/popupdocmenu.js");
  $action->parent->AddJsRef($action->GetParam("CORE_JSURL")."/resizeimg.js");

  $action->parent->AddCssRef("FDL:POPUP.CSS",true);


  if (($docid === 0) || ($docid === "") || ($docid === "0") )  {   
    if ($classid == "") $action->exitError(sprintf(_("Creation aborded : no family specified")));
    if (! is_numeric($classid))  $classid = getFamIdFromName($dbaccess,$classid);      
    if ($classid == "") $action->exitError(sprintf(_("Creation aborded : unknow family %s"), GetHttpVars("classid",getDefFam($action))));
    if ($classid > 0) {
      $cdoc= new_Doc($dbaccess,$classid);
      if ($cdoc->control('create') != "") $action->exitError(sprintf(_("no privilege to create this kind (%s) of document"),$cdoc->title));
      if ($cdoc->control('icreate') != "") $action->exitError(sprintf(_("no privilege to create interactivaly this kind (%s) of document"),$cdoc->title));
      $action->lay->Set("TITLE", sprintf(_("creation %s"),$cdoc->title));
    } else {
      $action->lay->Set("TITLE",_("new card"));
    }
    if ($usefor=="D") $action->lay->Set("TITLE", _("default values"));
    if ($usefor=="Q") $action->lay->Set("TITLE", _("parameters values"));
    
    $action->lay->Set("editaction", $action->text("Create"));
    $doc= createDoc($dbaccess,$classid);
    if (! $doc) $action->exitError(sprintf(_("no privilege to create this kind (%d) of document"),$classid));
    if ($usefor!="") $doc->doctype='T';
  } else {    
    $doc= new_Doc($dbaccess,$docid);
    $docid=$doc->id;
    setHttpVar("id",$doc->id);
    $err = $doc->lock(true); // autolock
    if ($err != "")   $action->ExitError($err);  
    if ($err=="") $action->AddActionDone("LOCKFILE",$doc->id);


    $classid = $doc->fromid;
    if (! $doc->isAlive()) $action->ExitError(_("document not referenced"));
    if (GetHttpVars("viewconstraint")!="Y") {      
      //      $doc->refresh(); // set in editcard
      // update access date
      $doc->adate=$doc->getTimeDate();
      $doc->modify(true,array("adate"),true);
    }

    $action->lay->Set("TITLE", $doc->title);
  }
    
  $action->lay->Set("STITLE",addJsSlashes($action->lay->get("TITLE"))); // for include in JS
  if ($zonebodycard == "") {
    if ($doc->cvid > 0) {
      $cvdoc= new_Doc($dbaccess, $doc->cvid);
      $cvdoc->set($doc);
      if ($vid == "") {
	// search preferred view
	$tv=$cvdoc->getAValues("CV_T_VIEWS");

	// sort
	usort($tv,"cmp_cvorder");

	foreach ($tv as $k=>$v) {
	  if ($v["cv_order"]>0) {
	    if ($v["cv_kview"]=="VEDIT") {
	      $err = $cvdoc->control($v["cv_idview"]); // control special view
	      if ($err == "") {
		$vid=$v["cv_idview"];
		setHttpVar("vid",$vid);
		break;
	      }
	    }
	  }
	}
	
      }
      
      if ($vid != "") {
	// special controlled view
	$err = $cvdoc->control($vid); // control special view
	if ($err != "") $action->exitError($err);
	$tview = $cvdoc->getView($vid);
	$doc->setMask($tview["CV_MSKID"]);
	if ($zonebodycard == "") $zonebodycard=$tview["CV_ZVIEW"];
      }  
    }
  }
  if (($vid == "")&&($mskid != "")) {
    $mdoc=new_Doc($dbaccess,$mskid);
    if ($mdoc->isAlive() && ($mdoc->control('view')==""))  $doc->setMask($mdoc->id);
  }

  if ($zonebodycard == "") {
    if ((! $docid) && $doc->defaultcreate!="") $zonebodycard = $doc->defaultcreate;
    else $zonebodycard = $doc->defaultedit;
  }

  $action->lay->set("emblem",$doc->getEmblem());
  $action->lay->Set("HEAD", (! ereg("[A-Z]+:[^:]+:[T|S|U|V]", $zonebodycard, $reg)));
  $action->lay->Set("FOOT", (! ereg("[A-Z]+:[^:]+:[S|U]", $zonebodycard, $reg)));
  $action->lay->Set("NOFORM", (ereg("[A-Z]+:[^:]+:U", $zonebodycard, $reg)));
  $action->lay->Set("NOSAVE", (ereg("[A-Z]+:[^:]+:V", $zonebodycard, $reg)));
  if (getHttpVars("forcehead")=="yes") $action->lay->Set("HEAD",true); // for freedom_edit
  $action->lay->Set("iconsrc", $doc->geticon());  $action->lay->Set("viewstate", "none");
  $action->lay->Set("dhelp", "none");
  if (getFamilyHelpFile($action,$doc->fromid) ) {      
      $action->lay->Set("dhelp", "");
      $action->lay->Set("helpid", $doc->fromid);
    }
  $action->lay->Set("state", "");

  $state=$doc->getState();
  $action->lay->Set("statecolor",$doc->getStateColor("transparent"));  
  $action->lay->Set("wid",false);
  if ($doc->fromid > 0) {
    $fdoc= $doc->getFamDoc();
    $action->lay->Set("wid",($fdoc->schar == 'R'));
    $action->lay->Set("FTITLE", $fdoc->title);
  } else {
    $action->lay->Set("FTITLE", _("no family"));
  }
  if ($state) { // see only if it is a transitionnal doc
    if (($doc->locked == -1)||($doc->lmodify != 'Y'))    $action->lay->Set("state", $action->text($state));
    else $action->lay->Set("state", sprintf(_("current (<i>%s</i>)"),$action->text($state)));
    $action->lay->Set("viewstate", "inherit");
    $action->lay->Set("wid", ($doc->wid>0)?$doc->wid:$doc->state);
  } 
  $action->lay->Set("version", $doc->version);
  

  

  $action->lay->Set("id", $docid);
  $action->lay->Set("dirid", $dirid);


  $action->lay->set("tablefoot","tableborder");
  $action->lay->set("tablehead","tableborder");
  $action->lay->set("ddivfoot","none");
  if ($action->Read("navigator","")=="NETSCAPE") {
    if (ereg("rv:([0-9.]+).*",$_SERVER['HTTP_USER_AGENT'],$reg)) {
      if (floatval($reg[1] >= 1.6)) {
	$action->lay->set("ddivfoot","");
	$action->lay->set("tablefoot","tablefoot");
	$action->lay->set("tablehead","tablehead");	
      }
    }
    
  } 


  $action->lay->set("VALTERN",($action->GetParam("FDL_VIEWALTERN","yes")=="yes"));
  // information propagation
  $action->lay->Set("classid", $classid);
  $action->lay->Set("dirid", $dirid);
  $action->lay->Set("id", $docid);
    

}

function cmp_cvorder($a, $b)
{
   if ($a["cv_order"] == $b["cv_order"]) {
       return 0;
   }
   return ($a["cv_order"] < $b["cv_order"]) ? -1 : 1;
}
?>
