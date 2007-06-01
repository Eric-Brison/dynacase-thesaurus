#! /usr/bin/php -q
<?php
/**
 * WHAT SHELL
 *
 * @author Anakeen 2002
 * @version $Id: wsh.php,v 1.28 2007/06/01 11:20:34 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package WHAT
 */
/**
 */

ini_set("max_execution_time", "3600");
include_once("WHAT/Lib.Prefix.php");
include_once('Class.Action.php');
include_once('Class.Application.php');
include_once('Class.Session.php');
include_once('Class.Log.php');


wbar(1,-1,"initialisation");
$log=new Log("","index.php");

$CoreNull = "";
global $CORE_LOGLEVEL;


// get param
global $_GET;
global $_SERVER;

if (isset($_SERVER['HTTP_HOST']))     {
  print "<BR><H1>:~(</H1>";
  exit;
}
if (count($argv) == 1) {
  print "Usage\twsh.php --app=APPLICATION --action=ACTION [--ARG=VAL] ...:  execute an action\n".
    "\twsh.php --api=API [--ARG=VAL] ....   :  execute an api function\n".
    "\twsh.php --listapi                     : view api list\n";
  exit;
}

while (list($k, $v) = each($argv)) {
  
  if (ereg("--([^=]+)=(.+)", $v , $reg)) {
    $_GET[$reg[1]]=$reg[2];
  }  else if (ereg("--(.+)", $v , $reg)) {
    if ($reg[1] == "listapi") {
      print "application list :\n";
      echo "\t- ";
      echo str_replace("\n","\n\t- ",shell_exec ("cd $pubdir/API;ls -1 *.php| cut -f1 -d'.'"));
      echo "\n";
      exit;
    }
    $_GET[$reg[1]]=true;
  }
}



$core = new Application();
if ($core->dbid < 0){
  print "Cannot access to main database";
  exit(1);
}

$core->Set("CORE",$CoreNull);
if (isset($_GET["userid"])) $core->user=new User("",$_GET["userid"]); //special user
else $core->user=new User("",1); //admin 

$core->session=new Session();

$CORE_LOGLEVEL=$core->GetParam("CORE_LOGLEVEL", "IWEF");


$puburl = $core->GetParam("CORE_PUBURL","http://".`hostname -f`."/what");


ini_set("memory_limit",$core->GetParam("MEMORY_LIMIT","32")."M");

$absindex=$core->GetParam("CORE_URLINDEX");
if ($absindex=='') {
  $absindex="$puburl/index.php";// try default 
 }

$core->SetVolatileParam("CORE_PUBURL", "."); // relative links
$core->SetVolatileParam("CORE_ABSURL", $puburl."/"); // absolute links
$core->SetVolatileParam("CORE_JSURL", "WHAT/Layout");
$core->SetVolatileParam("CORE_ROOTURL", "$absindex?sole=R&");
$core->SetVolatileParam("CORE_BASEURL", "$absindex?sole=A&");
$core->SetVolatileParam("CORE_SBASEURL","$absindex?sole=A&");// no session
$core->SetVolatileParam("CORE_STANDURL","$absindex?sole=Y&");
$core->SetVolatileParam("CORE_SSTANDURL","$absindex?sole=Y&"); // no session
$core->SetVolatileParam("CORE_ASTANDURL","$absindex?sole=Y&"); // absolute links

if (isset($_GET["app"])) {
  $appl = new Application();
  $appl->Set($_GET["app"],
	     $core);
} else {
  $appl = $core;
}

$action = new Action();
if (isset($_GET["action"])) {
  $action->Set($_GET["action"],
	       $appl);
} else {
  $action->Set("",$appl);
}




  // init for gettext
  
// init for gettext
setlocale(LC_MESSAGES,$core->Getparam("CORE_LANG"));  
putenv ("LANG=".$core->Getparam("CORE_LANG")); // needed for old Linux kernel < 2.4
bindtextdomain ("what", "$pubdir/locale");
bind_textdomain_codeset("what", 'ISO-8859-15');
textdomain ("what");
  


if (isset($_GET["api"])) {
  if (!include "API/".$_GET["api"].".php") {
    echo sprintf(_("API file %s not found"),"API/".$_GET["api"].".php");
  }
} else {
  if (! isset($_GET["wshfldid"])) {
    echo ($action->execute ());
  } else {
    // REPEAT EXECUTION FOR FREEDOM FOLDERS
    $dbaccess=$appl->GetParam("FREEDOM_DB");
    if ($dbaccess == "") {
      print "Freedom Database not found : param FREEDOM_DB";
      exit;
    }
    include_once("FDL/Class.Doc.php");
    $http_iddoc="id"; // default correspondance
    if (isset($_GET["wshfldhttpdocid"])) $http_iddoc=$_GET["wshfldhttpdocid"];
    $fld=new_Doc($dbaccess,$_GET["wshfldid"]);
    $ld=$fld->getContent();
    foreach ($ld as $k=>$v) {
      $_GET[$http_iddoc]=$v["id"];
      try {
	echo ($action->execute ());
      }
      catch (Exception $e) {
	echo sprintf(_("Caught Exception : %s<br>\n"),$e->getMessage());
      }
      echo "<hr>";

    }

  }
}

wbar(-1,-1,"completed");

return(0);
?>
