<?php

function getimapfolders($mboxid) {
  $dbaccess=getParam("FREEDOM_DB");
  $mb=new_doc($dbaccess,$mboxid);
  if ($mb->isAlive()) {
    $err=$mb->mb_connection();
    if ($err=="") {
      $list=imap_list($mb->mbox, $mb->fimap, "*");
      //      print_r2($list);
      sort($list);
      foreach ($list as $k=>$fld) {
	$fld=mb_convert_encoding( $fld, "ISO_8859-1", "UTF7-IMAP" ); 
	//print "|$fld]\n<br>";
	//	$fld=$mb->imap_utf7_decode_zero($fld);
	$f=substr($fld,strpos($fld,'}')+1);

	$tr[]=array($f,$f);
      }
    }
  }


  if ($err) return $err;
  return $tr;
  }

?>