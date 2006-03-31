<?php
/**
 * Create a new family
 *
 * @author Anakeen 2006
 * @version $Id: createthefam.php,v 1.1 2006/03/31 12:29:30 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage GED
 */
 /**
 */



include_once("FDL/Class.Doc.php");
include_once("FDL/Class.DocAttr.php");
include_once("FDL/Lib.Attr.php");
include_once("FDL/Class.DocFam.php");
include_once("FDL/freedom_util.php");  



// -----------------------------------
function createthefam(&$action) {


  // Get all the params      
  $ftitle=GetHttpVars("ftitle", _("new familly document"));
  
  
  $dbaccess = $action->GetParam("FREEDOM_DB");

  $bdfreedomattr = new DocAttr($dbaccess);
  if ( $docid == 0 )
    {
      $doc = new DocFam($dbaccess);
      //---------------------------
      // add new freedom familly
      //---------------------------
      $doc->title = $ftitle;
      $doc->owner = $action->user->id;
      $doc->locked = $action->user->id; // lock for next modification
      $doc->doctype = 'C'; // it is a new class document
      $doc->fromid = GetHttpVars("classid"); // inherit from
      $doc->profid = "0"; // NO PROFILE ACCESS

      if (GetHttpVars("classid") >0) {
	$cdoc = new_Doc($dbaccess,GetHttpVars("classid") );
	$doc->classname = "";
	$doc->profid = $cdoc->cprofid; // inherit father profile
      }
      $err=$doc-> Add();
      if ($err != "") $action->exitError($err);           
    } 
    

    


  $wsh = getWshCmd();
  $cmd = $wsh . "--userid={$action->user->id} --api=fdl_adoc --docid=".$doc->initid;

  $err= exec($cmd, $out, $ret);

  $doc->unlock(true);
  if ($ret) $action->exitError($err);
       


  
  redirect($action,"FDL","FDL_CARD&id=".$doc->id,
	   $action->GetParam("CORE_STANDURL"));
}




?>
