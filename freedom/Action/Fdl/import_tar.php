<?php
/**
 * Import Set of documents and files with directories
 *
 * @author Anakeen 2000 
 * @version $Id: import_tar.php,v 1.5 2006/01/16 16:11:39 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage 
 */
 /**
 */




include_once("FDL/import_file.php");

define("TARUPLOAD","/tmp/upload/");
define("TAREXTRACT","/extract/");
define("TARTARS","/tars/");


function getTarUploadDir(&$action) {
  $dtar = $action->getParam("FREEDOM_UPLOADDIR");
  if ($dtar=="") $dtar=TARUPLOAD;
  return $dtar."/".$action->user->login.TARTARS;
}
function getTarExtractDir(&$action,$tar) {
  $dtar = $action->getParam("FREEDOM_UPLOADDIR");
  if ($dtar=="") $dtar=TARUPLOAD;
  return $dtar."/".$action->user->login.TAREXTRACT.$tar."_D";
}


/**
 * import a directory files
 * @param action $action current action
 * @param string $ftar tar file
 */
function import_tar(&$action,$ftar,$dirid=0,$famid=7) {


}

/**
 * import a directory files
 * @param action $action current action
 * @param string $ldir local directory path
 */
function import_directory(&$action, $ldir,$dirid=0,$famid=7,$dfldid=2,
			  $onlycsv=false,$analyze=false) {
  // first see if fdl.csv file
  global $importedFiles;
  
  $dbaccess = $action->GetParam("FREEDOM_DB");

  if (is_dir($ldir)) {
    if ($handle = opendir($ldir)) {
      $lfamid=0;
      while (false !== ($file = readdir($handle))) {
	$absfile="$ldir/$file";
	$absfile=str_replace("//","/","$ldir/$file");
     
	if (is_file($absfile) && ($file=="fdl.csv")) {
	  $tr = analyze_csv($absfile,$dbaccess,$dirid,$lfamid,$lfldid,$analyze);
	
	}
      }
      if ($lfamid > 0) $famid=$lfamid; // set local default family identificator
      if ($lfldid > 0) $dfldid=$lfldid; // set local default family folder identificator

      rewinddir($handle);
   
      /* This is the correct way to loop over the directory. */
      $defaultdoc= createDoc($dbaccess,$famid);
      if (! $defaultdoc) $action->AddWarningMsg(sprintf(_("you cannot create this kind [%s] of document"),$famid));
      if (($lfamid == 0) && ($famid==7)) {
	$defaultimg= createDoc($dbaccess,"IMAGE");
	$fimgattr=$defaultimg->GetFirstFileAttributes();
      }
      $newdir= createDoc($dbaccess,$dfldid);
      if (! $newdir) $action->AddWarningMsg(sprintf(_("you cannot create this kind [%s] of folder"),$dfldid));
      $ffileattr=$defaultdoc->GetFirstFileAttributes();
  
      if ($dirid > 0) {
	$dir = new_Doc($dbaccess,$dirid);
      }

      $nfile=0;
      while (false !== ($file = readdir($handle))) {
	$nfile++;
	$absfile=str_replace("//","/","$ldir/$file");
	$level = substr_count( $absfile,"/");
	$index="f$level/$nfile";
	if (is_file($absfile)) {
	  if (!$onlycsv) { // add also unmarked files
	  
	    if (!isset($importedFiles[$absfile])) {
	      $tr[$index]=array("err"=>($defaultdoc)?"":sprintf(_("you cannot create this kind [%s] of document"),$famid),
				"folderid"=>0,
				"foldername"=>$ldir,
				"filename"=>$file,
				"title"=>"",
				"id"=>0,
				"anaclass"=>"fileclass",
				"familyid"=>$ddoc->fromid,
				"familyname"=>"",
				"action"=>"");
	      $err=AddVaultFile($dbaccess,$absfile,$analyze,$vfid);
      
	      if ($err != "") {
		$tr[$index]["err"]=$err;
	      } else {
		if (($lfamid == 0) && ($famid==7) && (substr($vfid,0,5)=="image")){
		  $ddoc=&$defaultimg;
		  $fattr=$fimgattr->id;
		} else {
		  $ddoc=&$defaultdoc;
		  $fattr=$ffileattr->id;
		}
		$tr[$index]["familyid"]=$ddoc->fromid;
		$tr[$index]["action"]=_("to be add");
		if (! $analyze) {
		  $ddoc->Init();
		  $ddoc->setValue($fattr,$vfid);
		  $err=$ddoc->Add();
		  if ($err!="") {
		    $tr[$index]["action"]=_("not added");
		  } else {
		    $tr[$index]["action"]=_("added");
		    $tr[$index]["id"]=$ddoc->id;
		    $ddoc->PostModify();
		    $ddoc->Modify();
		    if ($dirid > 0) {
		      $dir->AddFile($ddoc->id);
		    }
		  }
		}
	      }
	    }
	  }
	} else if (is_dir($absfile) && ($file[0]!='.')) {
	  $tr[$index]=array("err"=>($newdir)?"":sprintf(_("you cannot create this kind [%s] of folder"),$dfldid),
			    "folderid"=>0,
			    "foldername"=>$ldir,
			    "filename"=>$file,
			    "title"=>"",
			    "id"=>0,
			    "anaclass"=>"fldclass",
			    "familyid"=>$newdir->fromid,
			    "familyname"=>"",
			    "action"=>_("to be add"));
	  if (! $analyze) {
	    $newdir->Init();
	    $newdir->setTitle($file);
	    $err=$newdir->Add();
	    if ($err!="") {
	      $tr[$index]["action"]=_("not added");
	    } else {
	      $tr[$index]["action"]=_("added");
	      if ($dirid > 0) {
		$dir->AddFile($newdir->id);	 
	      }
	    }
	  }
	  $itr=import_directory($action, $absfile,$newdir->id,$famid,$dfldid,$onlycsv,$analyze);
	  $tr=array_merge($tr,$itr);
	}
      }

   

      closedir($handle);
      return $tr;
  
    } 
  }  else {
    $err = sprintf("cannot open local directory %s",$ldir);
    return array("err"=>$err);
  }
}

function analyze_csv($fdlcsv,$dbaccess,$dirid,&$famid,&$dfldid,$analyze) {
  $tr=array();
  $fcsv=fopen($fdlcsv,"r");
  if ($fcsv) {
    $ldir=dirname($fdlcsv);
    while ($data = fgetcsv ($fcsv, 2000, ";")) {
      $nline++;
      $level = substr_count( $ldir,"/");
      $index="c$level/$nline";
      switch ($data[0]) {
	// -----------------------------------
      case "DFAMID":
	$famid =  $data[1];
	//print "\n\n change famid to $famid\n";
	break; 
	// -----------------------------------
      case "DFLDID":
	$dfldid =  $data[1];
	//print "\n\n change dfldid to $dfldid\n";
	break; 
      case "ORDER":  
	if (is_numeric($data[1]))   $orfromid = $data[1];
	else $orfromid = getFamIdFromName($dbaccess,$data[1]);
      
	$tcolorder[$orfromid]=getOrder($data);
	$tr[$index]["action"]=sprintf(_("new column order %s"),implode(" - ",$tcolorder[$orfromid]));
      
      break;
      case "DOC":
	if (is_numeric($data[1]))   $fromid = $data[1];
	else $fromid = getFamIdFromName($dbaccess,$data[1]);
	$tr[$index]=csvAddDoc($dbaccess, $data, $dirid,$analyze,$ldir,"update",
			      array("title"),array(),$tcolorder[$fromid]);
	if ($tr[$index]["err"]=="") $nbdoc++;
	if ($tr[$index]["action"]!="") $tr[$index]["action"]=_($tr[$index]["action"]);
	 
	break;    
      }
    }
    fclose($fcsv);
  }
  return $tr;
}
/**
 * decode characters wihich comes from windows zip
 * @param $s string to decode
 * @return string decoded string
 */
function WNGBdecode($s) {
  $td=array(144=>"�",
	    130=>"�",
	    133=>"�",
	    135=>"�",
	    138=>"�",
	    151=>"�",
	    212=>"�",
	    210=>"�",
	    128=>"�",
	    183=>"�",
	    136=>"�",
	    183=>"�",
	    136=>"�",
	    150=>"�",
	    147=>"�",
	    137=>"�",
	    139=>"�");

  $s2=$s;
  for ($i=0;$i<strlen($s);$i++) {
    if (isset($td[ord($s[$i])]))  $s2[$i]=$td[ord($s[$i])];
      
  }
  return $s2;
}

/**
 * rename file name which comes from windows zip
 * @param $ldir directory to decode
 * @return void
 */
function WNGBDirRename($ldir) {
  $handle=opendir($ldir);
  while (false !== ($file = readdir($handle))) {
   if ($file[0] != ".") {
     $afile="$ldir/$file";

     if (is_file($afile)) {
       rename($afile,"$ldir/".WNGBdecode($file));
     } else if (is_dir($afile)) {
       WNGBDirRename($afile);
     }
   }
 }
 
 closedir($handle);
 rename($ldir,WNGBdecode($ldir)); 
}
?>
