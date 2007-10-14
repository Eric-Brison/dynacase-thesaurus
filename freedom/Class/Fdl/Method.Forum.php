<?php
/**
 * Image document
 *
 * @author Anakeen 2000 
 * @version $Id: Method.Forum.php,v 1.5 2007/10/14 08:54:41 marc Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage 
 */
 /**
 */

var $defaultview= "FDL:FORUM_VIEW:T";

function getEntryId() {
  $dids = $this->getTValue("forum_d_id");
  $max = 0;
  foreach ($dids as $k => $v) $max = ($v > $max ? $v : $max );
  $max++;
  return $max;
}


function forum_view() {
  global $action;

  setHttpVar("fid", $this->id);
  $start = GetHttpVars("start", -1);
  $this->lay->set("viewall", ($start==-1 ? true: false));

  $action->parent->AddCssRef("FDL:forum.css", true);
  $action->parent->AddJsRef("FDL:forum.js", true);
  $action->parent->AddJsRef($action->GetParam("CORE_PUBURL")."/FDC/Layout/inserthtml.js");

  $entries = $this->getentries();
  foreach ($entries as $k => $v) {
    if (($start==-1 && $v["prev"]==-1) || ($start!=-1 && $start==$v["id"])) {
      $el[] = array("fid"=>$this->id, "eid"=>$v["id"]);
    }
  }
  $this->lay->setBlockData("entry_list", $el);
  $this->lay->set("title", $this->getTitle());
  $this->lay->set("closed", false);
  $this->lay->set("docid", $this->getValue("forum_docid"));
  return;
}


function removeentry($eid) {

  $le = array($eid);
  $this->getsubentry($eid, $le);

  $t_id     = $this->getTValue("forum_d_id"); 
  $t_lid    = $this->getTValue("forum_d_link");
  $t_userid = $this->getTValue("forum_d_userid");
  $t_user   = $this->getTValue("forum_d_user");
  $t_mail   = $this->getTValue("forum_d_usermail");
  $t_text   = $this->getTValue("forum_d_text");
  $t_flag   = $this->getTValue("forum_d_flag");
  $t_date   = $this->getTValue("forum_d_date");

  $this->deleteValue("forum_d_id"); 
  $this->deleteValue("forum_d_link");
  $this->deleteValue("forum_d_userid");
  $this->deleteValue("forum_d_user");
  $this->deleteValue("forum_d_usermail");
  $this->deleteValue("forum_d_text");
  $this->deleteValue("forum_d_flag");
  $this->deleteValue("forum_d_date");

  $nt_id     = array();
  $nt_lid    = array();
  $nt_userid = array();
  $nt_user   = array();
  $nt_mail   = array();
  $nt_text   = array();
  $nt_flag   = array();
  $nt_date   = array();

//   print_r2($le);

  $nc = 0;
  foreach ($t_id as $k => $v) {
    if (!in_array($v, $le)) {
//       echo "je garde $v <br>";
      $nt_id[$nc]     = $t_id[$k];
      $nt_lid[$nc]    = $t_lid[$k];
      $nt_userid[$nc] = $t_userid[$k];
      $nt_user[$nc]   = $t_user[$k];
      $nt_mail[$nc]   = $t_mail[$k];
      $nt_text[$nc]   = $t_text[$k];
      $nt_flag[$nc]   = $t_flag[$k];
      $nt_date[$nc]   = $t_date[$k];
      $nc++;
    } else {
//       echo " --------> je supprime $v <br>";
    }
  }
 
  $this->setValue("forum_d_id", $nt_id);
  $this->setValue("forum_d_link", $nt_lid);
  $this->setValue("forum_d_userid", $nt_userid);
  $this->setValue("forum_d_user", $nt_user);
  $this->setValue("forum_d_usermail", $nt_usermail);
  $this->setValue("forum_d_text", $nt_text);
  $this->setValue("forum_d_flag", $nt_flag);
  $this->setValue("forum_d_date", $nt_date);

  $err = $this->modify(true, array("forum_d_id","forum_d_link","forum_d_userid","forum_d_user",
				   "forum_d_usermail","forum_d_text","forum_d_flag","forum_d_date"));
//   echo "$err";

  return;
}

function getsubentry($top, &$le, $level="") {

  $level .= "-";
  $lentries = $this->getentries();
  foreach($lentries[$top]["next"] as $k => $v) {
//     echo $level." ".$v."  (".$lentries[$v]["prev"].")<br>";
    $le[count($le)] = $v;
    $this->getsubentry($v, $le, $level);
  }
  return;
}

function getentries() {

  static $elist = false;

  if ($elist!==false) return $elist;

  $elist = array();
  
  $docid     = $this->getValue("forum_docid"); 
  $t_id     = $this->getTValue("forum_d_id"); 
  $t_lid    = $this->getTValue("forum_d_link");
  $t_userid = $this->getTValue("forum_d_userid");
  $t_user   = $this->getTValue("forum_d_user");
  $t_mail   = $this->getTValue("forum_d_usermail");
  $t_text   = $this->getTValue("forum_d_text");
  $t_flag   = $this->getTValue("forum_d_flag");
  $t_date   = $this->getTValue("forum_d_date");

  $fclosed = false;
  foreach ($t_id as $k => $v) {
    
    $next = array();
    $prev = -1;

    foreach ($t_id as $ki => $vi) {
      if ($t_lid[$ki] == $v) $next[] = $vi;
      if ($vi == $t_lid[$k]) $prev = $vi;
    }

    $elist[$v] = array( "id" => $v,
			"docid" => $docid,
			"next" => $next,
			"prev" => $prev,
			"whoid" => $t_userid[$k],
			"who" => $t_user[$k], // ." [eid:".$v."|link:".$t_lid[$k]."]",
			"mail" => $t_mail[$k],
			"havemail" => ($t_mail[$k]=="" ? false : true ),
			"content" => $t_text[$k],
			"date" => $t_date[$k],
			"flag" => $t_flag[$k],
			"havenext" => (count($next)==0 ? false : true),
			"closed" => $fclosed,
		    );

  }
  return $elist;
}


?>
