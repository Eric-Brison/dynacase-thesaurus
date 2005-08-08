<?php
/**
 * Generated Header (not documented yet)
 *
 * @author Anakeen 2000 
 * @version $Id: freedom_convert.php,v 1.4 2005/08/08 16:00:54 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage 
 */
 /**
 */



  // refreah for a classname
  // use this only if you have changed title attributes

include_once("FDL/Class.Doc.php");

$famId = GetHttpVars("tofamid",""); // familly filter
$docid = GetHttpVars("docid",""); // document


if (($docid == "") && ($famId == 0)) {
  print "arg class needed :usage --tofamid=<family id> --docid=<doc id to be converted>\n";
  
 } else {


  $appl = new Application();
  $appl->Set("FDL",	   $core);


  $dbaccess=$appl->GetParam("FREEDOM_DB");
  if ($dbaccess == "") {
    print "Freedom Database not found : param FREEDOM_DB";
    exit;
  }


  $doc= new_Doc($dbaccess, $docid);
  if ($doc->isAffected()) {
    if ($doc->convert($famId))  print $doc->title. " converted";
    else print $doc->title. " NOT converted";
  } else {    
    print "document  $docid not found";
  }
 }

?>