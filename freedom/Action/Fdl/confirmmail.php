<?php
/**
 * Generated Header (not documented yet)
 *
 * @author Anakeen 2000 
 * @version $Id: confirmmail.php,v 1.3 2008/02/28 17:50:36 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage 
 */
 /**
 */

// ---------------------------------------------------------------
// $Id: confirmmail.php,v 1.3 2008/02/28 17:50:36 eric Exp $
// $Source: /home/cvsroot/anakeen/freedom/freedom/Action/Fdl/confirmmail.php,v $
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

include_once("FDL/editmail.php");

// -----------------------------------
// -----------------------------------
function confirmmail(&$action) {

  $nextstate = GetHttpVars("state"); 
  $ulink = GetHttpVars("ulink"); 
  editmail($action); 

 
  $action->lay->Set("ulink",$ulink);
  $action->lay->Set("state",$nextstate);
  $action->lay->Set("tstate",_($nextstate));
  
}