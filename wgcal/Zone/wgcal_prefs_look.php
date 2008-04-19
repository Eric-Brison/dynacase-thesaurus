<?php
/**
 * Generated Header (not documented yet)
 *
 * @author Anakeen 2000 
 * @version $Id: wgcal_prefs_look.php,v 1.23 2008/04/19 09:45:29 marc Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage WGCAL
 */
 /**
 */
include_once('FDL/Lib.Dir.php');
include_once("EXTERNALS/WGCAL_external.php");
include_once("WGCAL/Lib.wTools.php");


function wgcal_prefs_look(&$action) {
  

  $uid = GetHttpVars("uid", $action->user->id);
  $action->lay->set("uid", $uid);

  $zwrvs = array( 30 => _("small"), 40 => _("medium"), 50 => _("large"));
  $opt = array(); $i = 0;
  foreach ($zwrvs as $k => $v) {
    $opt[$i]["optvalue"] = $k;
    $opt[$i]["optdescr"] = $v;
    $opt[$i]["optselect"] = ($k==$action->GetParam("WGCAL_U_HLINEHOURS") ? "selected" : "");
    $i++;
  }
  $action->lay->SetBlockData("HDIVSZ", $opt);

  $evcol = getParam("WGCAL_U_EVENTTEXTCOLOR", "");
  $action->lay->set("textcolor", ($evcol==""?"#000000":$evcol));
  $action->lay->set("CSELECTED", ($evcol!=""?true:false));

  $popuppos = array( "Float" => _("floating, follows the pointer"),
		     "LeftTop" => _("on the left top"), 
		     "LeftBottom" => _("on the left bottom"), 
		     "RightTop" => _("on the right top"), 
		     "RightBottom" => _("on the right bottom") );
  $opt = array(); $i = 0;
  foreach ($popuppos as $k => $v) {
    $opt[$i]["optvalue"] = $k;
    $opt[$i]["optdescr"] = $v;
    $opt[$i]["optselect"] = ($k==$action->GetParam("WGCAL_U_ALTFIXED") ? "selected" : "");
    $i++;
  }
  $action->lay->SetBlockData("ALTPOS", $opt);


  $rinit = getParam("WGCAL_U_RELOADTOOLBAR", 3600);
  $refresh = array( 
		   array( "val" => "0",  "sel" => "", "text" => _("never")),
		   array( "val" => "900" ,  "sel" => "", "text" => _("1/4 heure")),
		   array( "val" => "1800",  "sel" => "", "text" => _("1/2 heure")),
		   array( "val" => "2700",  "sel" => "", "text" => _("3/4 heure")),
		   array( "val" => "3600",  "sel" => "", "text" => _("1 heure"))
		   );
  foreach ($refresh as $k => $v) {
    $refresh[$k]["sel"] = ($rinit==$v["val"] ? "selected" : "");
  }			  
  $action->lay->SetBlockData("refresh", $refresh);
 
  $dop = getParam("WGCAL_U_EVOPACITY", "70");
  $op = array();
  for ($iop=10; $iop<=100; $iop=$iop+10) {
    $op[] = array( "val" => "$iop",  "sel" => ($dop==$iop?"selected":""), "text" => "$iop%");
  }
  $action->lay->SetBlockData("evopac", $op);
 

  $popuptimer = array( "200" => _("200 milli second"),
		       "500" => _("1/2 second"), 
		       "1000" => _("1 second"), 
		       "1500" => _("1,5 second"));
  $opt = array(); $i = 0;
  foreach ($popuptimer as $k => $v) {
    $opt[$i]["optvalue"] = $k;
    $opt[$i]["optdescr"] = $v;
    $opt[$i]["optselect"] = ($k==$action->GetParam("WGCAL_U_ALTTIMER") ? "selected" : "");
    $i++;
  }
  $action->lay->SetBlockData("ALTTIMER", $opt);


  $opt = array(); $i = 0;
  $navb = array ( 0 => _("no"), 1 => _("yes"));
  foreach ($navb as $k => $v) {
    $opt[$i]["optvalue"] = $k;
    $opt[$i]["optdescr"] = $v;
    $opt[$i++]["optselect"] = ($k==$action->GetParam("WGCAL_U_BARMNAVDATE") ? "selected" : "");
  }
  $action->lay->SetBlockData("NAVB", $opt);
    
  
  $opt = array(); $i = 0;
  for ($i=0; $i<13; $i++) {
    $opt[$i]["optvalue"] = $i;
    $opt[$i]["optdescr"] = $i."H00";
    $opt[$i]["optselect"] = ($i==$action->GetParam("WGCAL_U_STARTHOUR") ? "selected" : "");
  }
  $action->lay->SetBlockData("SH", $opt);

  $opt = array(); $i = 0;
  for ($i=13; $i<24; $i++) {
    $opt[$i]["optvalue"] = $i;
    $opt[$i]["optdescr"] = $i."H00";
    $opt[$i]["optselect"] = ($i==$action->GetParam("WGCAL_U_STOPHOUR") ? "selected" : "");
  }
  $action->lay->SetBlockData("EH", $opt);
 
    
  $opt = array(); $i = 0;
  for ($i=0; $i<=23; $i++) {
    $opt[$i]["optvalue"] = $i;
    $opt[$i]["optdescr"] = $i."H";
    $opt[$i]["optselect"] = ($i==$action->GetParam("WGCAL_U_HSUSED",7) ? "selected" : "");
  }
  $action->lay->SetBlockData("HSUSED", $opt);
  $opt = array(); $i = 0;
  for ($i=0; $i<=23; $i++) {
    $opt[$i]["optvalue"] = $i;
    $opt[$i]["optdescr"] = $i."H";
    $opt[$i]["optselect"] = ($i==$action->GetParam("WGCAL_U_HEUSED",  23) ? "selected" : "");
  }
  $action->lay->SetBlockData("HEUSED", $opt);

  $opt = array(); $i = 0;
  $minc = array( "1/4 h" => 15, "1/2 h" => 30, "1 h" => 60);
  foreach ($minc as $k => $v) { 
    $opt[$i]["optvalue"] = $v;
    $opt[$i]["optdescr"] = $k;
     $opt[$i]["optselect"] = ($v==$action->getParam("WGCAL_U_RVDEFDUR", 60) ? "selected" : "");
    $i++;
  }
  $action->lay->SetBlockData("RVDEFDUR", $opt);

  $opt = array(); $i = 0;
  $minc = array( "5","15","30"); 
  foreach ($minc as $k => $v) {
    $opt[$i]["optvalue"] = $v;
    $opt[$i]["optdescr"] = $v." min.";
    $opt[$i]["optselect"] = ($v==$action->GetParam("WGCAL_U_MINCUSED",15) ? "selected" : "");
    $i++;
  }
  $action->lay->SetBlockData("MINCUSED", $opt);
 
    
 
    
  return;
}
?>
