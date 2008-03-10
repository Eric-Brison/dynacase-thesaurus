<?php
/**
 * View workflow graph
 *
 * @author Anakeen 2000 
 * @version $Id: workflow_graph.php,v 1.1 2008/03/10 17:51:05 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage 
 */
 /**
 */


include_once("FDL/Class.WDoc.php");
// -----------------------------------
/**
 * Edition to affect document
 * @param Action &$action current action
 * @global id Http var : document id to affect
 * @global viewdoc Http var : with preview of affect document [Y|N]
 */
function workflow_graph(&$action) {
  $docid = GetHttpVars("id"); 
  $viewdoc = (GetHttpVars("viewdoc","N")=="Y"); 
  $type = GetHttpVars("type","simple"); // type of graph
  $dbaccess = $action->GetParam("FREEDOM_DB");

  $doc=new_doc($dbaccess,$docid);
  $action->lay->set("id",$doc->id);
  $action->lay->set("TITLE",$doc->title);
  
}