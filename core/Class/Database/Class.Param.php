<?php
// ---------------------------------------------------------------------------
// Param
// ---------------------------------------------------------------------------
// Anakeen 2000 - yannick.lebriquer@anakeen.com
// ---------------------------------------------------------------------------
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
// ---------------------------------------------------------------------------
//  $Id: Class.Param.php,v 1.6 2002/05/28 09:00:25 eric Exp $
//
include_once('Class.Log.php');
include_once('Class.DbObj.php');

$CLASS_PARAM_PHP = '$Id: Class.Param.php,v 1.6 2002/05/28 09:00:25 eric Exp $';

define("PARAM_APP","A");
define("PARAM_GLB","G");
define("PARAM_USER","U");
define("PARAM_STYLE","S");

Class Param extends DbObj
{
var $fields = array ("name","type","appid","val");

var $id_fields = array ("name","type","appid");

var $dbtable = "paramv";

var $sqlcreate = '
      create table paramv (
              name   varchar(50),
              type   varchar(21),
              appid  int4,
              val    varchar(200));
      create index paramv_idx2 on paramv(name);
      create unique index paramv_idx3 on paramv(name,type,appid);
                 ';

var $buffer=array();
   
function PreInsert( )
{
    if (strpos($this->name," ")!=0) {
      return "Le nom du param�tre ne doit pas contenir d'espace";
    }
}

function PreUpdate( )
{
   $this->PreInsert(); 
}

function SetKey($appid,$userid=ANONYMOUS_ID,$styleid="0") {
  $this->appid=$appid;
  $this->buffer=array_merge($this->buffer,$this->GetAll($appid,$userid,$styleid));
}

function Set($name,$val,$type=PARAM_GLB,$appid='')
{
  $this->name = $name;
  $this->val = $val;
  $this->type = $type;
  $this->appid = $appid;

  if ($this->Add() != "") {
    $this->Modify();
  } 
  $this->buffer[$name]=$val;
}

function SetVolatile($name,$val)
{
   $this->buffer[$name]=$val;
}

function Get($name,$def="")
{
   if (isset($this->buffer[$name])) {
     return ($this->buffer[$name]);
   } else {
     return ($def);
   }
}
   
function GetAll($appid="",$userid=ANONYMOUS_ID,$styleid="0")
{
   if ($appid=="") $appid=$this->appid;
   $query = new QueryDb($this->dbaccess,"Param");
   
   $list = $query->Query(0,0,"TABLE","select distinct on(name) * from {$this->dbtable} where ". 
			 "(type = '".PARAM_GLB."') ".
			 " OR (type='".PARAM_APP."' and appid=$appid)".
			 " OR (type='".PARAM_USER.$userid."' and appid=$appid)".
			 " OR (type='".PARAM_STYLE.$styleid."' and appid=$appid)".
			 " order by name, type desc");

   //print $query->LastQuery."<HR>";
   if ($query->nb != 0) {
     while(list($k,$v)=each($list)) {
       $out[$v["name"]]=$v["val"];
     }
   } else {
     $out = NULL;
     $this->log->debug("$appid no constant define for this application");
   }
   //   print_r2($out);
   return($out);
}
 
function GetUser($userid=ANONYMOUS_ID,$styleid="")
{
   $query = new QueryDb($this->dbaccess,"Param");
   
   $tlist = $query->Query(0,0,"TABLE","select  distinct on(paramv.name, paramv.appid) paramv.*,  paramdef.descr, paramdef.kind  from paramv, paramdef where paramv.name = paramdef.name and paramdef.isuser='Y' and (". 
			 " (type = '".PARAM_GLB."') ".
			 " OR (type='".PARAM_APP."')".
			 " OR (type='".PARAM_STYLE.$styleid."' )".
			 " OR (type='".PARAM_USER.$userid."' ))".
			 " order by paramv.name, paramv.appid, paramv.type desc");


   return($tlist);
}

function GetStyle($styleid)
{
   $query = new QueryDb($this->dbaccess,"Param");
   
   $tlist = $query->Query(0,0,"TABLE","select  distinct on(paramv.name, paramv.appid) paramv.*,  paramdef.descr, paramdef.kind  from paramv, paramdef where paramv.name = paramdef.name and paramdef.isstyle='Y' and (". 
			 " (type = '".PARAM_GLB."') ".
			 " OR (type='".PARAM_APP."')".
			 " OR (type='".PARAM_STYLE.$styleid."' ))".
			 " order by paramv.name, paramv.appid, paramv.type desc");

   
   return($tlist);
}

function GetApps()
{
   $query = new QueryDb($this->dbaccess,"Param");
   
   $tlist = $query->Query(0,0,"TABLE","select  paramv.*, paramdef.descr, paramdef.kind  from paramv, paramdef where paramv.name = paramdef.name and  (". 
			 " (type = '".PARAM_GLB."') ".
			 " OR (type='".PARAM_APP."'))".
			 " order by paramv.appid,  type desc");



   return($tlist);
}


// delete paramters that cannot be change after initialisation
function DelStatic($appid)
{

    $query = new QueryDb($this->dbaccess,"Param");
    $query->AddQuery ("appid=$appid");
    $query->AddQuery ("kind='static'");
    $list = $query->Query(0,0,"LIST","select paramv.*  from paramv, paramdef where paramdef.name=paramv.name and paramdef.kind='static' and paramv.appid=$appid");
    if ($query->nb != 0) {
      while(list($k,$v)=each($list)) {
        $v->Delete();
      }
    } 

}



function DelAll($appid="")
{
  return;
//    if ($key=="") $key=$this->key;
//    $query = new QueryDb($this->dbaccess,"Param");
//    $query->basic_elem->sup_where = array ("key=$key");
//    $list = $query->Query();
//    if ($query->nb != 0) {
//      while(list($k,$v)=each($list)) {
//        $v->Delete();
//      }
//    } else {
//      $out = NULL;
//      $this->log->debug("$key, no constant define for this key");
//    }
//    $this->buffer=array();
}


// FIN DE CLASSE
}
?>
