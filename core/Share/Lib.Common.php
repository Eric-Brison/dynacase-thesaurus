<?php
/**
 * Common util functions
 *
 * @author Anakeen 2002
 * @version $Id: Lib.Common.php,v 1.30 2006/07/27 16:03:37 eric Exp $
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
	if (!isset($CORE_DBID) || !isset($CORE_DBID["$dbaccess"])) {
           $CORE_DBID["$dbaccess"] = pg_connect("$dbaccess");
        } 
    return $CORE_DBID["$dbaccess"];
}

function getDbAccess() {
  global $CORE_DBANK;;
  global $pubdir;

  if ($CORE_DBANK != "") return $CORE_DBANK;
  $dbaccess="";

  $dbank=getenv("dbanakeen"); // choose when several databases
  
 
  if ($dbank != "") {
    $filename="$pubdir/virtual/$dbank/dbaccess.php";    
    if (file_exists($filename)) {
      include($filename);
    }    
  }
  if ($dbaccess=="") include("dbaccess.php");
  $CORE_DBANK=$dbaccess;
  return $CORE_DBANK;
  
}
 
function getDbEnv() {
   $dbank=getenv("dbanakeen"); // choose when several databases
   if ($dbank == "anakeen") return "";
   return $dbank;
}

/**
 * transform php postgresql connexion syntax for psql syntax connection
 * @param string postgresql string connection (like : dbname=anakeen user=admin)
 * @return string like --username admin --dbname anakeen
 */
function php2DbSql($dbcoord,$withdbname=true) {
    if (ereg('dbname=[ ]*([a-z_0-9]*)',$dbcoord,$reg)) {  
      $dbname=$reg[1];
    }
    if (ereg('host=[ ]*([a-z_0-9\.]*)',$dbcoord,$reg)) {  
      $dbhost=$reg[1];
    }
    if (ereg('port=[ ]*([a-z_0-9]*)',$dbcoord,$reg)) {  
      $dbport=$reg[1];
    }
    if (ereg('user=[ ]*([a-z_0-9]*)',$dbcoord,$reg)) {  
      $dbuser=$reg[1];
    }
    $dbpsql="";
    if ($dbhost != "")  $dbpsql.= "--host $dbhost ";
    if ($dbport != "")  $dbpsql.= "--port $dbport ";
    if ($dbuser != "")  $dbpsql.= "--username $dbuser ";
    if ($withdbname) $dbpsql.= "--dbname $dbname ";
    return $dbpsql;  
}
 
/**
 * transform psql syntax connection syntax to php postgresql connexion
 * @param string psql string connection (like : --dbname=anakeen --username=admin)
 * @return string like user admin dbname anakeen
 */
function DbSql2php($dbcoord,$withdbname=true) {
    if (ereg('--dbname[ ]*([a-z_0-9]*)',$dbcoord,$reg)) {  
      $dbname=$reg[1];
    }
    if (ereg('--host[ ]*([a-z_0-9\.]*)',$dbcoord,$reg)) {  
      $dbhost=$reg[1];
    }
    if (ereg('--port[ ]*([a-z_0-9]*)',$dbcoord,$reg)) {  
      $dbport=$reg[1];
    }
    if (ereg('--username[ ]*([a-z_0-9]*)',$dbcoord,$reg)) {  
      $dbuser=$reg[1];
    }
    $dbpsql="";
    if ($dbhost != "")  $dbpsql.= "host=$dbhost ";
    if ($dbport != "")  $dbpsql.= "port=$dbport ";
    if ($dbuser != "")  $dbpsql.= "user=$dbuser ";
    if ($withdbname) $dbpsql.= "dbname=$dbname ";
    return $dbpsql;  
}
function getDbName($dbaccess) {
  if (ereg("dbname=([a-z]+)",$dbaccess,$reg)) {
    return $reg[1];
  }
}


function getDbUser($dbaccess) {
  if (ereg("user=([a-z]+)",$dbaccess,$reg)) {
    return $reg[1];
  }
}


function getWshCmd($nice=false) {
  $dbank=getenv("dbanakeen"); // choose when several databases
  $wsh="export dbanakeen=$dbank;";
  if ($nice) $wsh.= "nice -n +10 ";
  $wsh.=GetParam("CORE_PUBDIR")."/wsh.php  ";
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

function wbar($reste,$total,$text="",$fbar=false) {
if (!$fbar) $fbar = GetHttpVars("bar"); // for progress bar
 if ($fbar) {
   
      
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

?>
