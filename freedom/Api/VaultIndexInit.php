<?php
/**
 * Reinit vault files
 *
 * @author Anakeen 2004
 * @version $Id: VaultIndexInit.php,v 1.3 2006/12/21 17:52:10 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage 
 */
 /**
 */
ini_set("max_execution_time", "36000");


include_once('FDL/Class.Doc.php');
include_once('FDL/Class.DocFam.php');
include_once('FDL/Class.DocVaultIndex.php');
include_once('VAULT/Class.VaultFile.php');


$dbaccess=GetParam("FREEDOM_DB");
if ($dbaccess == "") {
  print "Freedom Database not found : param FREEDOM_DB";
  exit;
}
$o=new DbObj($dbaccess);
$q=new QueryDb($dbaccess,"DocAttr");
$q->AddQuery("type = 'file' or type='image'");
//$q->AddQuery("frameid not in (select id from docattr where type~'array')");
$la=$q->Query(0,0,"TABLE");
if ($q->nb > 0) {
  $o->exec_query("delete from docvaultindex");
 }




foreach ($la as $k=>$v) {
  $docid=$v["docid"];
  $aid=$v["id"];

  $sql="insert into docvaultindex (docid,vaultid) (SELECT id, ltrim(split_part($aid,'|',2),' ')::int from doc$docid where $aid is not null and $aid ~ '^[^\n]*[0-9]$');" ;
  $o->exec_query($sql);
  //print "$sql\n";
  
    $sql2="SELECT vaultreindex(id, $aid) from doc$docid where $aid is not null and $aid ~ '^[^\n]*[0-9]\n.*[0-9]$';" ;
    $o->exec_query($sql2);
    //print "$sql2\n";
  
  
}


$sqlicon="insert into docvaultindex (docid,vaultid) (SELECT id, ltrim(split_part(icon,'|',2),' ')::int from doc where icon is not null and icon ~ '[0-9]$');" ;
$o->exec_query($sqlicon);
//print "$sqlicon\n";

?>
