<?php
/**
 * progress bar tool
 *
 * @author Anakeen 2000 
 * @version $Id: progressbar.php,v 1.1 2004/08/23 13:48:25 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package WHAT
 * @subpackage CORE
 */
 /**
 */

function progressbar(&$action) {
  $fbar = GetHttpVars("bar"); 

  
  
   $action->lay->set("bar",$fbar);
}

function progressbar2(&$action) {
  $fbar = GetHttpVars("bar"); 

   if ($fbar) {
     $rf=file($fbar);
     // if (count($rf) > 0) {
	$ffbar=fopen("$fbar.lck","w");
	if ($ffbar) fclose($ffbar);
	//}
   }
   $texts=array();
   $reste=0;
   $total=0;
   foreach ($rf as $line) {
     if (ereg("([0-9\-]+)/([0-9\-]+)/(.*)",$line,$reg)) {
       $texts[]=addslashes(trim($reg[3]));
       if ((intval($reg[2]) > 0) || (intval($reg[1]) > 0)){
	 $reste=intval($reg[1]);
	 $total=intval($reg[2]);
       }
     }
   }
   // $action->lay->set("text",trim(addslashes($text)));
   $action->lay->set("texts","['".implode("','",($texts))."']");
   $action->lay->set("reste",$reste);
   $action->lay->set("done",$total-$reste);
   $action->lay->set("total",$total);
   if ($total > 0) {
     $action->lay->set("pc",round(($total-$reste)/$total*100)."%"); 
     $action->lay->set("pc1",round(($total-$reste)/$total*100));
   }
   else $action->lay->set("pc","100%");
}
?>