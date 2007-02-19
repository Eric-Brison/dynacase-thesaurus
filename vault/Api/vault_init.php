<?php
/**
 * Initialisation of the FREEDOM VAULT based on the VAULT/FREEDOM.vault file
 *
 * create all sub-directories where files will be inserted by the VAULT application
 * @author Anakeen 2000 
 * @version $Id: vault_init.php,v 1.1 2007/02/19 16:25:40 marc Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage WSH
 */
 /**
 */
 
include_once("VAULT/Class.VaultFile.php");
include_once("FDL/Lib.Util.php");
   

$dirname=getHttpVars("path","/var/freedom/freedom");
$fsname=getHttpVars("name","FREEDOM");
$size_in_bytes=getHttpVars("size",500*1024*1024); // 500Mb


$dbaccess=$appl->GetParam("FREEDOM_DB");

if (!is_dir($dirname)) {
  if (is_dir(dirname($dirname))) {
    print sprintf(_("create directory %s\n"),$dirname);
    mkdir($dirname."/", VAULT_DMODE);
  }
 }
if (is_dir($dirname)) {
  if (!chown($dirname, HTTP_USER) || !chgrp($dirname, HTTP_USER)) {
    $err=sprintf(_("cannot change owner of %s: aborted\n"),$dirname);     
  }
 } else {  
  $err= sprintf(_("cannot create directory %s\nParent directory must be create before"),$dirname);
 }
if ($err=="") {
  $vf=new VaultFile($dbaccess);


  //  print_r2($vf);
  $q=new QueryDb($dbaccess,"VaultDiskFsStorage"); 
  $q->AddQuery("r_path='".pg_escape_string(trim($dirname))."'");
  $l=$q->Query(0,0,"TABLE");
  if ($q->nb==0) {
    $err=$vf->storage->fs->createArch($size_in_bytes,$dirname,$fsname);
    if ($err != "") $err=sprintf(_("cannot create %s: %s\n"),$dirname,$err);
    else print sprintf(_("vault %s created.\n"),$dirname);
  } else {
    $err= sprintf(_("vault already created %s: aborted\n"),$dirname);    
  }
 }
if ($err) print sprintf(_("ERROR %s\n"),$err); 

?>