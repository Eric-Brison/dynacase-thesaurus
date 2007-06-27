<?php
/**
 * States menu in edit mode
 *
 * @author Anakeen 2007
 * @version $Id: popupeditstate.php,v 1.1 2007/06/27 10:04:29 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage 
 */
 /**
 */


include_once("FDL/popupdocdetail.php");
function popupeditstate(&$action) {
  $docid = GetHttpVars("id");
  if ($docid == "") $action->exitError(_("No identificator"));
  $popup=array();

  $dbaccess = $action->GetParam("FREEDOM_DB");
  $doc = new_Doc($dbaccess, $docid);

  addStatesPopup($popup,$doc);

  foreach ($popup as $k=>$v) { 
    $popup[$k]["submenu"]="";
    $popup[$k]["jsfunction"]="document.getElementById('seltrans').value='$k';askForTransition(event)";
  }

  popupdoc($action,$popup);
  
}