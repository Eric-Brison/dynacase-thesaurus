<?php
/**
 * Generated Header (not documented yet)
 *
 * @author Anakeen 2000 
 * @version $Id: style_mod.php,v 1.3 2005/07/08 15:29:51 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package WHAT
 * @subpackage APPMNG
 */
 /**
 */

// ---------------------------------------------------------------
// $Id: style_mod.php,v 1.3 2005/07/08 15:29:51 eric Exp $
// $Source: /home/cvsroot/anakeen/freedom/core/Action/Appmng/style_mod.php,v $
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
// 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
// ---------------------------------------------------------------
// $Log: style_mod.php,v $
// Revision 1.3  2005/07/08 15:29:51  eric
// suppress CORE_USERDB
//
// Revision 1.2  2003/08/18 15:46:41  eric
// phpdoc
//
// Revision 1.1  2002/01/08 12:41:33  eric
// first
//
// Revision 1.1  2001/01/29 15:50:59  marianne
// prise en compte de la gestion des parametres
//
//
// ---------------------------------------------------------------
include_once("Class.SubForm.php");
include_once("Class.Param.php");

// -----------------------------------
function style_mod(&$action) {
// -----------------------------------
  // Get all the params      
  $style_id=GetHttpVars("id");
  $creation=GetHttpVars("creation");
  $name=GetHttpVars("name");
  if ($creation == "Y") {
    $ParamCour = new Param($action->GetParam("CORE_DB"));
  } else {
    $ParamCour = new Param($action->GetParam("CORE_DB"),array($style_id,$name));
  }
  $ParamCour->key=$style_id;
  $ParamCour->name=GetHttpVars("name");
  $ParamCour->val=GetHttpVars("val");
  if ($creation == "Y") {
    $res=$ParamCour->Add();
    if ($res != "") { 
      $txt = $action->text("err_add_param")." : $res";
      $action->Register("err_add_parameter",AddSlashes($txt));
    }
  } else {
    $res=$ParamCour->Modify();
    if ($res != "") { 
      $txt = $action->text("err_mod_param")." : $res";
      $action->Register("err_add_parameter",AddSlashes($txt));
    }
  }
  redirect($action,"APPMNG","STYLELIST");
}
?>
