<?php
/**
 * display users and groups list
 *
 * @author Anakeen 2000 
 * @version $Id: fusers_list.php,v 1.11 2008/02/13 15:33:56 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage FUSERS
 */
 /**
 */

include_once("FDL/Lib.Dir.php");
function fusers_list(&$action) {

  $action->parent->AddJsRef($action->GetParam("CORE_PUBURL")."/FDL/Layout/common.js");
  $action->parent->AddJsRef($action->GetParam("CORE_JSURL")."/subwindow.js");

  $action->parent->AddJsRef($action->GetParam("CORE_JSURL")."/geometry.js");
  $action->parent->AddJsRef($action->GetParam("CORE_JSURL")."/AnchorPosition.js");

  $dbaccess = $action->GetParam("FREEDOM_DB");
 

 
  // create group tree

  $action->parent->AddJsRef($action->GetParam("CORE_PUBURL")."/FDL/Layout/mktree.js");

  $action->lay->set("isMaster", $action->parent->Haspermission("FUSERS_MASTER"));



  $user = new User();
  $ugroup=$user->GetGroupsId();
    
  $q2= new queryDb("","User");
  $groups=$q2->Query(0,0,"TABLE","select users.*, groups.idgroup, domain.name as domain from users, groups, domain where users.id = groups.iduser and users.iddomain=domain.iddomain and users.isgroup='Y'");

  $q2= new queryDb("","User");
  $mgroups=$q2->Query(0,0,"TABLE","select users.*, domain.name as domain from users,domain where users.iddomain=domain.iddomain and isgroup='Y' and id not in (select iduser from groups)");
  
  if ($groups) {
    foreach ($groups as $k=>$v) {
      $groupuniq[$v["id"]]=$v;
      $groupuniq[$v["id"]]["checkbox"]="";
      if (in_array($v["id"],$ugroup)) 	 $groupuniq[$v["id"]]["checkbox"]="checked";
    }
  }
  if (!$groups) $groups=array();
  if ($mgroups) {
    $doc=createTmpDoc($dbaccess,1);
    uasort($mgroups,"cmpgroup");
    foreach ($mgroups as $k=>$v) {
      $cgroup=fusers_getChildsGroup($v["id"],$groups);
      $tgroup[$k]=$v;
      $tgroup[$k]["SUBUL"]=$cgroup;	
      $fid=$v["fid"];
      if ($fid) {
	$tdoc=getTDoc($dbaccess,$fid);
	$icon=$doc->getIcon($tdoc["icon"]);
	$tgroup[$k]["icon"]=$icon;
      } else {
	$tgroup[$k]["icon"]="Images/igroup.gif";	  
      }
      $groupuniq[$v["id"]]=$v;
      $groupuniq[$v["id"]]["checkbox"]="";
      if (in_array($v["id"],$ugroup)) $groupuniq[$v["id"]]["checkbox"]="checked";
    }
  }
  $action->lay->setBlockData("LI",$tgroup);
  $action->lay->setBlockData("SELECTGROUP",$groupuniq);

  $action->lay->set("expand", (count($groups) < 30));


  // add button to change categories
  $tcf=array();
  foreach (array("IUSER","IGROUP") as $fid) {

  $fdoc=new_doc($dbaccess,$fid);
  
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
     
  }
  $action->lay->setBlockData("CATG",$tcf);
  $filter[]="grp_isrefreshed = '0'";
  $tdoc = getChildDoc($dbaccess, 0,0,"ALL", $filter,1,"TABLE", "IGROUP");
  $ngr=count($tdoc);
  if ($ngr>0) $action->lay->set("textgroup",sprintf(_("<b>%d group(s) to refresh</b>"),$ngr));
  else $action->lay->set("textgroup",sprintf(_("No need to refresh group")));
}

/**
 * internal function use for choosegroup
 * use to compute displayed group tree
 */
function fusers_getChildsGroup($id,$groups) {
  static $dbaccess;
  static $doc;
  if (!$dbaccess) $dbaccess=getParam("FREEDOM_DB");
  if (!$doc) $doc=createDoc($dbaccess,1);
  $tlay=array();
  foreach ($groups as $k=>$v) {
    if ($v["idgroup"]==$id) {
      $tlay[$k]=$v;
      $tlay[$k]["SUBUL"]=fusers_getChildsGroup($v["id"],$groups);
      $fid=$v["fid"];
      if ($fid) {
	$tdoc=getTDoc($dbaccess,$fid);
	$icon=$doc->getIcon($tdoc["icon"]);
	$tlay[$k]["icon"]=$icon;
      } else {
	$tlay[$k]["icon"]="Images/igroup.gif";	  
      }
    }
  }
  
  if (count($tlay)==0) return "";
  global $action;
  $lay = new Layout("FUSERS/Layout/fusers_ligroup.xml",$action);
  
  uasort($tlay,"cmpgroup");
  $lay->setBlockData("LI",$tlay);
  return $lay->gen();
}
function cmpgroup($a,$b) {
	return strcasecmp($a['lastname'],$b['lastname']);
}
?>