<?php
/**
 * Generated Header (not documented yet)
 *
 * @author Anakeen 2000 
 * @version $Id: adddirquery.php,v 1.5 2004/03/25 11:10:10 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage GED
 */
 /**
 */


include_once("FDL/Class.Doc.php");
include_once("FDL/Class.Directory.php");
include_once("FDL/freedom_util.php");  



// -----------------------------------
function adddirquery(&$action) {
  // -----------------------------------



  // Get all the params      
  $docid=GetHttpVars("docid");


  

  
  
  redirect($action,"FDL","FDL_CARD&id=$docid");
}




?>
