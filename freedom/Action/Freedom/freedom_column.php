<?php
/**
 * View folder containt in column mode
 *
 * @author Anakeen 2000 
 * @version $Id: freedom_column.php,v 1.6 2005/08/18 09:16:09 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage GED
 */
 /**
 */




include_once('FREEDOM/freedom_view.php');



// -----------------------------------
// -----------------------------------
function freedom_column(&$action) {
// -----------------------------------
  // Set the globals elements
  $memo=(getHttpVars("memo","N")=="Y");
  

  if ($memo) $action->parent->param->Set("FREEDOM_VIEW","column",PARAM_USER.$action->user->id,$action->parent->id);
  viewfolder($action, false,true,1);
  


}
?>
