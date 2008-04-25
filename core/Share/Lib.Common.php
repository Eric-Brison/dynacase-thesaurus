<?php
/**
 * Common util functions
 *
 * @author Anakeen 2002
 * @version $Id: Lib.Common.php,v 1.42 2008/04/25 09:18:57 jerome Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package WHAT
 * @subpackage CORE
 */
/**
 */
include_once("Lib.Prefix.php");

function N_($s) {return ($s);} // to tag gettext without change text immediatly
// library of utilies functions

function print_r2($z,$ret=false) {
  print "<PRE>";
  print_r($z, $ret);
  print "</PRE>";
  flush();
}

function AddLogMsg($msg) {
  global $action;
  if (isset($action->parent))
    $action->parent->AddLogMsg($msg);
}

function AddWarningMsg($msg) {
  global $action;
  if (isset($action->parent))
    $action->parent->AddWarningMsg($msg);
}

function getMailAddr($userid, $full=false) { 
  $user = new User("",$userid);

  if ($user->isAffected()) {
    $pren = $postn = "";
    if ($full) {
      //	$pren = ucfirst(strtolower($user->getTitle()))." <";
      // $postn = ">";
    }
    return $pren.$user->getMail().$postn;
  }
  return false;
}

function GetParam($name, $def="") {
  global $action;
  if ($action)  return $action->getParam($name,$def);
  
  // case of without what context
  include_once("Class.Action.php");
  $core = new Application();
  $core->Set("CORE",$CoreNull);
  $act = new Action();
  $act->Set("",$core);
  return  $act->getParam($name, $def);
}

function getLayoutFile($app, $layfile) {
  $socStyle = Getparam("CORE_SOCSTYLE");
  if ($socStyle != "") {
    $root = Getparam("CORE_PUBDIR");
    $file = $root."/$app/Layout/$socStyle/$layfile";
    
    if (file_exists($file))  return($file);
    
  }
  return $app."/Layout/".$layfile;
}

function microtime_diff($a,$b) {
    list($a_micro, $a_int)=explode(' ',$a);
     list($b_micro, $b_int)=explode(' ',$b);
     if ($a_int>$b_int) {
        return ($a_int-$b_int)+($a_micro-$b_micro);
     } elseif ($a_int==$b_int) {
        if ($a_micro>$b_micro) {
          return ($a_int-$b_int)+($a_micro-$b_micro);
        } elseif ($a_micro<$b_micro) {
           return ($b_int-$a_int)+($b_micro-$a_micro);
        } else {
          return 0;
        }
     } else { // $a_int<$b_int
        return ($b_int-$a_int)+($b_micro-$a_micro);
     }
}

function getDbid($dbaccess) {
  global $CORE_DBID;

  if (!isset($CORE_DBID) || !isset($CORE_DBID[$dbaccess])) {
    $CORE_DBID[$dbaccess] = pg_connect($dbaccess);
  }
  return $CORE_DBID[$dbaccess];
}

function getDbAccess() {
  return getDbAccessCore();
}

function getDbAccessCore() {
  return "service='".getServiceCoreFromEnv()."'";
}

function getDbAccessFreedom() {
  return "service='".getServiceFreedomFromEnv()."'";
}

function getDbEnv() {
  error_log("Deprecated call to getDbEnv() : use getFreedomEnv()");
  return getFreedomEnv();
}

function getFreedomEnv() {
  $freedomenv=getenv("freedomenv");
  if( $freedomenv == false || $freedomenv == "" ) {
    return "default";
  }
  return $freedomenv;
}

function getServiceCoreFromEnv($freedomenv="") {
  global $PGSERVICE_CORE;
  global $pubdir;

  if ($freedomenv == "") {
    $freedomenv = getFreedomEnv();
  }

  if ($PGSERVICE_CORE != "") return $PGSERVICE_CORE;

  $pgservice_core = "";

  $freedomenv = getFreedomEnv();
  if ($freedomenv != "") {
    $filename="$pubdir/virtual/$freedomenv/dbaccess.php";
    if (file_exists($filename)) {
      include($filename);
    }    
  }

  if ($pgservice_core == "") {
    include("dbaccess.php");
  }

  $PGSERVICE_CORE=$pgservice_core;
  return $PGSERVICE_CORE;  
}

function getServiceFreedomFromEnv($freedomenv="") {
  global $PGSERVICE_FREEDOM;
  global $pubdir;

  if ($freedomenv == "") {
    $freedomenv = getFreedomEnv();
  }

  if ($PGSERVICE_FREEDOM != "") return $PGSERVICE_FREEDOM;

  $pgservice_freedom = "";

  $freedomenv = getFreedomEnv();
  if ($freedomenv != "") {
    $filename = "$pubdir/virtual/$freedomenv/dbaccess.php";
    if (file_exists($filename)) {
      include($filename);
    }
  }

  if ($pgservice_freedom == "") {
    include("dbaccess.php");
  }

  $PGSERVICE_FREEDOM = $pgservice_freedom;
  return $PGSERVICE_FREEDOM;
}

function getDbName($dbaccess) {
  error_log("Deprecated call to getDbName(dbaccess) : use getServiceName(dbaccess)");
  return getServiceName($dbaccess);
}

function getServiceName($dbaccess) {
  if (ereg("service='?([a-zA-Z0-9_-.]+)", $dbacces, $reg)) {
    return $reg[1];
  }
}

/**
 * return shell commande for wsh
 * depending of database (in case of several instances)
 * @param bool $nice set to true if want nice mode
 * @param int $userid the user identificator to send command (if 0 send like admin without specific user parameter)
 * @param bool $sudo set to true if want to be send with sudo (need /etc/sudoers correctly configured)
 * @return string the command
 */
function getWshCmd($nice=false,$userid=0,$sudo=false) {
  $freedomenv=getFreedomEnv(); // choose when several databases
  $wsh="export freedomenv=$freedomenv;";
  if ($nice) $wsh.= "nice -n +10 ";
  if ($sudo) $wsh.= "sudo ";
  $wsh.=GetParam("CORE_PUBDIR")."/wsh.php  ";
  $userid=intval($userid);
  if ($userid>0) $wsh.="--userid=$userid ";
  return $wsh;
}

/**
 * get the system user id
 * @return int
 */
function getUserId() {
  global $action;
  if ($action)  return $action->user->id;
 
  return 0;
}
/**
 * exec list of unix command in background
 * @param array $tcmd unix command strings
 */
function bgexec($tcmd,&$result,&$err) {
  $foutname = uniqid("/tmp/bgexec");
  $fout = fopen($foutname,"w+");
  fwrite($fout,"#!/bin/bash\n");
  foreach ($tcmd as $v) {
    fwrite($fout,"$v\n");
  }
  fclose($fout);
  chmod($foutname,0700);

  //  if (session_id()) session_write_close(); // necessary to close if not background cmd 
  exec("exec nohup $foutname > /dev/null 2>&1 &",$result,$err); 
  //if (session_id()) @session_start();
}

function wbartext($text) {
  wbar('-','-',$text);
}

function wbar($reste,$total,$text="",$fbar=false) {
  static $preste,$ptotal;
  if (!$fbar) $fbar = GetHttpVars("bar"); // for progress bar
  if ($fbar) {   
    if ($reste==='-') $reste=$preste;
    else $preste=$reste;
    if ($total==='-') $total=$ptotal;
    else $ptotal=$total;
    if (file_exists("$fbar.lck")) {
      $wmode="w";
      unlink("$fbar.lck");
    } else {
      $wmode="a";	
    }
    $ffbar=fopen($fbar,$wmode);
    fputs($ffbar,"$reste/$total/$text\n");
    fclose($ffbar);      
  }
}

function getJsVersion() {
  include_once("Class.QueryDb.php");
  $q=new QueryDb("","param");
  $q->AddQuery("name='VERSION'");
  $l=$q->Query(0,0,"TABLE");
  $nv=0;
  foreach ($l as $k=>$v) {  
    $nv+=intval(str_replace('.','',$v["val"]));
  }
  
  return $nv;
}

/**
 * produce an anchor mailto '<a ...>'
 * @param string to a valid mail address or list separated by comma -supported by client-
 * @param string anchor content <a...>anchor content</a>
 * @param string subject 
 * @param string cc
 * @param string bcc
 * @param array treated as html anchor attribute : key is attribute name and value.. value
 * @param string force link to be produced according the value
 * @return string like user admin dbname anakeen
 */
function setMailtoAnchor($to, $acontent="", $subject="", $cc="", $bcc="", $from="", $anchorattr=array(), $forcelink="" ) {

  global $action;
  
  if ($to=="") return '';

  if ($forcelink=="mailto"||$forcelink=="squirrel") {
    $target = $forcelink;
  } else {
    $target = strtolower(GetParam("CORE_MAIL_LINK", "optimal"));
    if ($target=="optimal") {
      $target = "mailto";
      if ($action->user->iddomain>9) { 
	$query=new QueryDb($action->dbaccess,"Application");
	$query->basic_elem->sup_where=array("name='MAIL'","available='Y'", "displayable='Y'");
	$list = $query->Query(0,0,"TABLE");
	if ($query->nb>0)  {
	  $queryact=new QueryDb($action->dbaccess,"Action");
	  $queryact->AddQuery("id_application=".$list[0]["id"]);
	  $queryact->AddQuery("root='Y'");
	  $listact = $queryact->Query(0,0,"TABLE");
	  $root_acl_name=$listact[0]["acl"];
	  if ($action->HasPermission($root_acl_name,$list[0]["id"])) {
	    $target = "squirrel";
	  }
	}
      }
    }
  }
  $prot = ($_SERVER["HTTPS"]=="on" ? "https" : "http" );
  $host = $_SERVER["SERVER_NAME"];
  $port = $_SERVER["SERVER_PORT"];

  $attrcode = "";
  if (is_array($anchorattr)) {
    foreach ($anchorattr as $k => $v) $attrcode .= ' '.$k.'="'.$v.'"';
  }

  $subject = str_replace(" ", "%20", $subject);

  switch ($target) {

  case "squirrel" :
    $link  = ' <a ';
    $link .= 'href="'.$prot."://".$host.":".$port."/".GetParam("CORE_MAIL_SQUIRRELBASE", "squirrel")."/src/compose.php?";
    $link .= "&send_to=".$to;
    $link .= ($subject!="" ? '&subject='.$subject : '');
    $link .= ($cc!="" ? '&cc='.$cc : '');
    $link .= ($bcc!="" ? '&bcc='.$bcc : '');
    $link .= '"';
    $link .= $attrcode;
    $link .= '>';
    $link .= $acontent;
    $link .= '</a>';
    break;

  case "mailto":
    $link  = '<a '; 
    $link .= 'href="mailto:'.$to.'"';
    $link .= ($subject!="" ? '&Subject='.$subject : '');
    $link .= ($cc!="" ? '&cc='.$cc : '');
    $link .= ($bcc!="" ? '&bcc='.$bcc : '');
    $link .= '"';
    $link .= $attrcode;
    $link .= '>';
    $link .= $acontent;
    $link .= '</a>';
    break;
    
  default:   
    $link = '<span '.$classcode.'>'.$acontent.'</span>';
  }
  return $link;
}


/**
 * Returns <kbd>true</kbd> if the string or array of string is encoded in UTF8.
 *
 * Example of use. If you want to know if a file is saved in UTF8 format :
 * <code> $array = file('one file.txt');
 * $isUTF8 = isUTF8($array);
 * if (!$isUTF8) --> we need to apply utf8_encode() to be in UTF8
 * else --> we are in UTF8 :)
 * </code>
 * @param mixed A string, or an array from a file() function.
 * @return boolean
 */
function isUTF8($string)
{
  if (is_array($string))   return seems_utf8(implode('', $string));
  else return seems_utf8($string);
}

/**
 * Returns <kbd>true</kbd> if the string  is encoded in UTF8.
 *
 * @param mixed $Str string
 * @return boolean
 */
function seems_utf8($Str) {
 for ($i=0; $i<strlen($Str); $i++) {
  if (ord($Str[$i]) < 0x80) $n=0; # 0bbbbbbb
  elseif ((ord($Str[$i]) & 0xE0) == 0xC0) $n=1; # 110bbbbb
  elseif ((ord($Str[$i]) & 0xF0) == 0xE0) $n=2; # 1110bbbb
  elseif ((ord($Str[$i]) & 0xF0) == 0xF0) $n=3; # 1111bbbb
  else return false; # Does not match any model
  for ($j=0; $j<$n; $j++) { # n octets that match 10bbbbbb follow ?
   if ((++$i == strlen($Str)) || ((ord($Str[$i]) & 0xC0) != 0x80)) return false;
  }
 }
 return true;
}

/**
 * return true if it is possible to manage USER by FREEDOM
 * 
 */
function usefreedomuser() {    
  if (@include_once('FDL/Lib.Usercard.php')) {
    $usefreedom=(GetParam("USE_FREEDOM_USER")!="no");
    return $usefreedom;
  }
  return false;
}

/**
 * Initialise WHAT : set global $action whithout an authorized user
 * 
 */
function WhatInitialisation() {
  global $action;
  include_once('Class.User.php');
  include_once('Class.Session.php');

  $CoreNull="";
  $core = new Application();
  $core->Set("CORE",$CoreNull);
  $core->session=new Session();
  $action=new Action();
  $action->Set("",$core);

  // i18n
  $lang=$action->Getparam("CORE_LANG");
  setlocale(LC_MESSAGES,$lang);  
  setlocale(LC_MONETARY, $lang);
  setlocale(LC_TIME, $lang);
  bindtextdomain ("what", DEFAULT_PUBDIR."/locale");
  bind_textdomain_codeset("what", 'ISO-8859-15');
  textdomain ("what");
}

?>
