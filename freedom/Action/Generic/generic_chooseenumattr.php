<?php
/**
 * Display list of enumrate attribute for a family
 *
 * @author Anakeen 2006
 * @version $Id: generic_chooseenumattr.php,v 1.1 2006/02/03 17:03:41 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage 
 */
 /**
 */




include_once("FDL/Class.Doc.php");
include_once("GENERIC/generic_util.php"); 

/**
 * Display list of enumrate attribute for a family
 * @param Action &$action current action
 * @global famid Http var : family document identificator where find enum attributes
 */
function generic_chooseenumattr(&$action) {
  $famid=GetHttpVars("famid",getDefFam($action)); 
  $action->lay->set("famid",$famid);
  $dbaccess = $action->GetParam("FREEDOM_DB");
  $action->parent->AddJsRef($action->GetParam("CORE_JSURL")."/subwindow.js");

  $tcf=array();

  $fdoc=new_doc($dbaccess,$famid);
  
  $lattr = $fdoc->getNormalAttributes();
  foreach ($lattr as $k=>$a) {
     if ((($a->type == "enum") || ($a->type == "enumlist")) &&
	 (($a->phpfile == "") || ($a->phpfile == "-"))&&
	 ($a->getOption("system")!="yes")) {

       $tcf[]=array("label"=>$a->labelText,
		    "famid"=>$a->docid,
		    "ftitle"=>$fdoc->getTitle($a->docid),
		    "kindid"=>$a->id);
     }
  }
     
  
  $action->lay->setBlockData("CATG",$tcf);
  $action->lay->set("title",sprintf("modify categories for family : %s",$fdoc->title));
  $action->lay->set("icon",$fdoc->getIcon());
}
?>