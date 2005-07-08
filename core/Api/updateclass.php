<?php
/**
 * Update the SQL structure of a table of a DbObj Object
 *
 * @param string $appc the application directory (WHAT, FDL, ...)
 * @param string $class the class name of the DbObj Class
 * @param string $dbname the SQL database name (anakeen, freedom)
 * @author Anakeen 2002
 * @version $Id: updateclass.php,v 1.10 2005/07/08 15:29:51 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package WHAT
 * @subpackage CORE
 */
/**
 */


$pubdir="/home/httpd/what";
ini_set("include_path", ".:/home/httpd/what:/home/httpd/what/WHAT:/usr/share/pear");

function GetArg($name, $def="") {

  global $ARGS, $argv;;

  static $first=true;;

  if ($first) {

    while (list($k, $v) = each($argv)) {
  
      if (ereg("--(.+)=(.+)", $v , $reg)) {
	$ARGS[$reg[1]]=$reg[2];
      }  else if (ereg("--(.+)", $v , $reg)) {
    
	$ARGS[$reg[1]]=true;
      } 
    }
    $first=false;
  }
  
  
  if (isset($ARGS[$name])) return ($ARGS[$name]); // 
    return($def);
}

global $_SERVER;

if ($_SERVER['HTTP_HOST'] != "")     {
  print "<BR><H1>:~(</H1>";
  exit;
}


$appclass = Getarg("appc","WHAT");
$class = Getarg("class");
$dbname = Getarg("dbname");


include("$pubdir/dbaccess.php");
if ($dbname != "")   $db = ereg_replace("dbname=([^ ]+)","dbname=$dbname", $dbaccess);
else {
  include_once("Lib.Common.php");
  $db = getDbAccess();
}
include_once("$pubdir/$appclass/Class.$class.php");


$o= new $class($db);

$dbid=pg_connect($db);
if (! $dbid) {
  print _("cannot access to  database $db\n");
  exit(1);
} else print _("access granted to  database $db\n");

$sql=array();
$rq=@pg_query ($dbid, "select * from ".$o->dbtable." LIMIT 1;");
if (!$rq) {
  // table not exist : just create
  $o->Create();
  // $sqlcmds = explode(";",$o->sqlcreate);
//   while (list($k,$sqlquery)=each($sqlcmds)) {
//     if (chop($sqlquery) != "")
//       $sql[]=$sqlquery;
  // }
} else {
  $row=0;


  if (pg_result_error($rq) == "") {
    if (pg_num_rows($rq) > 0 ) {
      $row= pg_fetch_array($rq,0,PGSQL_ASSOC);
      if ($row) {
	$fieds = array_intersect($o->fields,array_keys($row));
	$sql[]= "CREATE TABLE ".$o->dbtable."_old AS SELECT * FROM ".$o->dbtable.";";
      }
    }
    $sql[]= "DROP TABLE ".$o->dbtable.";";
  }
  $sqlcmds = explode(";",$o->sqlcreate);
  while (list($k,$sqlquery)=each($sqlcmds)) {
    if (chop($sqlquery) != "")
      $sql[]=$sqlquery;
  }

  if ($row) {
    $sql[]= "INSERT INTO ".$o->dbtable." (".implode(",", $fieds).") SELECT ".implode(",", $fieds). " FROM ".$o->dbtable."_old";
    
    $sql[]= "DROP TABLE ".$o->dbtable."_old;";
  }
}
while (list($k,$v) = each ($sql)) {
  print "Sql:$v\n";
  $rq=@pg_exec ($dbid, $v);
  if (! $rq) {
    if (ereg("create sequence",$v, $reg)) {
      $pgmess = pg_errormessage($dbid);
      echo "[1;33;49m".$pgmess."[0m\n";

    } else {
      $pgmess = pg_errormessage($dbid);
      echo "[1;31;49m".$pgmess."[0m\n";
      echo "[1;31;40m"."ABORTED"."[0m\n";
      break;
    }
    
  }
}

pg_close($dbid);
?>
