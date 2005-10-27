<?php
/**
 * Intranet User & Group  manipulation
 *
 * @author Anakeen 2004
 * @version $Id: Method.DocIntranet.php,v 1.12 2005/10/27 14:36:07 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage USERCARD
 */
 /**
 */


/**
 * verify if the login syntax is correct and if the login not already exist
 * @param string $login login to test
 * @return array 2 items $err & $sug for view result of the constraint
 */
function ConstraintLogin($login,$iddomain) {
  $sug=array("-");
  $id=$this->GetValue("US_WHATID");
  $user=new User("",$id);
                                         
  if ($login == "") {
    $err= _("the login must not be empty");
  } else if ($login == "-") {
  } else {
    if (!ereg("^[a-z][-_a-z0-9\.]*[a-z0-9]+$", $login)) {
      $err= _("the login syntax is like : john.doe");
    }  
    $q=new QueryDb("","User");
    $q->AddQuery("login='$login'");
    $q->AddQuery("id != $id");
    $q->AddQuery("iddomain=$iddomain");
    $q->Query(0,0,"TABLE");
    if ($q->nb > 0) $err= _("login yet use");
    if ($err=="") $sug=array();
  }
  return array("err"=>$err,"sug"=>$sug);
}

function preCreated() {
  if ($this->getValue("US_WHATID") != "") {
    include_once('FDL/Lib.Dir.php');

    $filter = array("us_whatid = ".$this->getValue("US_WHATID"));
    $tdoc = getChildDoc($this->dbaccess, 0,0,"ALL", $filter,1,"TABLE",$this->fromid);
    if (count ($tdoc) > 0)  return _("what id already set in freedom\nThis kind of document can not be duplicated");
  }
}

/**
 * interface to affect group for an user
 *
 * @param string $target window target name for hyperlink destination
 * @param bool $ulink if false hyperlink are not generated
 * @param bool $abstract if true only abstract attribute are generated
 */
function ChooseGroup($target="_self",$ulink=true,$abstract=false) {
  global $action;

  $action->parent->AddJsRef($action->GetParam("CORE_PUBURL")."/FDL/Layout/mktree.js");



  $iduser = $this->getValue("US_WHATID");
  if ($iduser > 0) {
    $user = $this->getWUser();
    if (! $user->isAffected()) return sprintf(_("user #%d does not exist"), $iduser);
    $ugroup=$user->GetGroupsId();
  } else {
    $user=new User();
    $ugroup=array("2"); // default what group
  }

  $this->lay->set("wid",($iduser=="")?"0":$iduser);
    
  $q2= new queryDb("","User");
  $groups=$q2->Query(0,0,"TABLE","select users.*, groups.idgroup, domain.name as domain from users, groups, domain where users.id = groups.iduser and users.iddomain=domain.iddomain and users.isgroup='Y'");

  $q2= new queryDb("","User");
  $mgroups=$q2->Query(0,0,"TABLE","select users.*, domain.name as domain from users,domain where users.iddomain=domain.iddomain and isgroup='Y' and id not in (select iduser from groups)");
  
  if ($groups) {
    foreach ($groups as $k=>$v) {
      $groupuniq[$v["id"]]=$v;
      $groupuniq[$v["id"]]["checkbox"]="";
      if (in_array($v["id"],$ugroup)) 	 $groupuniq[$v["id"]]["checkbox"]="checked";
    }
  }
  if (!$groups) $groups=array();
  if ($mgroups) {
    foreach ($mgroups as $k=>$v) {
	$cgroup=$this->_getChildsGroup($v["id"],$groups);
	$tgroup[$k]=$v;
	$tgroup[$k]["SUBUL"]=$cgroup;
      $groupuniq[$v["id"]]=$v;
      $groupuniq[$v["id"]]["checkbox"]="";
      if (in_array($v["id"],$ugroup)) $groupuniq[$v["id"]]["checkbox"]="checked";
    }
  }
  $this->lay->setBlockData("LI",$tgroup);
  $this->lay->setBlockData("SELECTGROUP",$groupuniq);

}

/**
 * internal function use for choosegroup
 * use to compute displayed group tree
 */
function _getChildsGroup($id,$groups) {
  $tlay=array();
  foreach ($groups as $k=>$v) {
    if ($v["idgroup"]==$id) {
      $tlay[$k]=$v;
       $tlay[$k]["SUBUL"]=$this->_getChildsGroup($v["id"],$groups);

    }
  }
  
  if (count($tlay)==0) return "";
  global $action;
  $lay = new Layout("USERCARD/Layout/ligroup.xml",$action);
  $lay->setBlockData("LI",$tlay);
  return $lay->gen();
}
/**
 * affect new groups to the user
 * @global gidnew  Http var : egual Y to say effectif change (to not suppress group if gid not set)
 * @global gid Http var : array of new groups id
 */
function setGroups() {
  include_once("FDL/Lib.Usercard.php");

  global $_POST;
  $gidnew = $_POST["gidnew"];
  $tgid=array(); // group ids will be modified

  if ($gidnew=="Y") {
    $gid = $_POST["gid"];
    if ($gid=="") $gid=array();

    $user=$this->getWUser();
    $rgid=$user->GetGroupsId();
    if ((count($rgid)!=count($gid)) || (count(array_diff($rgid,$gid))!=0)) {
      $gdel=array_diff($rgid,$gid);
      $gadd=array_diff($gid,$rgid);

      // add group
      $g = new Group("",$user->id);
      foreach ($gadd as $gid) {	
	$g->iduser=$user->id;
	$g->idgroup=$gid;
	$aerr.=$g->Add(true);
	if ($aerr=="") {
	  // insert in folder group
	  $gdoc=$this->getDocUser($gid);
	  $gdoc->insertMember($this->id);
	  $tgid[$gid]=$gid;
	}
	$err.=$aerr;
      }
      foreach ($gdel as $gid) {	
	$g->iduser=$gid;
	$aerr.=$g->SuppressUser($user->id,true);
	
	if ($aerr=="") {
	  // delete in folder group
	  $gdoc=$this->getDocUser($gid);
	  $gdoc->deleteMember($this->id);
	  $tgid[$gid]=$gid;
	  
	}

      }
      $g->PostInsert();


      //if ($user->isgroup=='Y')  $tgid[$user->id]=$user->id;
    }
  }
  refreshGroups($tgid,true);
  
  return $err;
}

/**
 * return document objet from what id (user or group)
 * @param int $wid what identificator
 * @return Doc the object document (false if not found)
 */
function getDocUser($wid) {
  $u= new User("",$wid);
  if ($u->isAffected()) {
    if ($u->fid > 0) {
      $du=new_Doc($this->dbaccess,$u->fid);
      if ($du->isAlive()) return $du;
    }
  }
  return false;
}

/** 
 * return what user object conform to whatid
 * @return User return false if not found
 */
function getWuser($nocache=false) {
  if ($nocache) {
    $u=new User();
    unset($this->wuser); // needed for reaffect new values
  }
  if (! isset($this->wuser)) {
    $wid=$this->getValue("us_whatid");
    if ($wid > 0) { 
      $this->wuser=new User("",$wid); 
    }
  }
  if (! isset($this->wuser)) return false;
  return $this->wuser;
			       
}
?>
