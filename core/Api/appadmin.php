<?php
/**
 * Add, modify or delete WHAT application
 * 
 *
 * @param string $appname internal name of the application
 * @param string $method may be "init","reinit","update","delete"
 * @author Anakeen 2003
 * @version $Id: appadmin.php,v 1.8 2008/05/21 07:27:02 marc Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package WHAT
 * @subpackage WSH
 */
/**
 */
include_once("Class.Application.php");

$appname = GetHttpVars("appname","");
$method = GetHttpVars("method","init");

if ($appname=="") {
  echo "[appadmin::$method] missing application. Please set it whith --appname=<application name>\n";
  exit;
}
if ($method!="init" && $method!="reinit" && $method!="update" && $method!="delete") {
  echo "[appadmin] allowed method : init reinit update delete.\n";
  exit;
}
echo " $appname...$method\n";
$app=new Application();
$Null = "";
$app->Set($appname,$Null);
if ($method == "reinit") $app->InitApp($appname,false);
if ($method == "update") $app->InitApp($appname,true);
if ($method == "delete") $app->DeleteApp();

?>
