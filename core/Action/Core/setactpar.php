<?php
// ---------------------------------------------------------------
// $Id: setactpar.php,v 1.1 2002/01/08 12:41:33 eric Exp $
// $Source: /home/cvsroot/anakeen/freedom/core/Action/Core/setactpar.php,v $
// ---------------------------------------------------------------
//    O   Anakeen - 2000
//   O*O  Anakeen Hacking Group
//    O   hack@anakeen.com
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
// $Log: setactpar.php,v $
// Revision 1.1  2002/01/08 12:41:33  eric
// first
//
// Revision 1.2  2001/02/07 11:31:11  yannick
// M�nage sur debug
//
// Revision 1.1  2001/02/06 16:32:45  yannick
// Add Action-Session set action
//
// Revision 1.2  2000/10/10 19:09:11  marc
// Mise au point
//
// Revision 1.1  2000/10/06 19:37:44  marc
// Creation
//
//
// ---------------------------------------------------------------
include_once("Class.Action.php");


// -----------------------------------
function setactpar(&$action) {
// -----------------------------------
  global $HTTP_POST_VARS;
  reset($HTTP_POST_VARS);
  $sapp = GetHttpVars("sapp",$action->name);
  $sact = GetHttpVars("sact",$action->parent->name);

  $key  = GetHttpVars("key","");

  $app = new Application($action->dbaccess);
  $app->Set($sapp,$action->parent);
  $act = new Action($action->dbaccess);
  $act->Set($sact,$app,$action->session);

  if ($key != "") {
    if ($key != "all") {
      $val = GetHttpVars("$key","");
      $act->ActRegister($key,$val);
    } else {
      global $HTTP_POST_VARS;
      reset($HTTP_POST_VARS);
      while (list($k,$v)=each($HTTP_POST_VARS)) {
        if (($k != "key") && ($k != "sapp") && ($k != "sact")) {
          $act->ActRegister($k,$v);
        }
      }
    }
  }

  redirect($action,$sapp,$sact);
}
?>
