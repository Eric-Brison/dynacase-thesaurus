<?php

include_once('FDL/Lib.Dir.php');
function savesvc(&$action) {

  $dbaccess = getParam("FREEDOM_DB");
  
  $snum = GetHttpVars("snum", -1);
  if ($snum==-1 || $snum=="") {
    $action->lay->set("OUT", "var svcnum = -1;");
    return;
  }

  $tup = GetChildDoc( getParam("FREEDOM_DB"), 0, 0, "ALL", 
		     array("uport_ownerid = ".$action->user->fid), $action->user->id, "LIST", "USER_PORTAL");
  if (count($tup)<1 || !$tup[0]->isAffected()) {
    $action->lay->set("OUT", "var svcnum = -1;");
    return;
  } else {
    $up = $tup[0];
  }

  $p = urldecode(GetHttpVars("params", ""));

  $svcnum   = $up->getTValue("uport_svcnum");
  $svcparam = $up->getTValue("uport_param");

  $change = false;
  foreach ($svcnum as $k => $v) {
    if ($snum==$v) {
      $svcparam[$k] = $p;
      $change = true;
    }
  }
  if ($change) {
    $up->setValue("uport_param", $svcparam);
    $err = $up->modify();
    $up->postModify();
    $action->lay->set("OUT", "var svcnum = $snum;");
  } else $action->lay->set("OUT", "var svcnum = -1;");
}
?>