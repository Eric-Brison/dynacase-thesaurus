<?php
/**
 * View virtual document
 *
 * @author Anakeen 2000 
 * @version $Id: viewicard.php,v 1.5 2005/03/08 17:53:56 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage GED
 */
 /**
 */


include_once("FDL/freedom_util.php");

// -----------------------------------
function viewicard(&$action) {
  global $action;
  // Get All Parameters
  $xml = GetHttpVars("xml");
  $famid = GetHttpVars("famid");
  $dbaccess = $action->GetParam("FREEDOM_DB");

  $action->lay->Set("TITLE",$idoc->title);
 
  //  print(base64_decode(trim($xml)));

  $idoc=fromxml($dbaccess,$xml,$famid,true);
  $idoc->SetTitle($idoc->title);

  redirect($action,GetHttpVars("redirect_app","FDL"),
	   GetHttpVars("redirect_act","IMPCARD&dochead=no&id=".$idoc->id),
	   $action->GetParam("CORE_STANDURL"));

}
?>