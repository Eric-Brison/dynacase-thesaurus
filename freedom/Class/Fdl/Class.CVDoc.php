<?php
/**
 *  Control view Class Document
 *
 * @author Anakeen 2003
 * @version $Id: Class.CVDoc.php,v 1.5 2004/08/09 16:23:27 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage 
 */
 /**
 */





include_once('FDL/Class.Doc.php');

/**
 * Control view Class
 */
Class CVDoc extends Doc {

  
  /**
   * CVDoc has its own special access depend on special views
   * by default the three access are always set
   *
   * @var array
   */
  var $acls = array("view","edit","delete");

	

  var $usefor='W';
  var $defDoctype='P';
  var $attrPrefix="CVI"; // prefix attribute
  // --------------------------------------------------------------------
 

  function CVDoc($dbaccess='', $id='',$res='',$dbid=0) {
    // first construct acl array

   
    if (isset($this->fromid)) $this->defProfFamId=$this->fromid; // it's a profil itself



    // don't use Doc constructor because it could call this constructor => infinitive loop
    DocCtrl::DocCtrl($dbaccess, $id, $res, $dbid);

    $this->setAcls();
  }


  
  function setAcls() {
    $ti = $this->getTValue("CV_IDVIEW");
    $tl = $this->getTValue("CV_LVIEW");
    $tz = $this->getTValue("CV_ZVIEW");
    $tk = $this->getTValue("CV_KVIEW");
    $tm = $this->getTValue("CV_MSKID");
    
    $ka = POS_WF;
    while (list($k, $v) = each($tk)) {
      if ($ti[$k]=="") $cvk="CV$k";
      else $cvk=$ti[$k];
      $this->dacls[$cvk]=array("pos"=>$ka,
			       "description" =>$tl[$k]);
      $this->acls[]=$cvk;
      $ka++;
    }
  }


  function getView($vid) {
    $ti = $this->getTValue("CV_IDVIEW");
    foreach ($ti as $k=>$v) {
      if ($v == $vid) {
	// found it
	$tl = $this->getTValue("CV_LVIEW");
	$tz = $this->getTValue("CV_ZVIEW");
	$tk = $this->getTValue("CV_KVIEW");
	$tm = $this->getTValue("CV_MSKID");

	return array("CV_IDVIEW"=>$v,
		     "CV_LVIEW"=>$tl[$k],
		     "CV_ZVIEW"=>$tz[$k],
		     "CV_KVIEW"=>$tk[$k],
		     "CV_MSKID"=>$tm[$k]);
	
      }
    }
    return false;
  }
  

  function getViews() {
    $ti = $this->getTValue("CV_IDVIEW");
    $tv=array();
    foreach ($ti as $k=>$v) {
     
	$tv[$v]=$this->getView($v);

	
      
    }
    return $tv;
  }
  

  function postModify() {
    
    $ti = $this->getTValue("CV_IDVIEW");
    foreach ($ti as $k=>$v) {
      if ($v == "") $ti[$k]="CV$k";
    }
    $this->setValue("CV_IDVIEW",$ti);
  }

  function DocControl($aclname) {
    return Doc::Control($aclname);
  }

  /**
   * Special control in case of dynamic controlled profil
   */
  function Control($aclname) {

    $err= $this->DocControl($aclname);
    if ($err == "") return $err; // normal case

    if ($this->getValue("DPDOC_FAMID") > 0) {
      // special control for dynamic users
      if (! isset($this->pdoc)) {
	$pdoc = createDoc($this->dbaccess,$this->fromid,false);
	$pdoc->doctype="T"; // temporary
	//	$pdoc->setValue("DPDOC_FAMID",$this->getValue("DPDOC_FAMID"));
	$err=$pdoc->Add();
	if ($err != "") return "CVDoc::Control:".$err; // can't create profil

	$pdoc->setProfil($this->profid, $this->doc);
	$pdoc->dacls=$this->dacls;

	$this->pdoc = &$pdoc;
      }


      $err=$this->pdoc->DocControl($aclname);

    }
    return $err;
  }

  
  function Set(&$doc) {
    if (! isset($this->doc) ) {
      $this->doc= &$doc;
     
    }
  }
}

?>
