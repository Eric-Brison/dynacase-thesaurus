<?php
/**
 * Action to delete a user account 
 *
 * The action also delete mail account of the user
 * @author Anakeen 2000 
 * @version $Id: user_del.php,v 1.3 2003/08/18 15:46:41 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package WHAT
 * @subpackage USERS
 */
/**
 */

// ---------------------------------------------------------------
// $Id: user_del.php,v 1.3 2003/08/18 15:46:41 eric Exp $
// $Source: /home/cvsroot/anakeen/freedom/core/Action/Users/user_del.php,v $
// ---------------------------------------------------------------
//  O   Anakeen - 2000
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
// 59 Temple Place, Suite 330, Boston, MA 02111-1307 USAr
// ---------------------------------------------------------------
include_once("Class.SubForm.php");
include_once("Class.MailAccount.php");
include_once("Class.User.php");
/**
 * Action to delete a user account 
 *
 * The action also delete mail account of the user
 * @param Action current action
 */
function user_del(&$action) {
// -----------------------------------

  // Get all the params      
  $id=GetHttpVars("id");

  if ($id !== "" && $id != 1) {
    $user = new User($action->GetParam("CORE_USERDB"),$id);
    if ( (isset($action->user)) && 
         ($action->HasPermission("ADMIN") ||
          (($action->HasPermission("DOMAIN_MASTER")) && 
           ($action->user->iddomain == $user->iddomain) && 
           ($action->user->id != $user->id)))) {
      $user->Delete();
      $mailapp = new Application();
      if (($action->user->isgroup != "Y") && $mailapp->Exists("MAILADMIN")) {
        $mailapp->Set("MAILADMIN", $action->parent);
        $acc = new MailAccount($mailapp->Getparam("MAILDB"),$id);
        $acc->Remove();
      }
    } else {
      $action->info("Access Not Allowed");
      Redirect($action,"CORE","");
    }
  }    

  if (isset($user) && ($user->isgroup == "Y")) {
    redirect($action,"USERS","GROUP_TABLE");
  } else {
    redirect($action,"USERS","USER_TABLE");
  }
}
?>
