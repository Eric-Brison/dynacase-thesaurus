<?php
// ---------------------------------------------------------------
// $Id: helpview.php,v 1.1 2002/01/08 12:41:33 eric Exp $
// $Source: /home/cvsroot/anakeen/freedom/core/Action/Core/helpview.php,v $
// ---------------------------------------------------------------
//  O   Anakeen - 2001
// O*O  Anakeen development team
//  O   dev@anakeen.com
// ---------------------------------------------------------------
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or (at
//  your option) any later version.
//
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
// or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License
// for more details.
//
// You should have received a copy of the GNU General Public License along
// with this program; if not, write to the Free Software Foundation, Inc.,
// 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
// ---------------------------------------------------------------
// ---------------------------------------------------------------


// -----------------------------------
function helpview(&$action) {
// -----------------------------------
  
  $id = strtoupper (GetHttpVars("id"));

  include $action->GetParam("HELP_PUBURL")."/helpid.php";

  $kid = "";
  while(list($hid,$file) = each($helpids)) {
    if (strtoupper($hid) == $id) {
      $kid = $hid;
      break;
    }
  }

  if ($kid != "") {
    $ret = Header  ("Location: ".$action->GetParam("CORE_PUBURL")."/doc/aide/html/".$helpids[$kid]."#".$kid);
  
    exit;
  } else {
    $errtext=sprintf( _("index %s not found."),$id);
    $action->ExitError($errtext);
  }
}
?>