<?php
/**
 * User manipulation
 *
 * @author Anakeen 2004
 * @version $Id: Method.DocIUser.php,v 1.42 2007/02/14 16:35:13 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage USERCARD
 */
 /**
 */
var $cviews=array("FUSERS:FUSERS_IUSER");
var $eviews=array("USERCARD:CHOOSEGROUP");
var $defaultview="FUSERS:FUSERS_IUSER:T";
var $defaultedit="FUSERS:FUSERS_EIUSER:T";
 

function SpecRefresh() {
  $err=_USER::SpecRefresh();

    $this->AddParamRefresh("US_WHATID","US_LOGIN,US_GROUP");
    $this->AddParamRefresh("US_AUTOMAIL","US_EXTMAIL");
    if ($this->getValue("US_IDDOMAIN",1) > 1) $this->AddParamRefresh("US_WHATID","US_DOMAIN");
    $this->AddParamRefresh("US_IDDOMAIN","US_DOMAIN");
    
    if ($this->getValue("US_STATUS")=='D') $err .= ($err==""?"":"\n")._("user is desactivated");
    // refresh MEID itself
    $this->SetValue("US_MEID",$this->id);
    $iduser = $this->getValue("US_WHATID");
    if ($iduser > 0) {
      $user = $this->getWUser();
      if (! $user->isAffected()) return sprintf(_("user #%d does not exist"), $iduser);
    } else {
      if ($this->getValue("us_login")!='-') $err= _("user has not identificator");
    }
    return $err;
}

/**
 * test if the document can be set in LDAP
 */
function canUpdateLdapCard() {
  return  ($this->getValue("US_STATUS")!='D');

}


function GetOtherGroups() {
  if ($this->id == 0) return array();
  
  include_once("FDL/freedom_util.php");  
  include_once("FDL/Lib.Dir.php");  

  $sqlfilters[]="in_textlist(grp_idruser,{$this->id})";
  // $sqlfilters[]="fromid !=".getFamIdFromName($this->dbaccess,"IGROUP");
  $tgroup=getChildDoc($this->dbaccess, 
		      0, 
		      "0", "ALL", $sqlfilters, 
		      1, 
		      "TABLE", getFamIdFromName($this->dbaccess,"GROUP"));
  
  return $tgroup;
}
/**
 * Refresh folder parent containt
 */
function refreshParentGroup() {
  $tgid=$this->getTValue("US_IDGROUP");
  foreach ($tgid as $gid) {
    $gdoc=new_Doc($this->dbaccess,$gid);
    if ($gdoc->isAlive()) {
      $gdoc->insertGroups();
    }
  }
}
/**
 * recompute intranet values from USER database
 */
function RefreshDocUser() {

  $err="";
  $wid=$this->getValue("us_whatid");
  if ($wid > 0) { 
    $wuser=$this->getWuser(true);

    if ($wuser->isAffected()) {
      $this->SetValue("US_WHATID",$wuser->id);
      $this->SetValue("US_LNAME",$wuser->lastname);
      $this->SetValue("US_FNAME",$wuser->firstname);
      $this->SetValue("US_PASSWD",$wuser->password);
      $this->SetValue("US_PASSWD1"," ");
      $this->SetValue("US_PASSWD2"," ");
      $this->SetValue("US_LOGIN",$wuser->login);
      $this->SetValue("US_STATUS",$wuser->status);
      $this->SetValue("US_PASSDELAY",$wuser->passdelay);
      $this->SetValue("US_EXPIRES",$wuser->expires);
      $this->SetValue("US_DAYDELAY",$wuser->passdelay/3600/24);
      $this->SetValue("US_IDDOMAIN",$wuser->iddomain);
      include_once("Class.Domain.php");
      $dom = new Domain("",$wuser->iddomain);
      $this->SetValue("US_DOMAIN",$dom->name);
      $mail=$wuser->getMail();
      if (! $mail) $this->DeleteValue("US_MAIL");
      else $this->SetValue("US_MAIL", $mail);
      if ($wuser->passdelay<>0) { 
	$this->SetValue("US_EXPIRESD",strftime("%d/%m/%Y",$wuser->expires));
	$this->SetValue("US_EXPIREST",strftime("%H:%M",$wuser->expires));
      } else  {
	$this->SetValue("US_EXPIRESD"," ");
	$this->SetValue("US_EXPIREST"," ");
      }


      $this->SetValue("US_MEID",$this->id);

      // search group of the user
      $g = new Group("",$wid);

      if (count($g->groups) > 0) {
	foreach ($g->groups as $gid) {
	  $gt=new User("",$gid);
	  $tgid[$gid]=$gt->fid;
	  $tglogin[$gid]=$this->getTitle($gt->fid);
	}
	$this->SetValue("US_GROUP", $tglogin);
	$this->SetValue("US_IDGROUP", $tgid);
      } else {
	$this->SetValue("US_GROUP"," ");
	$this->SetValue("US_IDGROUP"," ");
      }
      $err=$this->modify();


    } else     {
      $err= sprintf(_("user %d does not exist"),$wid);
    }
  } 
  
  
  return $err;
}



/**
 * Modify IUSER via Freedom    
 */
function PostModify() {                                                                                    
  $uid=$this->GetValue("US_WHATID");
  $lname=$this->GetValue("US_LNAME");
  $fname=$this->GetValue("US_FNAME");
  $pwd1=$this->GetValue("US_PASSWD1");
  $pwd2=$this->GetValue("US_PASSWD2");
  $pwd=$this->GetValue("US_PASSWD");
  $expires=$this->GetValue("US_EXPIRES");
  $daydelay=$this->GetValue("US_DAYDELAY");
  if ($daydelay==-1) $passdelay=$daydelay;
  else $passdelay=intval($daydelay)*3600*24;
  $status=$this->GetValue("US_STATUS");
  $login=$this->GetValue("US_LOGIN");
  $extmail=$this->GetValue("US_EXTMAIL");

  if ($login != "-") {
    // compute expire for epoch
  
    $expiresd=$this->GetValue("US_EXPIRESD");
    $expirest=$this->GetValue("US_EXPIREST","00:00");
    //convert date 
    $expdate=$expiresd." ".$expirest.":00";
    $expires=0;
    if ($expdate != "") {
      if (ereg("([0-9][0-9])/([0-9][0-9])/(2[0-9][0-9][0-9]) ([0-2][0-9]):([0-5][0-9]):([0-5][0-9])", 
	       $expdate, $reg)) {   
	$expires=mktime($reg[4],$reg[5],$reg[6],$reg[2],$reg[1],$reg[3]);
      } else  if (ereg("(2[0-9][0-9][0-9])-([0-9][0-9])-([0-9][0-9]) ([0-2][0-9]):([0-5][0-9]):([0-5][0-9])", 
	       $expdate, $reg)) {   
	$expires=mktime($reg[4],$reg[5],$reg[6],$reg[2],$reg[3],$reg[1]);
      }
      
    }


    $iddomain=$this->GetValue("US_IDDOMAIN");
    $domain=$this->GetValue("US_DOMAIN");

    $fid=$this->id;        
    $user=$this->getWUser();
    if (!$user) {
      $user=new User(""); // create new user
      $this->wuser=&$user;
    }
    $err.=$user->SetUsers($fid,$lname,$fname,$expires,$passdelay,
			  $login,$status,$pwd1,$pwd2,
			  $iddomain,$extmail);  
    if ($err=="") { 
      if ($user)  $err=$this->setGroups(); // set groups (add and suppress) may be long
      if (($pwd1 == "") && ($pwd1==$pwd2) && ($pwd!="")) {
	if (($pwd != $user->password) && (strlen($pwd)>12)) {
	  $user->password=$pwd;
	  $err=$user->modify();
	}
      }
    }
 
    if ($err=="") {
      $this->setValue("US_WHATID",$user->id);
      $err=$this->RefreshDocUser();// refresh from core database 
      $this->modify(true,array("us_whatid"));
      //      $this->refreshParentGroup();
      $errldap=$this->RefreshLdapCard();
      if ($errldap!="") AddWarningMsg($errldap);
    } 

  } else { 
    // tranfert extern mail if no login specified yet
    if ($this->getValue("us_login")=="-") {
      $this->setValue("US_IDDOMAIN","0");
      $email=$this->getValue("us_extmail");
      if (($email != "")&&($email[0]!="<")) $this->setValue("us_mail",$this->getValue("us_extmail"));
      else $this->deleteValue("us_mail");
    }
  }

  $this->setValue("US_LDAPDN",$this->getLDAPValue("dn",1));
  return $err;

}


function PostDelete() {
  _USER::PostDelete();

  $user=$this->getWUser();
  if ($user) $user->Delete();
                                                                                     
}                                                                                    
                                                                                    
                                                                                      

/**
 * Do not call ::setGroup if its import 
 * called only in initialisation
 */
function preImport() {
  if ($this->id > 0) {
    global $_POST;
    $_POST["gidnew"]="N";
  }
}
                                                                                      
function ConstraintPassword($pwd1,$pwd2,$login) {
  $sug=array();
  $err="";

  if ($pwd1<>$pwd2) {
    $err= _("the 2 passwords are not the same");
  }  else if (($pwd1 == "")&&($this->getValue("us_whatid") == "")) {
    if ($login != "-") $err= _("passwords must not be empty");
  }    
  
                                                                                      
  return array("err"=>$err,
	       "sug"=>$sug);                                                                              
                                                                                  
}

function ConstraintExpires($expiresd,$expirest,$daydelay) {
  $sug=array();
  if (($expiresd<>"") && ($daydelay==0)) {
    $err= _("Expiration delay must not be 0 to keep expiration date");
  }
                                       
  return array("err"=>$err,
	       "sug"=>$sug);
}

function editlikeperson($target="finfo",$ulink=true,$abstract="Y") {
  global $action;
  
  $this->lay = new Layout(getLayoutFile("FDL","editbodycard.xml"), $action);
  $this->attributes->attr['us_fr_intranet']->visibility='R';
  $this->ApplyMask();
  if ($this->getValue("us_iddomain") == 0) {
    $this->attributes->attr['us_extmail']->mvisibility='W';
    $this->attributes->attr['us_extmail']->fieldSet= $this->attributes->attr['us_fr_coord'];
    $this->attributes->attr['us_extmail']->ordered=$this->attributes->attr['us_pphone']->ordered - 1;
    uasort($this->attributes->attr,"tordered"); 
  }
    
  $this->editbodycard($target,$ulink,$abstract);
  
}

function fusers_iuser($target="finfo",$ulink=true,$abstract="Y") {
  global $action;
  //setHttpVar("specialmenu","menuab");
  $this->viewdefaultcard($target,$ulink,$abstract);
  $action->parent->AddCssRef("USERCARD:faddbook.css",true);
  $action->parent->AddJsRef($action->GetParam("CORE_PUBURL")."/USERCARD/Layout/faddbook.js");

  
  // list of attributes displayed directly in layout
  $ta=array("us_workweb","us_photo","us_lname","us_fname","us_society","us_civility","us_mail","us_phone","us_mobile","us_fax","us_intphone","us_workaddr","us_workcedex","us_country","us_workpostalcode","us_worktown","us_groups","us_whatid","us_state","us_login","us_status","us_domain","us_expiresd","us_expirest","us_daydelay","us_idsociety");
  //$ta["ident"]=array("us_lo

  $la=$this->getAttributes();
  $to=array();
  $tabs=array();
  foreach ($la as $k=>$v) {
    $va=$this->getValue($v->id);
    if (($va || ($v->type=="array")) && (! in_array($v->id,$ta)) &&(!$v->inArray()) ) {
	  
     if ((($v->mvisibility == "R") || ($v->mvisibility == "W"))) {
	if ($v->type=="array") {
	  $hv=$this->getHtmlValue($v,$va,$target,$ulink);
	  if ($hv) {
	    $to[]=array("lothers"=>$v->labelText,
		      "aid"=>$v->id,
		      "vothers"=>$hv,
		      "isarray"=>true);	
	    $tabs[$v->fieldSet->labelText][]=$v->id;
	  }
	} else {
	  $to[]=array("lothers"=>$v->labelText,
		      "aid"=>$v->id,
		      "vothers"=>$this->getHtmlValue($v,$va,$target,$ulink),
		      "isarray"=>false);
	$tabs[$v->fieldSet->labelText][]=$v->id;
	}
      }
    }
  }
  $this->lay->setBlockData("OTHERS",$to);
  $this->lay->set("HasOTHERS",(count($to)>0));
  $this->lay->set("HasDOMAIN",($this->getValue("US_IDDOMAIN")>9));
  $this->lay->set("HasDPassword",(intval($this->getValue("US_DAYDELAY"))!=0));
  $ltabs=array();
  foreach ($tabs as $k=>$v) {
    $ltabs[$k]=array("tabtitle"=>$k,
		     "aids"=>"['".implode("','",$v)."']");
  }
  $this->lay->setBlockData("TABS",$ltabs);
  $this->lay->set("CanEdit",($this->control("edit")==""));
}


/**
 * interface to only modify name and password
 */
function editchangepassword() {
  $this->viewprop();
  $this->editattr(false);
}
function fusers_eiuser() {
  global $action;
  $this->editattr();
  $action->parent->AddCssRef("USERCARD:faddbook.css",true);
  $action->parent->AddJsRef($action->GetParam("CORE_PUBURL")."/USERCARD/Layout/faddbook.js");
  $firsttab=getHttpVars("tab"); // first tab displayed
  
  // list of attributes displayed directly in layout
  $ta=array("us_workweb","us_photo","us_lname","us_fname","us_society","us_idsociety","us_civility","us_mail","us_phone","us_mobile","us_fax","us_intphone","us_workaddr","us_workcedex","us_country","us_workpostalcode","us_worktown","us_groups","us_whatid","us_state","us_login","us_status","us_domain","us_iddomain","us_expiresd","us_expirest","us_daydelay","us_passwd1","us_passwd2","us_extmail","us_role","us_scatg","us_pfax","us_pphone","us_job","us_type","us_initials","us_service","us_idservice","us_socaddr");
  //$ta["ident"]=array("us_lo

  $la=$this->getNormalAttributes();

  $this->lay->set("editgroup",($la["us_group"]->mvisibility=="W"));
  $this->lay->set("firsttab",$firsttab);
  $to=array();
  $th=array();
  $tabs=array();
  foreach ($la as $k=>$v) {
    $va=$this->getValue($v->id);
    if (!$v->inArray() && (! in_array($v->id,$ta)))  {	      
      if ($v->mvisibility != "I") {
	if ($v->type=="array") {
	  $hv=getHtmlInput($this,$v,$va);
	  if ($hv) {
	    if ($v->mvisibility != "H") {
	      $to[]=array("lothers"=>$v->labelText,
			  "aid"=>$v->id,
			  "vothers"=>$hv,
			  "isarray"=>true);		    
	      $tabs[$v->fieldSet->labelText][]=$v->id;
	    } else {
	      $th[]=array("aid"=>$v->id,
			  "vothers"=>getHtmlInput($this,$v,$va));
	    }
	  }
	} else {
	  if ($v->mvisibility != "H") {
	    $to[]=array("lothers"=>$v->labelText,
			"aid"=>$v->id,
			"vothers"=>getHtmlInput($this,$v,$va),
			"isarray"=>false);
	    $tabs[$v->fieldSet->labelText][]=$v->id;
	  } else {
	    $th[]=array("aid"=>$v->id,
			"vothers"=>getHtmlInput($this,$v,$va));
	    
	  }
	
	}
      }
    }
  }
  $this->lay->setBlockData("OTHERS",$to);
  $this->lay->setBlockData("IHIDDENS",$th);
  $this->lay->set("HasOTHERS",(count($to)>0));
  $ltabs=array();
  foreach ($tabs as $k=>$v) {
    $ltabs[$k]=array("tabtitle"=>$k,
		     "aids"=>"['".implode("','",$v)."']");
  }
  $this->lay->setBlockData("TABS",$ltabs);
  $this->viewprop();
}

?>
