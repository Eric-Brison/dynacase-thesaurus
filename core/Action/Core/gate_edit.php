<?php
/**
 * Display buttons to edit gate
 *
 * @author Anakeen 2000 
 * @version $Id: gate_edit.php,v 1.4 2006/02/28 08:06:18 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package WHAT
 * @subpackage CORE
 */
 /**
 */



function gate_edit(&$action) {
  $action->parent->AddJsRef($action->GetParam("CORE_JSURL")."/geometry.js");
  $action->parent->AddJsRef($action->GetParam("CORE_JSURL")."/subwindow.js");

  
  global $_SERVER;
  global $CacheObj;

  $CacheObj=array();
  session_unregister("CacheObj"); // clearcache
  $action->lay->set("PHP_AUTH_USER",$_SERVER['PHP_AUTH_USER']);    

}

?>