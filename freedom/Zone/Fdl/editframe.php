<?php
/**
 * Generate Layout to edit frame (fieldset)
 *
 * @author Anakeen 2000 
 * @version $Id: editframe.php,v 1.23 2005/07/19 09:48:06 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage 
 */
 /**
 */



include_once("FDL/Class.Doc.php");
include_once("FDL/Class.DocAttr.php");

include_once("FDL/freedom_util.php");
include_once("FDL/editutil.php");



// Compute value to be inserted in a specific layout
// -----------------------------------
function editframe(&$action) {
  // -----------------------------------

  // GetAllParameters
  $docid = GetHttpVars("id",0);
  $classid = GetHttpVars("classid");
  $frameid = strtolower(GetHttpVars("frameid"));
  $vid = GetHttpVars("vid"); // special controlled view

  // Set the globals elements


  $dbaccess = $action->GetParam("FREEDOM_DB");

  if ($docid == 0) $doc = createDoc($dbaccess, $classid);
  else $doc = new_Doc($dbaccess, $docid);

  if (($vid != "") && ($doc->cvid > 0)) {
    // special controlled view
    $cvdoc= new_Doc($dbaccess, $doc->cvid);
    $tview = $cvdoc->getView($vid);
      if ($tview)  $doc->setMask($tview["CV_MSKID"]);
  }
  
  $listattr = $doc->GetNormalAttributes();
    
  if (GetHttpVars("viewconstraint")=="Y") { // from modcard function if constraint error    
    include_once("FDL/modcard.php");  
    setPostVars($doc); // HTTP VARS comes from previous edition    
  }

    
  $thval = array();
  $tval = array();
  while (list($k,$v) = each($listattr)) {


    if (($v->fieldSet->id != $frameid) ) continue;
    if ($v->inArray() ) continue;
    if ($v->mvisibility == "I" ) continue;// not editable

    $action->lay->set("flabel",$v->fieldSet->labelText);
    $action->lay->set("frameid",$v->fieldSet->id);

    //------------------------------
    // Set the table value elements
    $value = chop($doc->GetValue($v->id));
    if ($docid == 0) {
      $value=$doc->GetValueMethod($value); // execute method for default values
    }
    if ( ($v->mvisibility == "H") || 
	 ($v->mvisibility == "R") ) {

      $thval[$k]["avalue"]=  getHtmlInput($doc,
					  $v, 
					  $value);

      // special case for hidden values
    } else {	
      $tval[$k]["alabel"]=  $v->labelText;
      if ($v->needed ) $tval[$k]["labelclass"]="FREEDOMLabelNeeded";
      else $tval[$k]["labelclass"]="FREEDOMLabel";
      $tval[$k]["avalue"]=  getHtmlInput($doc,
					 $v, 
					 $value);

      $tval[$k]["winput"]=($v->type=="array")?"1%":"30%";  // width
      $tval[$k]["TESTARRAY"]=($v->type=="array");
         
    }
	
      
      
  }
  $action->lay->setBlockData("FVALUES",$tval);
  $action->lay->setBlockData("FHIDDENS",$thval);
  if (count($tval) > 0) {
    
    $action->lay->setBlockData("FRAME",array(array("bou")));

  }
    
  
}


?>
