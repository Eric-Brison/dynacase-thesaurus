<?php
/**
 * View PDF document manuals
 *
 * @author Anakeen 2004
 * @version $Id: docpdf.php,v 1.2 2004/10/11 15:40:27 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package WHAT
 * @subpackage CORE
 */
 /**
 */




// -----------------------------------
function docpdf(&$action) {
  // -----------------------------------

  $pubdir=$action->GetParam("CORE_PUBDIR");
  $pubdir.="/Docs/";
  
if ($handle = opendir($pubdir)) {

   /* Ceci est la fa�on correcte de traverser un dossier. */
   $tpdfdoc=array();
   while (false !== ($file = readdir($handle))) {
     if (($file != "") && ($file[0]!= ".")) {
       $info=`strings $pubdir/$file | grep -A 10 "/Title"`;

       $title=$file;
       $subject="";
       $cdate="";
	if (ereg("/Title <([^>]*)>",$info,$reg)) {
	  $title=String2utf8($reg[1]);
	}
	if (ereg("/Subject <([^>]*)>",$info,$reg)) {
	  $subject=String2utf8($reg[1]);
	}

	if (ereg("/CreationDate \(([^\)]*)\)",$info,$reg)) {
	 
	  // like D:20040824122100
	 
	  $cdate=strftime("%d/%m/%Y",mktime(0,0,0,substr($reg[1],6,2),substr($reg[1],8,2),substr($reg[1],2,4)));
	}
       $tpdfdoc[]=array("url"=>"Docs/$file",
			"filename"=>$file,
			"subject"=>$subject,
			"cdate"=>$cdate,
			"title"=>$title);
     }
   }

   
   closedir($handle);
}
 $action->lay->setBlockData("PDFDOC",$tpdfdoc);
}


function String2utf8($s) {
  $r="";
  for ($i=4;$i<strlen($s);$i+=2) {
    
    if ($s[$i].$s[$i+1]!="00")
      $r.=chr(hexdec($s[$i].$s[$i+1]));
  }
  return ($r);
  return utf8_decode($r);
}