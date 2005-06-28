<?php
/**
 * Apply sort by family
 *
 * @author Anakeen 2000 
 * @version $Id: generic_usort.php,v 1.5 2005/06/28 08:37:46 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage 
 */
 /**
 */


include_once("FDL/Class.Doc.php");
include_once("GENERIC/generic_util.php");

// -----------------------------------
function generic_usort(&$action) {
  // -----------------------------------

  
  // get all parameters
  $aorder=GetHttpVars("aorder"); // id for controlled object

  if ($aorder == "-") {
    // invert order
    $uorder = getDefUSort($action);
    if ($uorder[0] == "-") $aorder=substr($uorder,1);
    else $aorder="-".$uorder;
  }

 
   $action->parent->param->Set("GENERIC_USORT",setUsort($action,$aorder),PARAM_USER.$action->user->id,$action->parent->id);


  $famid = getDefFam($action);

   redirect($action,$action->GetParam("APPNAME","GENERIC"),
	    "GENERIC_TAB&tab=0&famid=$famid",
	     $action->GetParam("CORE_STANDURL"));
  
 
}


function setUsort(&$action, $aorder) {
  
  $famid=getDefFam(&$action);
  $dbaccess = $action->GetParam("FREEDOM_DB");


  $fdoc= new_Doc( $dbaccess, $famid);

  $pu = $action->GetParam("GENERIC_USORT");
  $tr=array();
  if ($pu) {
    // disambled parameter
    $tu = explode("|",$pu);
    
    while (list($k,$v) = each($tu)) {
      list($afamid,$uorder,$sqlorder) = explode(":",$v);
      $tr[$afamid]=$uorder.":".$sqlorder;
    }
  }

  $sqlorder=$aorder; 
  if ($aorder[0] == "-") $sqlorder=substr($aorder,1);
  $a = $fdoc->getAttribute($sqlorder);
  if ($a && $a->type == "text") $sqlorder="lower($sqlorder)";
  if ($aorder[0] == "-") $sqlorder.= " desc";

 
  $tr[$famid]=$aorder.":".$sqlorder;

  // rebuild parameter
  $tu=array();
  reset($tr);
  while (list($k,$v) = each($tr)) {
    $tu[]="$k:$v";
  }
  return implode("|", $tu);
  
  
  
}
?>
