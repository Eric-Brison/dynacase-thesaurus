<?php 
/**
 * Install command executing
 *
 * @author Anakeen 2005
 * @version $Id: wgexecute.php,v 1.5 2005/11/24 09:11:18 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package WHAT
 * @subpackage CORE
 */
header('Content-type: text/xml; charset=utf-8'); 
global $_POST;
$number=$_POST["number"];

session_start();
global $_COOKIE;
$sid=$_COOKIE['adminsession'];
session_id($sid);
print '<?xml version="1.0" encoding="UTF-8"?>';

if (!$sid) {
  $code="SESSION ERROR";
} else {
  $actions=$_SESSION["actions"];
  if (isset($actions[$number])) {
    $cmd=$actions[$number];
      if ($cmd[0]!='#') {
        exec ( $cmd , $out ,$err );
	if ($err==0) $code="OK";
	else $code=$err;
	foreach ($out as $k=>$v) $out[$k]=str_replace("","",htmlentities($v));
	$msg=implode("<br/>",$out);
      } else {
	$code="SKIP";
      }
  } else {
   $code="NO CMD";
  }
  print sprintf("<status code=\"%s\" number=\"%s\"><msg><![CDATA[%s]]></msg></status>",$code,$number,$msg);
}
?>
