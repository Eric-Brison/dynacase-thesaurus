<?php
/**
 * Parameters modification
 *
 * @author Anakeen 2000 
 * @version $Id: param_mod.php,v 1.8 2005/11/10 15:43:40 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package WHAT
 * @subpackage APPMNG
 */
 /**
 */


include_once("Class.SubForm.php");
include_once("Class.Param.php");

// -----------------------------------
function param_mod(&$action) {
  // -----------------------------------
    // Get all the params      

      $appid=GetHttpVars("appid");
  $name =GetHttpVars("aname");
  $atype=GetHttpVars("atype",PARAM_APP);
  $val  =GetHttpVars("val");
  
  $ParamCour = new Param($action->dbaccess,array($name,$atype,$appid));
  if (! $ParamCour->isAffected()) {
    $ParamCour->appid=$appid;
    $ParamCour->type=$atype;
    $ParamCour->name=$name;
    $ParamCour->val=$val;
    $res=$ParamCour->Add();
    if ($res != "") { 
      $action->addLogMsg( $action->text("err_add_param")." : $res");
    }
  } else {
    $ParamCour->val=$val;
    $res=$ParamCour->Modify();
    if ($res != "") { 
      $action->addLogMsg( $action->text("err_mod_parameter")." : $res");
    }
  }
  
  // reopen a new session to update parameters cache
  //unset($_SESSION["CacheObj"]);
  $prevact=$action->Read("PARAM_ACT","PARAM_ALIST");
  $action->parent->session->close();
  //      unset($_SESSION["CacheObj"]);
   redirect($action,"APPMNG",$prevact);
  
}

// -----------------------------------
function param_umod(&$action) {
// -----------------------------------

 
  $atype=GetHttpVars("atype",PARAM_APP);
  $appid=GetHttpVars("appid");
  if ($atype[0] != PARAM_USER) $action->exitError(_("only user parameters can be modified with its action"));
  if (substr($atype,1) != $action->user->id) $action->exitError(_("only current user parameters can be modified with its action"));

  param_mod($action);
}

?>
