<?php
/**
 * Users Definition
 *
 * @author Anakeen 2000 
 * @version $Id: Class.User.php,v 1.27 2004/03/04 13:45:59 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package WHAT
 * @subpackage CORE
 */
 /**
 */



$CLASS_USER_PHP = '$Id: Class.User.php,v 1.27 2004/03/04 13:45:59 eric Exp $';
include_once('Class.DbObj.php');
include_once('Class.QueryDb.php');
include_once('Class.Log.php');
include_once('Class.Application.php');
include_once('Class.Group.php');

require_once 'PEAR.php';
require_once 'Crypt/CHAP.php';

define("GALL_ID", 2);
define("ANONYMOUS_ID", 3);
define("GADMIN_ID", 4);

Class User extends DbObj
{
  var $fields = array ( "id","iddomain","lastname","firstname","login","password","isgroup","expires","passdelay","status","ntpasswordhash","lmpasswordhash","fid");

  var $id_fields = array ("id");

  var $dbtable = "users";

  var $order_by="lastname, isgroup desc";

  var $fulltextfields = array ("login","lastname","firstname");

  var $sqlcreate = "
create table users ( id      int not null,
                     iddomain int not null,
                primary key (id),
                        lastname   text,
                        firstname  text,
                        login      text not null,
                        password   varchar(30) not null,
                        isgroup    char,
                        expires    int,
                        passdelay  int,
                        status     char,
                        ntpasswordhash text,
                        lmpasswordhash text,
                        fid int);
create index users_idx1 on users(id);
create index users_idx2 on users(lastname);
create index users_idx3 on users(login);
create sequence seq_id_users start 10";



  function SetLoginName($loginDomain)
    {
      $query = new QueryDb($this->dbaccess,"User");
      if (ereg("(.*)@(.*)",$loginDomain, $reg)) {
    
	$queryd = new QueryDb($this->dbaccess,"Domain");
	$queryd->AddQuery("name='".$reg[2]."'");
	$list = $queryd->Query();

	if ($queryd->nb == 1) {
	  $domainId=$list[0]->iddomain;
	  $query->AddQuery("iddomain='$domainId'");
	  $query->AddQuery("login='".$reg[1]."'");
	} else {
	  return false;
	}
    
      } else {

	$query->AddQuery("login='$loginDomain'");
      }
      $list = $query->Query();

      if ($query->nb == 1) {
	$this=$list[0];
      } else {
	return FALSE;
      }

      return TRUE;
    }
  function SetLogin($login,$domain)
    {
      $query = new QueryDb($this->dbaccess,"User");

      $query->basic_elem->sup_where=array("login='$login'",
					  "iddomain=$domain");

      $list = $query->Query();

      if ($query->nb != 0) {
	$this=$list[0];
      } else {
	return FALSE;
      }

      return TRUE;
    }

  function PreInsert()    {
    if ($this->Setlogin($this->login,$this->iddomain)) return "this login exists";                                            
    if ($this->login=="") return _("login must not be empty");                                          
    if ($this->id == "") {
      $res = pg_exec($this->dbid, "select nextval ('seq_id_users')");
      $arr = pg_fetch_array ($res, 0);
      $this->id = $arr[0];
    }
                                                                                      
                                                                                        
                                                                                     
    if (isset($this->isgroup) && ($this->isgroup == "Y")) {
      $this->password_new="no"; // no passwd for group
    } else {
      $this->isgroup = "N";
    }

 

    $this->login = strtolower($this->login);

    if (isset($this->password_new) && ($this->password_new!="")) {
      $this->computepass($this->password_new, $this->password);
    }


                                                                                      
    //expires and passdelay
    $this->GetExpires(); 	    


  }
   

  function PostInsert()     
    {
      // create default ACL for each application
      // only for group
      //    if ($this->isgroup == "Y") {
      // 	$app = new Application();
      // 	$app-> UpdateUserAcl($this->id);
      //       }
    //Add default group to user
    $group=new group($this->dbaccess);
    $group->iduser=$this->id;
    //2 = default group
    $group->idgroup=2;
    $group->Add();       

      $err=$this->FreedomWhatUser();  
       // double pass to compute dynamic profil on itself
      return $err;
      if ($this->fid<>"") { 

 	$wsh = GetParam("CORE_PUBDIR","/home/httpd/what")."/wsh.php";
 	$cmd = $wsh . " --api=usercard_iuser --whatid={$this->id}";
 	exec($cmd);
      }
      return $err;
    }

  function PostUpdate()     
    {
      return $this->FreedomWhatUser();  
    }


  function PreUpdate()
    {
      if (isset($this->password_new) && ($this->password_new!="")) {
        $this->cryptEngine = new Crypt_MSCHAPv1;
	$this->ntpasswordhash = strtoupper(bin2hex($this->cryptEngine->ntPasswordHash($this->password_new)));
	$this->lmpasswordhash = strtoupper(bin2hex($this->cryptEngine->lmPasswordHash($this->password_new)));
	$this->computepass($this->password_new, $this->password);
      }

      //expires and passdelay
      $this->GetExpires();
                                                                                                                                                             

    }


  function PostDelete()
    {
      // delete reference in group table
      $group = new Group($this->dbaccess, $this->id);
      $group-> Delete();
      //remove MailAccount
      //$mailaccount=new MailAccount("",$this->id);
      //$mailaccount-> Remove();
    }


  function CheckLogin($login,$domain,$whatid)
    {
      $query = new QueryDb($this->dbaccess,"User");                  
                                                                                      
      $query->basic_elem->sup_where=array("login='$login'",
                                          "iddomain=$domain");     
                                                                                      
      $list = $query->Query();
      if ($query->nb==0 or ($query->nb==1 and $list[0]->id==$whatid))
        {return true;}
      else {return false;}
                                                                                      
    }

  /**
   * update user from FREEDOM IUSER document
   * @param int $fid document id
   * @param string $login login
   * @param int $iddomain mail domain identificator
   */
  function SetUsers($fid,$lname,$fname,$expires,$passdelay,
		    $login,$status,
		    $pwd1,$pwd2,$iddomain)  {

    $this->lastname=$lname;
    $this->firstname=$fname;	
    $this->status=$status;
    if ($this->login=="") $this->login=$login;

    //ne modifie pas le password en base m�me si contrainte forc�e 
    if ($pwd1==$pwd2 and $pwd1<>"") {
      $this->password_new=$pwd2;   
    }

    if ($this->iddomain == 0) {
      $this->iddomain=1;
    }
    
    if ($expires>0) $this->expires=$expires;
 
     	  
    if ($passdelay>0) $this->passdelay=$passdelay;
    $this->fid=$fid;
    if (! $this->isAffected()) {    
      $err=$this->Add();
    } else { 
      $err=$this->Modify();
    }  

    if ($err == "") {
      if ($iddomain > 1) {
	if ($this->iddomain < 2) {
	  include_once("Class.MailAccount.php");
	  $this->iddomain=$iddomain;     
	  // create mail account
	  $mailapp = new Application();
	  if ($mailapp->Exists("MAILADMIN")) {
	    $mailapp->Set("MAILADMIN", $action->parent);
	    $uacc = new MailAccount($mailapp->GetParam("MAILDB"));
	    $uacc->iddomain    = $this->iddomain ;
	    $uacc->iduser      = $this->id;
	    $uacc->login       = $this->login;
	    $err=$uacc->Add();
	    if ($err == "") $err=$this->Modify();
	  }          
	} 
	 
      }  
     
    
    
    }
    return $err;
  }
 /**
   * update user from FREEDOM IUSER document
   * @param int $fid document id
   * @param string $login login
   * @param int $iddomain mail domain identificator
   */
  function SetGroups($fid,$gname,$login,$iddomain)  {
    if ($gname!="") $this->lastname=$gname;
    if (($this->login=="")&&($login!="")) $this->login=$login;

  

    if ($this->iddomain == 0) {
       $this->iddomain=1;
    }
    
    $this->fid=$fid;
    if (! $this->isAffected()) {    
      $this->isgroup="Y";
      $err=$this->Add();
    } else { 
      $err=$this->Modify();
    }  

    if ($err == "") {
      if ($iddomain > 1) {
	if ($this->iddomain < 2) {
	  include_once("Class.MailAccount.php");
	  $this->iddomain=$iddomain;     
	  // create mail account
	  $mailapp = new Application();
	  if ($mailapp->Exists("MAILADMIN")) {
	    $mailapp->Set("MAILADMIN", $action->parent);
	    $uacc = new MailAccount($mailapp->GetParam("MAILDB"));
	    $uacc->iddomain    = $this->iddomain ;
	    $uacc->iduser      = $this->id;
	    $uacc->login       = $this->login;
	    $err=$uacc->Add();
	    if ($err == "") $err=$this->Modify();
	  }          
	} 
	
      }
    }
    return $err;
  }


  //Add and Update expires and passdelay for password
  //Call in PreUpdate and PreInsert
  function GetExpires()
    {
      if (intval($this->passdelay) == 0) {
	$this->expires="0"; 
	$this->passdelay="0";
      }// neither expire
      else if (intval($this->expires)==0) {
	$this->expires=time()+$this->passdelay;
      }

    }

  function FreedomWhatUser() {   
    if (@include_once('FDL/Lib.Dir.php')) {

      $dbaccess=GetParam("FREEDOM_DB");

      if ($this->fid<>"") { 
	$iuser=new Doc($dbaccess,$this->fid); 

	$err=$iuser->RefreshDocUser(); 

      } //Update from what
      else {
	if ($this->isgroup=="Y") $fam="IGROUP";
	else $fam="IUSER";;
	$filter = array("us_whatid = ".$this->id);
	$tdoc = getChildDoc($dbaccess, 0,0,"ALL", $filter,1,"LIST",$fam);
	if (count ($tdoc)==0)  {
	  //Create a new doc IUSER                                        
	  $iuser = createDoc($dbaccess,$fam);
	  $iuser->SetValue("US_WHATID",$this->id);   
	  $iuser->Add();
	  $err=$iuser->RefreshDocUser(); 
	} else {
	  $this->fid=$tdoc[0]->id;
	  $this->modify();
	  $err=$tdoc[0]->RefreshDocUser(); 
	
	}

      }
      return $err;
    }
  }


  // --------------------------------------------------------------------
  function computepass($pass, &$passk)
    {
      srand((double)microtime()*1000000);
      $salt = chr(rand(59,122)).chr(rand(59,122));
      $passk = crypt($pass, $salt);
    }

  function checkpassword($pass)
    {
      if ($this->isgroup == 'Y') return false; // don't log in group 
      return($this->checkpass($pass,$this->password));
    }    

  // --------------------------------------------------------------------
  function checkpass($pass, $passk)
    {
      $salt = substr($passk, 0, 2);
      $passres = crypt($pass, $salt);
      return ($passres == $passk);
    } 

  function PostInit() {

    $group = new group($this->dbaccess);

    // Create admin user
    $this->iddomain=1;
    $this->id=1;
    $this->lastname="Master";
    $this->firstname="What";
    $this->password_new="anakeen";
    $this->login="admin";
    $this->Add(true);
    $group->iduser=$this->id;

    // Create default group
    $this->iddomain=1;
    $this->id=GALL_ID;
    $this->lastname="Default What Group";
    $this->firstname="";
    $this->login="all";
    $this->isgroup="Y";
    $this->Add(true);
    $group->idgroup=$this->id;
    $group->Add(true);
  
  
    // Create anonymous user
    $this->iddomain=1;
    $this->id=ANONYMOUS_ID;
    $this->lastname="anonymous";
    $this->firstname="guest";
    $this->login="anonymous";
    $this->isgroup="N";
    $this->Add(true);

    // Create admin group
    $this->iddomain=1;
    $this->id=GADMIN_ID;
    $this->lastname="Admin Group";
    $this->firstname="";
    $this->login="gadmin";
    $this->isgroup="Y";
    $this->Add(true);
    $group->idgroup=GALL_ID;
    $group->iduser=GADMIN_ID;
    $group->Add(true);

    // Store error messages
     
  }

  // get All Users (not group)
  function GetUserList($qtype="LIST") {
    $query = new QueryDb($this->dbaccess,"User");
    $query->order_by="lastname";
    $query-> AddQuery("(isgroup != 'Y') OR (isgroup isnull)");
    return($query->Query(0,0,$qtype));
  }

  // get All groups
  function GetGroupList($qtype="LIST") {
    $query = new QueryDb($this->dbaccess,"User");
    $query->order_by="lastname";
    $query-> AddQuery("isgroup = 'Y'");
    return($query->Query(0,0,$qtype));
  }

  // get All users & groups
  function GetUserAndGroupList() {
    $query = new QueryDb($this->dbaccess,"User");
    $query->order_by="isgroup desc, lastname";
    return($query->Query());
  }


  /**
   * get All ascendant group ids of the user object 
   */
  function GetGroupsId() {
    $query = new QueryDb($this->dbaccess, "Group");

    $query-> AddQuery("iduser='{$this->id}'");

    $list = $query->Query(0,0,"TABLE");
    $groupsid=array();

    if ($query->nb >0) {
      while (list($k,$v) = each($list)) {
	$groupsid[] = $v["idgroup"];
      }
    
    } 

    return $groupsid;

  }

  
  /**
   * for group :: get All user & groups ids in all descendant(recursive);
   * @param int $id group identificator
   * @return array of user array
   */
  function GetRUsersList($id,$r=array()) {
    $query = new QueryDb($this->dbaccess, "User");
    $list = $query->Query(0,0,"TABLE",
			  "select users.* from users, groups where ".
			  "groups.iduser=users.id and ".
			  "idgroup=$id ;");


    $uid=array();

    if ($query->nb >0) {
      foreach($list as $k=>$v) {
	$uid[$v["id"]] = $v;
	if ($v["isgroup"]=="Y") {
	  if (! in_array($v["id"],$r)) {
	    array_push($r,$v["id"]);
	    $uid += $this->GetRUsersList($v["id"],$r);
	  }
	}
      }
    
    } 

    return $uid;

  }

   
  /**
   * for group :: get All direct user & groups ids 
   * @param int $id group identificator
   */
  function GetUsersGroupList($gid) {
    $query = new QueryDb($this->dbaccess, "User");
    $list = $query->Query(0,0,"TABLE",
			  "select users.* from users, groups where ".
			  "groups.iduser=users.id and ".
			  "idgroup=$gid ;");


    $uid=array();

    if ($query->nb >0) {
      while (list($k,$v) = each($list)) {
	$uid[$v["id"]] = $v;	
      }
    
    } 

    return $uid;
  }

 
  

  // only use for group
  // get user member of group
  function getGroupUserList($qtype="LIST", $withgroup=false) {
    $query = new QueryDb($this->dbaccess,"User");
    $query->order_by="isgroup desc, lastname";
    $selgroup = "and (isgroup != 'Y' or isgroup is null)";
    if ($withgroup) $selgroup = "";
    return ($query->Query(0,0,$qtype,
			  "select users.* from users, groups where ".
			  "groups.iduser=users.id and ".
			  "idgroup={$this->id} {$selgroup};"));
  }
}
?>
