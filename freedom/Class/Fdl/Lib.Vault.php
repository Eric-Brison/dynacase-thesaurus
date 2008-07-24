<?php
/**
 * Utilities functions for manipulate files from VAULT
 *
 * @author Anakeen 2007
 * @version $Id: Lib.Vault.php,v 1.23 2008/07/24 16:03:15 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage 
 */
 /**
 */

include_once("VAULT/Class.VaultFile.php");
include_once("VAULT/Class.VaultEngine.php");
include_once("VAULT/Class.VaultDiskStorage.php");

function initVaultAccess() {
  static $FREEDOM_VAULT=false;;
  if (! $FREEDOM_VAULT) {
    include_once("VAULT/Class.VaultFile.php");
    $dbaccess=getParam("FREEDOM_DB");
    $FREEDOM_VAULT= new VaultFile($dbaccess, "FREEDOM");
  }
  return $FREEDOM_VAULT;
}


/**
 * Generate a conversion of a file
 * The result is store in vault itself
 * @param string $engine the convert engine identificator (from VaultEngine Class)
 * @param int $idfile vault file identificator (original file)
 * @param int &$gen_idfile vault identificator of new stored file
 * @return string error message (empty if OK)
 */
function vault_generate($dbaccess,$engine,$vidin,$vidout,$isimage=false) {
  if (($vidin>0)&&($vidout>0))  {
    $tea=getParam("TE_ACTIVATE");
    if ($tea!="yes") return;
    if (@include_once("TE/Class.TEClient.php")) {
      global $action;
      include_once("FDL/Class.TaskRequest.php");
      $of=new VaultDiskStorage($dbaccess,$vidin);
      $filename=$of->getPath();
      $ofout=new VaultDiskStorage($dbaccess,$vidout);
      $ofout->teng_state=3; // in progress
      $ofout->modify();
      $urlindex=getParam("TE_URLINDEX");
      if ($urlindex=="") { //case DAV
	$au=getParam("CORE_URLINDEX");
	if ($au != "") $urlindex=getParam("CORE_URLINDEX");
	else {
	  $scheme=getParam("CORE_ABSURL");
	  if ($scheme=="") $urlindex='/freedom/';
	  else $urlindex=getParam("CORE_ABSURL");
	}
      }
      
      if (strstr($urlindex,'?')) $beg='&';
      else $beg='?';
      $callback=$urlindex.$beg."sole=Y&app=FDL&action=INSERTFILE&engine=$engine&vidin=$vidin&vidout=$vidout&isimage=$isimage";
      $ot=new TransformationEngine(getParam("TE_HOST"),getParam("TE_PORT"));
      $err=$ot->sendTransformation($engine,$vid,$filename,$callback,$info);
      if ($err=="") {
	$tr=new TaskRequest($dbaccess);
	$tr->tid=$info["tid"];
	$tr->fkey=$vidout;
	$tr->status=$info["status"];
	$tr->comment=$info["comment"];
	$tr->uid=$action->user->id;
	$tr->uname=$action->user->firstname." ".$action->user->lastname;
	$err=$tr->Add();
      } else {
	$vf=initVaultAccess();
	$filename= uniqid("/var/tmp/txt-".$vidout.'-');
	file_put_contents($filename,$err);
	//$vf->rename($vidout,"toto.txt");
	$vf->Retrieve($vidout, $info);
	$err=$vf->Save($filename, false , $vidout);
	@unlink($filename);
	$vf->rename($vidout,_("impossible conversion").".txt");
	$vf->storage->teng_state=-2;
	$vf->storage->modify();;
      }
    } else {
      AddWarningMsg(_("TE engine activate but TE-CLIENT not found"));
    }
  }
  return $err;
  
  
}

/**
 * return various informations for a file stored in VAULT 
 * @param int $idfile vault file identificator 
 * @param string $teng_name transformation engine name
 * @return array 
 */
function vault_properties($idfile,$teng_name="") {
  
  $FREEDOM_VAULT=initVaultAccess();  
  $FREEDOM_VAULT->Show($idfile, $info,$teng_name);
  return $info;
}

/**
 * return various informations for a file stored in VAULT 
 * @param string $filename 
 * @param int &$vid return vaul identificator
 * @return string error message 
 */
function vault_store($filename,&$vid,$ftitle="") {
  
  $FREEDOM_VAULT=initVaultAccess();  
  $err=$FREEDOM_VAULT->store($filename,false , $vid);
  if (($err=="") && ($ftitle != "")) $FREEDOM_VAULT->rename($vid,$ftitle);
  return $err;
}
/**
 * return context of a file
 * @param int $idfile vault file identificator 
 * @return array 
 */
function vault_get_content($idfile) {
  $FREEDOM_VAULT=initVaultAccess();
  $v=new VaultDiskStorage($FREEDOM_VAULT->dbaccess,$idfile);

  if ($v->isAffected()) {
    $path=$v->getPath();
    if (file_exists($path)) return file_get_contents($path);
  }
  return false;
}



/**
 * send request to have text conversion of file
 */
function sendLatinTransformation($dbaccess,$docid,$attrid,$index,$vid) {
  if (($docid >0)  && ($vid>0)) {

    $tea=getParam("TE_ACTIVATE");
    if ($tea!="yes") return;
    $tea=getParam("TE_FULLTEXT");
    if ($tea!="yes") return;
    if (@include_once("TE/Class.TEClient.php")) {
      global $action;
      include_once("FDL/Class.TaskRequest.php");
      $of=new VaultDiskStorage($dbaccess,$vid);
      $filename=$of->getPath();
      //      error_log("sendLatinTransformation $filename");
      
      $urlindex=getParam("TE_URLINDEX");
      if ($urlindex=="") { //case DAV
	$au=getParam("CORE_URLINDEX");
	if ($au != "") $urlindex=getParam("CORE_URLINDEX");
	else {
	  $scheme=getParam("CORE_ABSURL");
	  if ($scheme=="") $urlindex='/freedom/';
	  else $urlindex=getParam("CORE_ABSURL");
	}
      }

      if (strstr($urlindex,'?')) $beg='&';
      else $beg='?';
      $callback=$urlindex.$beg."sole=Y&app=FDL&action=SETTXTFILE&docid=$docid&attrid=".$attrid."&index=$index";
      $ot=new TransformationEngine(getParam("TE_HOST"),getParam("TE_PORT"));
      $err=$ot->sendTransformation('latin',$vid,$filename,$callback,$info);
      if ($err=="") {
	$tr=new TaskRequest($dbaccess);
	$tr->tid=$info["tid"];
	$tr->fkey=$vid;
	$tr->status=$info["status"];
	$tr->comment=$info["comment"];
	$tr->uid=$action->user->id;
	$tr->uname=$action->user->firstname." ".$action->user->lastname;
	$err=$tr->Add();
      }
    } else {
      AddWarningMsg(_("TE engine activate but TE-CLIENT not found"));
    }
  }
  return $err;
}

/**
 * send request to convert and waiting 
 * @param string  $infile path to file to convert
 * @param string  $engine engine name to use
 * @param string  $outfile path where to store new file
 * @param array &$info various informations for convertion process
 * @return string error message 
 */
function convertFile($infile,$engine,$outfile,&$info) {
  global $action;
  if (file_exists($infile)  && ($engine!="")) {
    $tea=getParam("TE_ACTIVATE");
    if ($tea!="yes") return _("TE not activated");
    
    if (@include_once("TE/Class.TEClient.php")) {
      
      $callback="";
      $ot=new TransformationEngine(getParam("TE_HOST"),getParam("TE_PORT"));
      $err=$ot->sendTransformation($engine,$vid,$infile,$callback,$info);
      if ($err=="") {
	include_once("FDL/Class.TaskRequest.php");
	$dbaccess = GetParam("FREEDOM_DB");
	$tr=new TaskRequest($dbaccess);
	$tr->tid=$info["tid"];
	$tr->fkey=$vid;
	$tr->status=$info["status"];
	$tr->comment=$info["comment"];
	$tr->uid=$action->user->id;
	$tr->uname=$action->user->firstname." ".$action->user->lastname;
	$err=$tr->Add();
      }
      if ($err=="") {
	$tid=$info["tid"];
	if ($tid == 0) $err=_("no task identificator");
      }
      // waiting response
      if ($err=="") {
	$status="";
	ini_set("max_execution_time", "3600"); // may be long 
	while (($status != 'K') && ($status != 'D') && ($err=="")) {
	  $err=$ot->getInfo($tid,$info);
	  $status=$info["status"];
	  if ($err=="") {
	    switch($info["status"]) {
	    case  'P':
	      $statusmsg=_("File:: Processing");
	      break;
	    case  'W':
	      $statusmsg=_("File:: Waiting");
	      break;
	    case  'D':
	      $statusmsg=_("File:: converted");
	      break;
	    case  'K':
	      $statusmsg=_("File:: failed");
	      break;
	    default:
	      $statusmsg=$info["status"];	
	    }
	  }
	  
	  sleep(2);
	}
	if (($err=="") && ($status=='D')) {
	  include_once("FDL/insertfile.php");
	  $err=getTEFile($tid,$outfile,$info);
	}
      }
    }
  } else {
    $err= sprintf(_("file %s not found"),$infile);
  }
  return $err;
}

?>