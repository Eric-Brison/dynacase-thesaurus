<?php
// ---------------------------------------------------------------
// $Id: param_edit.php,v 1.2 2002/05/23 16:14:40 eric Exp $
// $Source: /home/cvsroot/anakeen/freedom/core/Action/Appmng/param_edit.php,v $
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
// $Log: param_edit.php,v $
// Revision 1.2  2002/05/23 16:14:40  eric
// param�tres utilisateur
//
// Revision 1.1  2002/01/08 12:41:33  eric
// first
//
// Revision 1.1  2001/01/29 15:50:59  marianne
// prise en compte de la gestion des parametres
//
// ---------------------------------------------------------------
include_once("Class.Param.php"); 
include_once("Class.SubForm.php"); 

// -----------------------------------
function param_edit(&$action) {
// -----------------------------------


  // Get all the params      
  $name=GetHttpVars("id");
  $vtype=GetHttpVars("vtype");
  $atype=GetHttpVars("atype",PARAM_APP);

    $action->lay->Set("vtype",$vtype);
    $action->lay->Set("atype",$atype);

  if ($name == "") {
    $input_name = new Layout($action->GetLayoutFile("input_name.xml"),$action);
    $action->lay->Set("NAME_EDIT",$input_name->gen());
    $param = new Param("");
    $action->lay->Set("name","");
    $action->lay->Set("val","");
    $action->lay->Set("TITRE",$action->text("titleparamcreate"));
    $action->lay->Set("BUTTONTYPE",$action->text("butcreate"));
  } else {
    $param = new Param($action->dbaccess, array($name,$atype,$vtype));
    $input_name = new Layout($action->GetLayoutFile("aff_name.xml"),$action);
    $input_name->Set( "NAME",$name);
    $action->lay->Set("NAME_EDIT",$input_name->gen());
    $action->lay->Set("name",$name);
    $action->lay->Set("val",$param->val);
    $action->lay->Set("TITRE",$action->text("titleparammodify"));
    $action->lay->Set("BUTTONTYPE",$action->text("butmodify"));
  }



}
?>
