<?php
/**
 * generate interface for the rdition of document
 *
 * @author Anakeen 2003
 * @version $Id: editcard.php,v 1.65 2008/02/08 16:34:25 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage 
 */
 /**
 */


include_once("FDL/Class.Doc.php");
include_once("FDL/Class.DocAttr.php");
include_once("FDL/editutil.php");


function editcard(&$action) {
  
  $docid = GetHttpVars("id",0);        // document to edit
  $classid = GetHttpVars("classid",0); // use when new doc or change class
  $zonebodycard = GetHttpVars("zone"); // define view action
  $usefor = GetHttpVars("usefor"); // default values for a document
  $vid = GetHttpVars("vid"); // special controlled view
  $mskid = GetHttpVars("mskid"); // special mask

  $dbaccess = $action->GetParam("FREEDOM_DB");
  editmode($action);
  if (! is_numeric($classid))  $classid = getFamIdFromName($dbaccess,$classid);
  

  if (($usefor=="D") && ($zonebodycard == "")) $zonebodycard="FDL:EDITBODYCARD";// always default view for default document
 
    
  if ($docid == 0) { // new document
    if ($classid > 0) {
      $doc= createDoc($dbaccess,$classid,true,($usefor!="D"));
    }
  } else { // modify document
    
    $doc= new_Doc($dbaccess,$docid);
    $docid=$doc->id;
    if ($doc->isConfidential()) {      
      redirect($action,"FDL",
	       "FDL_CONFIDENTIAL&&id=".$doc->id);
    }
  }
  
  $usefor = GetHttpVars("usefor"); // default values for a document
  $vid = GetHttpVars("vid"); // special controlled view
  $mskid = GetHttpVars("mskid"); // special mask

  $dbaccess = $action->GetParam("FREEDOM_DB");
  editmode($action);
  if (! is_numeric($classid))  $classid = getFamIdFromName($dbaccess,$classid);
  

  if (($usefor=="D") && ($zonebodycard == "")) $zonebodycard="FDL:EDITBODYCARD";// always default view for default document
 
    
  if ($docid == 0) { // new document
    if ($classid > 0) {
      $doc= createDoc($dbaccess,$classid,true,($usefor!="D"));
      if (! $doc) $action->exitError(sprintf(_("no privilege to create this kind (%d) of document"),$classid));
      $fdoc = new DocFam($dbaccess, $classid);
      if ($fdoc->control('icreate') != "") $action->exitError(sprintf(_("no privilege to create interactivaly this kind (%s) of document"),$fdoc->title));
    }
  } else { // modify document
    
    $doc= new_Doc($dbaccess,$docid);
    $docid=$doc->id;
    if ($doc->isConfidential()) {      
      redirect($action,"FDL",
	       "FDL_CONFIDENTIAL&&id=".$doc->id);
    }
    $fdoc = new DocFam($dbaccess, $classid);
  }
  

  if (($usefor == "D")||($usefor == "Q")) {
    // special edit
    $zonebodycard="FDL:EDITBODYCARD";
    switch ($usefor) {
    case "D":
      $doc->usefor='D';
      $doc->setDefaultValues($fdoc->getDefValues(),false); 
      break;
    case "Q":
      $doc->usefor='Q';
      $doc->setDefaultValues($fdoc->getParams(),false); 
      break;
    }
  
  } else {
    // normal edit
    if ($doc->cvid > 0) {
      // special controlled view
      $cvdoc= new_Doc($dbaccess, $doc->cvid);
      $cvdoc->set($doc);
      if (($docid == 0) ) {
	// search default create view     
	$vidcreate = $cvdoc->getValue("CV_IDCVIEW");
	if ($vidcreate) $vid=$vidcreate; // use it first if exist
      }

      if ($vid == "") {
	// search preferred view
	$tv=$cvdoc->getAValues("CV_T_VIEWS");

	// sort
	usort($tv,"cmp_cvorder2");

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
    }
  
    if (($vid != "") && ($doc->cvid > 0)) {
      $err = $cvdoc->control($vid); // control special view
      if ($err != "") $action->exitError($err);
      $tview = $cvdoc->getView($vid);
      $doc->setMask($tview["CV_MSKID"]);
      if ($zonebodycard == "") $zonebodycard=$tview["CV_ZVIEW"];
    }  

    if (($vid == "")&&($mskid != "")) {
      $mdoc=new_Doc($dbaccess,$mskid);
      if ($mdoc->isAlive() && ($mdoc->control('view')==""))  $doc->setMask($mdoc->id);
    }

    if (GetHttpVars("viewconstraint")=="Y") { // from modcard function if constraint error
    
      include_once("FDL/modcard.php");  
      setPostVars($doc); // HTTP VARS comes from previous edition

    
    }
  

    if ($zonebodycard == "") {
      if ((! $docid) && $doc->defaultcreate!="") $zonebodycard = $doc->defaultcreate;
      else $zonebodycard = $doc->defaultedit;
    }
  }
  if ($zonebodycard == "") $zonebodycard="FDL:EDITBODYCARD";
  $action->lay->Set("classid", $classid);
  $action->lay->Set("usefor", $usefor);

  if ($usefor == "D") {
    $doc->SetWriteVisibility();
  // contruct js functions
    $jsfile=$action->GetLayoutFile("editcard.js");
    $jslay = new Layout($jsfile,$action);
    $jslay->Set("attrnid",'[]');
    $jslay->Set("attrntitle",'[]');
    $jslay->SetBlockData("RATTR",$tjsa);
    $action->parent->AddJsCode($jslay->gen());
  } else  setNeededAttributes($action,$doc);
  $action->lay->Set("ZONEBODYCARD", $doc->viewDoc($zonebodycard));
  $action->lay->Set("NOFORM", (ereg("[A-Z]+:[^:]+:U", $zonebodycard, $reg)));
  // compute modify condition js

}

function setNeededAttributes(&$action,&$doc) {  
  $attrn = $doc->GetNeededAttributes();
  
  if (count($attrn) == 0) {
    $sattrNid = "[]";
    $sattrNtitle = "[]";
  } else {
    while(list($k,$v) = each($attrn)) {
      $attrNid[]=$v->id;
      $attrNtitle[]=addslashes($v->labelText);
    }
    $sattrNid = "['".implode("','",$attrNid)."']";
    $sattrNtitle = "['".implode("','",$attrNtitle)."']";
  }


  //compute constraint for enable/disable input
  $tjsa=array();
  if ($usefor != "D") {
    if (GetHttpVars("viewconstraint")!="Y") $doc->Refresh();
    else {
      $err=$doc->SpecRefresh();
      $err.=$doc->SpecRefreshGen();      
    }
    
    $ka=0;

    reset($doc->paramRefresh);
  
    while(list($k,$v) = each($doc->paramRefresh)) {

      $tjsa[]=array("jstain" => "['".implode("','", $v["in"])."']",
		    "jstaout" => "['".implode("','", $v["out"])."']",
		    "jska"=> "$ka");
      $ka++;
	
          
    }
  }

  // contruct js functions
  $jsfile=$action->GetLayoutFile("editcard.js");
  $jslay = new Layout($jsfile,$action);
  $jslay->Set("attrnid",$sattrNid);
  $jslay->Set("attrntitle",$sattrNtitle);
  $jslay->SetBlockData("RATTR",$tjsa);
  $action->parent->AddJsCode($jslay->gen());
  
}

function moreone($v) {
  return (strlen($v) > 1);
}

function cmp_cvorder2($a, $b)
{
   if ($a["cv_order"] == $b["cv_order"]) {
       return 0;
   }
   return ($a["cv_order"] < $b["cv_order"]) ? -1 : 1;
}

?>
