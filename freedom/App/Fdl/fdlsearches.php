<?php
/**
 * function use for specialised searches
 *
 * @author Anakeen 2006
 * @version $Id: fdlsearches.php,v 1.3 2007/08/01 14:08:09 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage 
 */
 /**
 */
function mytagdoc($start,$slice,$tag,$userid=0) {
  include_once("FDL/Class.DocUTag.php");
  include_once("FDL/Lib.Dir.php");
  $dbaccess=getParam("FREEDOM_DB");
  if ($userid==0) $uid=getUserId();
  else $uid=$userid;
  $q=new QueryDb($dbaccess,"DocUTag");
  $q->AddQuery("uid=$uid");
  $q->AddQuery("tag='$tag'");
  $lq=$q->Query(0,1000,"TABLE");
  $lid=array();
  if ($q->nb > 0) {
    foreach($lq as $k=>$v) {
      $lid[$v["initid"]]=$v["id"];
    }
  }

  
  //print Doc::getTimeDate(0,true);
  $ltdoc=getDocsFromIds($dbaccess,$lid);
  // print "\nc=".count($ltdoc)."\n";
  //print Doc::getTimeDate(0,true);
  //  print_r2($ltdoc);

  return $ltdoc;

}


/**
 * function use for specialised search
 * return all document tagged TOVIEWDOC for current user
 * 
 * @param int $start start cursor
 * @param int $slice offset ("ALL" means no limit)
 * @param int $userid user system identificator (NOT USE in this function)
 */
function mytoviewdoc($start="0", $slice="ALL",$userid=0) {
  return mytagdoc($start,$slice,"TOVIEW");
}


/**
 * function use for specialised search
 * return all document tagged  for current user
 * 
 * @param int $start start cursor
 * @param int $slice offset ("ALL" means no limit)
 * @param int $userid user system identificator (NOT USE in this function)
 */
function myaffecteddoc($start="0", $slice="ALL",$userid=0) {
  return mytagdoc($start,$slice,"AFFECTED");
}
/**
 * function use for specialised search
 * return all referenced documents
 * 
 * @param int $start start cursor
 * @param int $slice offset ("ALL" means no limit)
 * @param int $userid user system identificator (NOT USE in this function)
 * @param int $docid document referenced
 * @param int $famid family restriction (0 if no restriction)
 */
function relateddoc($start="0", $slice="ALL",$userid=0,$docid=0,$famid=0) {
  
  $dbaccess=getParam("FREEDOM_DB");
  if ($docid>0) {
    include_once("FDL/Class.DocRel.php");
    $lid=array();
    $doc = new_Doc($dbaccess, $docid);
    $idocid=$doc->initid;
    $rdoc=new DocRel($dbaccess,$idocid);
    $rdoc->sinitid=$idocid;
    $trel=$rdoc->getIRelations();

    foreach ($trel as $k=>$v) {
      $lid[$v["sinitid"]]=$v["sinitid"];
      $tlay[$v["sinitid"].'_F']=array("iconsrc"=>$doc->getIcon($v["sicon"]),
				      "initid"=>$v["sinitid"],
				      "title"=>$v["stitle"],
				      "aid"=>$v["type"],
				      "alabel"=>_($v["type"]),
				      "type"=>_("Referenced from"));
    }
    $ltdoc=getLatestDocsFromIds($dbaccess,$lid);
    if ($famid!=0) {
      if (! is_numeric($famid)) $famid=getFamIdFromName($dbaccess,$famid);
      if ($famid > 0) {
	$tfam=$doc->GetChildFam($famid);
	$tfamids=array_keys($tfam);
	$tfamids[]=$famid;

	foreach ($ltdoc as $k=>$v) {
	  if (!in_array($v["fromid"],$tfamids)) unset($ltdoc[$k]);
	}
      }
    }
  }
  return $ltdoc;
}
?>