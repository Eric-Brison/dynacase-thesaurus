<?php
/**
 * Execute Freedom Processes
 *
 * @author Anakeen 2005
 * @version $Id: fdl_execute.php,v 1.4 2005/09/12 16:33:55 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage 
 */
 /**
 */



// refreah for a classname
// use this only if you have changed title attributes

include_once("FDL/Lib.Attr.php");
include_once("FDL/Class.DocFam.php");




$appl = new Application();
$appl->Set("FDL",	   $core);


$dbaccess=$appl->GetParam("FREEDOM_DB");
if ($dbaccess == "") {
  print "Freedom Database not found : param FREEDOM_DB";
  exit;
}



$docid = GetHttpVars("docid",0); // special docid
$comment = base64_decode(GetHttpVars("comment")); // additionnal comment
if (($docid==0) && (! is_numeric($docid)))  $docid   =  getFamIdFromName($dbaccess,$docid);




if ($docid > 0) {
  $doc=new_Doc($dbaccess,$docid);
  if ($doc->locked == -1) { // it is revised document
    $doc=new_Doc($dbaccess,$doc->latestId());
  }
  
  $cmd=$doc->bgCommand($action->user->id==1);
  $f=uniqid("/tmp/fexe");
  $fout="$f.out";
  $ferr="$f.err";
  $cmd.= ">$fout 2>$ferr";
  $m1=microtime();
  system($cmd,$statut);
  $m2=microtime_diff(microtime(),$m1);
  $ms=gmstrftime("%H:%M:%S",$m2);


  if (file_exists($fout)) {
    $doc->setValue("exec_detail",file_get_contents($fout)); 
    unlink($fout);
  }
  if (file_exists($ferr)) {
    $doc->setValue("exec_detaillog",file_get_contents($ferr)); 
    unlink($ferr);
  }

  
  $doc->deleteValue("exec_nextdate");
  $doc->setValue("exec_elapsed",$ms);
  $doc->setValue("exec_date",date("d/m/Y H:i "));
  $doc->setValue("exec_state",(($statut==0)?"OK":$statut));
  $puserid=$doc->getValue("exec_iduser"); // default exec user
  $doc->setValue("exec_iduser",$doc->getExecUserID());
  $doc->refresh();
  $err=$doc->modify();
  if ($err == "") {
    if ($comment != "") $doc->AddComment($comment);
    $err=$doc->AddRevision(sprintf(_("execution by %s done %s"),$doc->getTitle($doc->getExecUserID()),$statut));
    if ($err == "") {
      $doc->deleteValue("exec_elapsed");
      $doc->deleteValue("exec_detail");
      $doc->deleteValue("exec_detaillog");
      $doc->deleteValue("exec_date");
      $doc->deleteValue("exec_state");
      $doc->setValue("exec_iduser",$puserid);
      $doc->refresh();
      $err=$doc->modify();
    }    
  }
  
  
 }

    

?>