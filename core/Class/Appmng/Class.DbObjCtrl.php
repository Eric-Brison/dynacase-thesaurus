<?php
// ---------------------------------------------------------------
// $Id: Class.DbObjCtrl.php,v 1.1 2002/01/08 12:41:34 eric Exp $
// $Source: /home/cvsroot/anakeen/freedom/core/Class/Appmng/Class.DbObjCtrl.php,v $
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
// $Log: Class.DbObjCtrl.php,v $
// Revision 1.1  2002/01/08 12:41:34  eric
// first
//
// Revision 1.7  2001/12/11 08:16:15  eric
// correction oid -> id
//
// Revision 1.6  2001/11/28 16:47:21  eric
// correction for possible external database
//
// Revision 1.5  2001/11/21 16:06:21  eric
// modif methode initialisation
//
// Revision 1.4  2001/11/14 15:16:38  eric
// modif pour optimisation
//
// Revision 1.3  2001/10/12 10:21:50  eric
// ajout method IsControlled
//
// Revision 1.2  2001/10/10 12:04:41  eric
// a
//
// Revision 1.1  2001/09/07 16:48:59  eric
// gestion des droits sur les objets
//

//
// ---------------------------------------------------------------


$CLASS_CONTROLLED_PHP = '$Id: Class.DbObjCtrl.php,v 1.1 2002/01/08 12:41:34 eric Exp $';

include_once('Class.ObjectPermission.php');
include_once('Class.Application.php');

Class DbObjCtrl extends DbObj
{
  var $obj_acl = array (); //should be replaced by the Child Class 

  var $operm;
  var $action;
  // --------------------------------------------------------------------
  function DbObjCtrl ($dbaccess='', $id='',$res='',$dbid=0) {
    // --------------------------------------------------------------------
    global $action; // necessary to see information about user privilege
    $this->action=&$action;

    $app = new Application(); // to get class id
    $this->classid = $app->GetIdFromName(get_class($this)); 

    DbObj::DbObj($dbaccess, $id,$res,$dbid);



    

      
  }

  function PostSelect()
    {
      if ($this->IsControlled()) {

    $this->operm= new ObjectPermission("", 
                                       array($this->action->parent->user->id,
				             $this->id ));
      }



    }


  // --------------------------------------------------------------------
  function Control ($aclname) {
    // -------------------------------------------------------------------- 
    if ($this->IsAffected())
      if ($this->IsControlled()) 
	return $this->operm->Control($this, $aclname);
      else return "";

    return "object not initialized ; $aclname";
  }
  // --------------------------------------------------------------------
  function PostUpdate()
    // --------------------------------------------------------------------    
    {
      // add controlled object
      	      
      if (!isset($this->id)) return "";

      $cobj = new ControlObject("",array($this->id, $this->classid));

      $cobj->description = $this->Description();
      $cobj->Modify();
    }  

  // --------------------------------------------------------------------
  function SetControl()
    // --------------------------------------------------------------------    
    {
      // record object as controlled object
      	           
      //print "SetControl:$this->id, $this->classid";
      if (!isset($this->id)) return "";

      $cobj = new ControlObject();
      $cobj->id_obj= $this->id;
      $cobj->id_class = $this->classid;
      $cobj->description = $this->Description();
      $cobj->Add();

      $this->operm= new ObjectPermission("", 
                                         array($this->action->parent->user->id,
				               $this->id ));
      $acl =new Acl();

      $acl-> Set("modifyacl", $this->classid);
      $this->operm->id_acl = $acl->id;
      $this->operm->Add();

      $acl-> Set("viewacl", $this->classid);
      $this->operm->id_acl = $acl->id;
      $this->operm->Add();
    }

  // --------------------------------------------------------------------
  function UnsetControl()
    // --------------------------------------------------------------------    
    {
      // delete object as controlled object
      	           
      
      if (!isset($this->id)) return "";

      $cobj = new ControlObject("", array($this->id, $this->classid));

      $cobj->Delete();
    }

  // --------------------------------------------------------------------
  function IsControlled() {
  // --------------------------------------------------------------------
    $cobj = new ControlObject("", array($this->id, $this->classid));
    return $cobj->IsAffected();

  }

  // --------------------------------------------------------------------
  function PostDelete()    
    // --------------------------------------------------------------------
    {
      // ------------------------------
      // delete control object
      
      $cobj = new ControlObject("", array($this->id, $this->classid ));
      $cobj-> Delete();

    }

  // --------------------------------------------------------------------
  function Description() {
    // -------------------------------------------------------------------- 
    // This function should be replaced by the Child Class 
    return "None";
  }


  // --------------------------------------------------------------------
  function InitObjectAcl()
    // init ACL table with object acls
    // -------------------------------------------------------------------- 
    {

      $defacl =array(array(
			   "name"		=>"modifyacl",
			   "description"	=>N_("modify object acl")),
		     array(
			   "name"		=>"viewacl",
			   "description"	=>N_("view object acl"))
		     );

      if (! ((isset ($obj->obj_acl)) && (is_array($obj->obj_acl)))) {
	$this->log->warning("InitObjectAcl no Acl for object class ".get_class($this));
      }
      
      
      $this->obj_acl= array_merge($this->obj_acl, $defacl);
      
      $app = new Application();

      if (($id_app = $app->GetIdFromName(get_class($this)) ) == 0) {
	// create if not exist
	$app->name = get_class($this);
	$app->short_name = get_class($this)." Class";
	$app->description = get_class($this)." Class";
	$app->access_free = "N";
	$app->available = "N";
	$app->icon = "";
	$app->displayable = "N";
	$app->objectclass = "Y";
	$app -> Add();
	$id_app = $app->id;
      } else {
	$app->Select($id_app);
      }

      $acl = new Acl();
    
      $acl->Init($app, $this->obj_acl, true);

            
    }
}
?>
