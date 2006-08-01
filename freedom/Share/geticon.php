<?php
/**
 * Retrieve icon file
 *
 * @author Anakeen 2002
 * @version $Id: geticon.php,v 1.6 2006/08/01 15:31:43 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage 
 */
 /**
 */

include_once("Lib.Http.php");
include_once("Lib.Common.php");

include_once("FDL/exportfile.php");


$vaultid = GetHttpVars("vaultid",0);
$mimetype = GetHttpVars("mimetype","image");

$wdbaccess = getDbAccess();
$dbaccess = getParam("FREEDOM_DB");

$vf = newFreeVaultFile($dbaccess);

  if ($vf -> Retrieve ($vaultid, $info) != "") {    
  } else
    {
      //Header("Location: $url");
      if (( $info->public_access)) {
	Http_DownloadFile($info->path, $info->name, $mimetype);
	
      } else {
	Http_DownloadFile("FREEDOM/Images/doc.gif", "unknow", "image/gif");
      }
    }

?>
