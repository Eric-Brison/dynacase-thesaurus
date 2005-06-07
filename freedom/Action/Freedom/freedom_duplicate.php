<?php
/**
 * Duplicate a document
 *
 * @author Anakeen 2000 
 * @version $Id: freedom_duplicate.php,v 1.12 2005/06/07 16:06:24 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage GED
 */
 /**
 */




include_once("FDL/duplicate.php");

include_once("FDL/Class.Dir.php");


// -----------------------------------
function freedom_duplicate(&$action) {
  // -----------------------------------

    // Get all the params      
  $dirid=GetHttpVars("dirid",10); // where to duplicate
  $docid=GetHttpVars("id",0);       // doc to duplicate
  $folio=GetHttpVars("folio","N")=="Y"; // return in folio
  
  duplicate($action, $dirid, $docid);

  
  if ($folio) redirect($action,"FREEDOM","FOLIOLIST&dirid=".$dirid);
  else  redirect($action,"FREEDOM","FREEDOM_VIEW&dirid=".$dirid);


}


?>
