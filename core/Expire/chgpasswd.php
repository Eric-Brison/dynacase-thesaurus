<?php
/**
 * Display interface to change password in case of expiration
 *
 * @author Anakeen 2003
 * @version $Id: chgpasswd.php,v 1.7 2004/06/22 14:32:46 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package WHAT
 * @subpackage 
 */
/**
 */

global $_POST;

if ($_POST["login"] == "") {
  print _("no login : passwd unchanged");
  exit;
}
include_once("Class.Application.php");
include_once("Class.User.php");
include_once('Class.SessionCache.php');

bindtextdomain ("what", "/home/httpd/what/locale");
textdomain ("what");
setlocale(LC_MESSAGES,getenv("LANG"));

$CoreNull = "";
$core = new Application();
$core->Set("CORE",$CoreNull);
$action = new Action();
$action->Set("",$core);

$core->user = new User();
$core->user->setLoginName($_POST["login"]);

if (! $core->user->isAffected()) {
  print _("unknown login : passwd unchanged");
  exit;
}

if ($_POST["passwd1"] != $_POST["passwd2"]) {
  print _("password are not identicals : not changed");
  exit;
}
if ($_POST["passwd1"] == "") {
  print _("empty password : not changed");
  exit;
}
$core->user->password_new=$_POST["passwd1"];
$core->user->expires = 0;
$core->user->modify();

global $_SERVER;

Header("Location: http://".$_SERVER['SERVER_NAME'].":".$_SERVER['SERVER_PORT']."/what/index.php?sole=R");
exit;
?>