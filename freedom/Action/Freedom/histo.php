<?php
/**
 * Historique view
 *
 * @author Anakeen 2000 
 * @version $Id: histo.php,v 1.7 2005/06/28 08:37:46 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage GED
 */
 /**
 */




include_once("FDL/Class.Doc.php");
function histo(&$action) 
{
  $dbaccess = $action->GetParam("FREEDOM_DB");
  $docid = GetHttpVars("id",0);

  
  $doc= new_Doc($dbaccess,$docid);
  $action->lay->Set("title",$doc->title);
  
}

?>
