<?php
/**
 * View imported tar
 *
 * @author Anakeen 2004
 * @version $Id: freedom_ana_tar.php,v 1.4 2005/02/08 11:34:37 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage GED
 */
 /**
 */


include_once("FREEDOM/freedom_import_tar.php");






function freedom_ana_tar(&$action) {

  global $_FILES;
 
  $analyze = GetHttpVars("analyze","Y"); // just analyze
  $filename = GetHttpVars("filename"); // the select filename

  $ldir=getTarUploadDir($action);
  if ($handle = opendir($ldir)) {
    while (false !== ($file = readdir($handle))) {
      if ($file[0]!=".") {
	$ttar[]=array("filename"=>$file,
		      "selected"=>($file==$filename)?"selected":"");
	if ($file==$filename) {
	  $selfile=$file;
	}
      }
      
    }
  }
  if ($selfile=="") {
    // try the first
    $ttar[0]["selected"]="selected";
    $selfile=$ttar[0]["filename"];    
  }


  
     
  
  $nbdoc=analyze_tar($action,$selfile);
  
  if ($analyze=="Y") $action->lay->setBlockData("AFORM", array(array("zou")));

  $action->lay->set("huge",($action->Read("navigator","")=="EXPLORER")?"":"huge");

  $action->lay->Set("nbdoc","$nbdoc");
  $action->lay->Set("importtext",sprintf(_("proceed background import of %d documents"),$nbdoc));
}




function analyze_tar(&$action,$selfile) {
  $dirid = GetHttpVars("dirid"); // directory to place imported doc 
  $famid = GetHttpVars("famid",7); // default import family
  $dfldid = GetHttpVars("dfldid",2); // default import folder family
  $onlycsv = (GetHttpVars("onlycsv") != ""); // only files described in fdl.csv files
  $topfld = (GetHttpVars("topfld") != ""); // add a root folder
  $analyze = (GetHttpVars("analyze","Y")=="Y"); // just analyze
  $filename = GetHttpVars("filename"); // the select filename

  $dbaccess = $action->GetParam("FREEDOM_DB");
  $selectclass=array();

  $doc = new Doc($dbaccess, $classid);
  $tclassdoc = GetClassesDoc($dbaccess, $action->user->id,0,"TABLE");

  foreach ($tclassdoc as $k=>$cdoc) {
    $selectclass[$k]["idcdoc"]=$cdoc["initid"];
    $selectclass[$k]["classname"]=$cdoc["title"];
    if ($cdoc["initid"] == $famid) $selectclass[$k]["selected"]="selected";
    else $selectclass[$k]["selected"]="";
  }
  $action->lay->SetBlockData("SELECTCLASS", $selectclass);
  
  $tclassdoc = GetClassesDoc($dbaccess, $action->user->id,2,"TABLE");
  foreach ($tclassdoc as $k=>$cdoc) {
      $selectfld[$k]["idcdoc"]=$cdoc["initid"];
      $selectfld[$k]["classname"]=$cdoc["title"];
      if ($cdoc["initid"] == $dfldid) $selectfld[$k]["selected"]="selected";
      else $selectfld[$k]["selected"]="";    
  }

  $action->lay->SetBlockData("SELECTDFLD", $selectfld);


  $untardir= getTarExtractDir($action,$selfile);
  
  $dir = new Doc($dbaccess,$dirid);
  $dirtitle=$dir->title;
  if (! method_exists($dir,"addfile")) {
    $action->AddWarningMsg(sprintf(_("The document <%s> is not a folder"),$dirtitle));
    $dir=createDoc($dbaccess,"DIR");
    $dir=$dir->getHome();
    $dirtitle=$dir->title;
    $dirid=$dir->id;
    $action->AddWarningMsg(sprintf(_("Use your home folder instead <%s>"),$dirtitle));
  }
  if ($topfld) {
      if ($analyze) {
	$dirtitle=$dirtitle."/".$filename;
      } else {
      // add a father
      $pdir = createDoc($dbaccess,"DIR");
      $pdir->setValue("BA_TITLE",$filename);
      $err=$pdir->Add();
      if ($err!="") $action->exitError($err);
      $err=$dir->AddFile($pdir->id);
      if ($err!="") $action->exitError($err);
      $dirid=$pdir->id;
      $dirtitle=$dirtitle."/".$pdir->title;
      
    }
  }

  $tr=import_directory($action,$untardir,$dirid,$famid,$dfldid,$onlycsv,$analyze);
  if ($tr) {
  foreach ($tr as $k=>$v) {
    if ($v["familyid"]>0) {
      $f=new Doc($dbaccess,$v["familyid"]);
      $tr[$k]["familyname"]=$f->title;
      $tr[$k]["foldername"]=$dirtitle.'/'.substr($v["foldername"],strposn($v["foldername"],'/',6));
      
    }
  }
  }

  $action->lay->Set("dirid", $dirid);
  $action->lay->SetBlockData("ADDEDDOC",$tr);
  $action->lay->Set("selfile",stripslashes($selfile));
  $action->lay->Set("oselected",$onlycsv?"checked":"");
  $action->lay->Set("tselected",$topfld?"checked":"");
  $action->lay->Set("fdisabled",$onlycsv?"disabled":"");
  $action->lay->Set("mailaddr",getMailAddr($action->user->id));
  $action->lay->Set("bgdisabled",(count($tr)>0)?"":"disabled");

  return count($tr);
}

/**
 * get position of $i th character $c  in $s string
 */
function strposn($s,$c, $i) {
  $p=0; // begin
  for ($j=0;$j<$i;$j++) {
    $p=strpos($s,$c,$p);
    $p++;
  }
  if ($p==0) $p=strlen($s);
  return $p;
}
?>
