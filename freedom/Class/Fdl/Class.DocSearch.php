<?php
/**
 * Document searches classes
 *
 * @author Anakeen 2000 
 * @version $Id: Class.DocSearch.php,v 1.46 2007/08/02 09:56:58 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 */
 /**
 */



include_once("FDL/Class.PDocSearch.php");
include_once("FDL/Lib.Dir.php");


Class DocSearch extends PDocSearch {
  

  public $defDoctype='S';
  public $defaultedit= "FREEDOM:EDITSEARCH";


  function DocSearch($dbaccess='', $id='',$res='',$dbid=0) {

    PDocSearch::__construct($dbaccess, $id, $res, $dbid);
    if (((! isset($this->fromid))) || ($this->fromid == "")) $this->fromid = FAM_SEARCH;
  }

  function AddQuery($tquery) {
    // insert query in search document
    if (is_array($tquery)) $query=implode(";\n",$tquery);
    else $query=$tquery;

    if ($query == "") return "";
    if ($this->id=="") return "";

    if (substr($query,0,6) != "select") {
      AddWarningMsg(sprintf(_("query [%s] not valid for select document"), $query));
      return sprintf(_("query [%s] not valid for select document"), $query);
    }
    $oqd = new QueryDir($this->dbaccess);
    $oqd->dirid = $this->id;
    $oqd->qtype="M"; // multiple
    $oqd->query = $query;

    if ($this->id>0) $this->exec_query("delete from fld where dirid=".intval($this->id)." and qtype='M'");
    $err= $oqd-> Add();
    if ($err == "") {
      $this->setValue("SE_SQLSELECT",$query);
      $err=$this->modify();
    }

    return $err;
    
  }

  /**
   * return true if the search has parameters
   */
  function isParameterizable() {
    return false;
  }

  function GetQueryOld() {
    $query = new QueryDb($this->dbaccess, "QueryDir");
    $query->AddQuery("dirid=".$this->id);
    $query->AddQuery("qtype != 'S'");
    $tq=$query->Query(0,0,"TABLE");


    if ($query->nb > 0)
      {
	return $tq[0]["query"];
      }
    return "";
  }

  /**
   * return SQL query(ies) needed to search documents
   * @return array string
   */
  function getQuery() {
    if (! $this->isStaticSql()) {
      $query= $this->ComputeQuery($this->getValue("se_key"),
				  $this->getValue("se_famid"),
				  $this->getValue("se_latest"),
				  $this->getValue("se_case")=="yes",
				  $this->getValue("se_idfld"),
				  $this->getValue("se_sublevel") === "",
				  $this->getValue("se_case")=="full") ;
      // print "<HR>getQuery1:[$query]";
    } else {
      $query[]=$this->getValue("SE_SQLSELECT");
      // print "<BR><HR>".$this->getValue("se_latest")."/".$this->getValue("se_case")."/".$this->getValue("se_key");
      //  print "getQuery2:[$query]";
    }

    return $query;
  }


  /**
   * @param bool $full set to true if wan't use full text indexing
   */
  function getSqlGeneralFilters($keyword,$latest,$sensitive,$full=false) {
    $filters=array();

    if ($latest == "fixed") {
      $filters[] = "locked = -1";
      $filters[] = "lmodify = 'L'";       
    } else if ($latest == "allfixed") {
      $filters[] = "locked = -1";
    } 
    if ($keyword[0]=='~') {
      $full=false; // force REGEXP
      $keyword=substr($keyword,1);      
    } else if ($keyword[0]=='*') {
      $full=true;// force FULLSEARCH
      $keyword=substr($keyword,1);      
    } 
    if ($full) {
      $this->getFullSqlFilters($keyword,$sqlfilters,$order,$tkeys);
      $filters=array_merge($filters,$sqlfilters);
      $this->setValue("se_orderby",$order);
    } else {
      $op= ($sensitive)?'~':'~*';
      //    $filters[] = "usefor != 'D'";
      $keyword= pg_escape_string($keyword);
      $keyword= str_replace("^","�",$keyword);
      $keyword= str_replace("$","\0",$keyword);
      if (strtolower(substr($keyword,0,5))=="::get") { // only get method allowed
	// it's method call
	$keyword = $this->ApplyMethod($keyword);
	$filters[] = "values $op '$keyword' ";
      } else if ($keyword != "") {
	// transform conjonction
	$tkey=explode(" ",$keyword);
	$ing=false;
	foreach ($tkey as $k=>$v) {
	  if ($ing) {
	    if ($v[strlen($v)-1]=='"') {
	      $ing=false;
	      $ckey.=" ".substr($v,0,-1);
	      $filters[] = "values $op '$ckey' ";	    
	    } else {
	      $ckey.=" ".$v;
	    }
	  } else if ($v[0]=='"') {
	    if ($v[strlen($v)-1]=='"') {	    
	      $ckey=substr($v,1,-1);
	      $filters[] = "values $op '$ckey' ";	  
	    } else {
	      $ing=true;
	      $ckey=substr($v,1);
	    }
	  } else {
	    $filters[] = "values $op '$v' ";	  
	  }
	}
      }
      $this->setValue("se_orderby"," ");
    }   
    return $filters;
  }

  /**
   * return sqlfilters for a simple query in fulltext mode
   * @param string $keyword the word(s) searched
   * @param array &$sqlfilters return array of sql conditions
   * @param string &$sqlorder return sql order by
   * @param string &$fullkeys return tsearch2 keys for use it in headline sql function 
   */
  static function getFullSqlFilters($keyword,&$sqlfilters,&$sqlorder,&$fullkeys) {
    $fullkeys="";
    $sqlorder="";
    $sqlfilters=array("true");
    if ($keyword=="") return;
    $pspell_link = pspell_new("fr","","","iso8859-1",PSPELL_FAST);
    $tstatickeys=explode('"',$keyword);
    $tkeybrut=array();
    $tsearchkeys=array();
    $tkeys=array();
    $sqlfilters=array();
    if (count($tstatickeys) > 2) {
      //each odd
      $keyword="";
      foreach ($tstatickeys as $k=>$v) {
	if (($k%2) == 1) {
	  $tkeybrut[]=$v;
	 
	  $keyword.= " (\"".str_replace(" ","&",trim($v))."\")";
	} else {
	  $keyword.= " ".trim($v);
	}
      }
      
    }

    $keyword=preg_replace('/\s+(OR)\s+/','|',$keyword);
    $tkeys=explode(" ",$keyword);
    
    $sqlfiltersbrut=array();
    foreach ($tkeys as $k=>$key) {
      $key=trim($key);
      if ($key) { 
	$tsearchkeys[$k]=$key;
	if ((!is_numeric($key)) && (strstr($key, '|')===false) && (strstr($key, '&')===false) && (ord($key[0])>47) && (!pspell_check($pspell_link, $key))) {
	  $suggestions = pspell_suggest($pspell_link, $key);
	  $sug=$suggestions[0];
	  //foreach ($suggestions as $k=>$suggestion) {  echo "$k : $suggestion\n";  }
	  if ($sug && (unaccent($sug) != $key) &&  (!strstr($sug,' '))) $tsearchkeys[$k]="$key|$sug";
	} 
	if (strstr($key, '"')!==false) {
	  // add more filter for search complete and exact expression
	  if (strstr($key, '|')===false) {
	    $sqlfiltersbrut[]="svalues ~* '\\\\y".pg_escape_string(str_replace(array('"','&','(',')'),
									      array("",' ','',''),$key))."\\\\y' ";
	  } else {
	    list($left,$right)=explode("|",$key);
	    if (strstr($left,'"')!==false) $q1="svalues ~* '\\\\y".pg_escape_string(str_replace(array('"','&','(',')'),
										      array("",' ','',''),$left))."\\\\y' ";
	    else $q1="";
	    if (strstr($right,'"')!==false) $q2="svalues ~* '\\\\y".pg_escape_string(str_replace(array('"','&','(',')'),
									      array("",' ','',''),$right))."\\\\y' ";
	    else $q2="";
	    $q3="fulltext @@ to_tsquery('fr','".pg_escape_string(unaccent($left))."') ";
	    $q4="fulltext @@ to_tsquery('fr','".pg_escape_string(unaccent($right))."') ";

	    if ((!$q1) && $q2) $sqlfiltersbrut[]="($q4 and $q2) or $q3";
	    elseif ((!$q2) && $q1) $sqlfiltersbrut[]="($q3 and $q1) or $q4";
	    elseif ($q2 && $q1) $sqlfiltersbrut[]="($q3 and $q1) or ($q4 and $q2)";	    
	  }
	}
      }
    }


    if (count($tsearchkeys)>0) {
      $fullkeys='('.implode(")&(",$tsearchkeys).')';  
      $fullkeys=unaccent($fullkeys);
      $fullkeys=pg_escape_string($fullkeys);
      $sqlfilters[]="fulltext @@ to_tsquery('fr','$fullkeys') ";
    }
    if (count($sqlfiltersbrut)>0) $sqlfilters=array_merge($sqlfilters,$sqlfiltersbrut);
    $sqlorder="rank(fulltext,to_tsquery('fr','$fullkeys')) desc";
  }

  
  function ComputeQuery($keyword="",$famid=-1,$latest="yes",$sensitive=false,$dirid=-1, $subfolder=true,$full=false) {
    if ($dirid > 0) {
      if ($subfolder)  $cdirid = getRChildDirId($this->dbaccess, $dirid);
      else $cdirid=$dirid;
      
       
    } else $cdirid=0;

    if ($keyword[0]=='~') {
      $full=false;
      $keyword=substr($keyword,1);      
    } else  if ($keyword[0]=='*') {
      $full=true;
      $keyword=substr($keyword,1);      
    }
    $filters=$this->getSqlGeneralFilters($keyword,$latest,$sensitive,$full);


    $query = getSqlSearchDoc($this->dbaccess, $cdirid, $famid, $filters,false,$latest=="yes",$this->getValue("se_trash"));
    return $query;
  }


  /**
   * return true if the sqlselect is writted by hand
   * @return bool
   */
  function isStaticSql() {
    return (($this->getValue("se_latest") == "") && ($this->getValue("se_case")=="")&& ($this->getValue("se_key")==""));
  }

  function SpecRefresh() {
    $err="";

    if (! $this->isStaticSql()) {
      if (! $this->isParameterizable()) $query=$this->getQuery();
      else $query='select id from only doc where false';
      $err=$this->AddQuery($query);
    }
    return $err;
  }
  function editsearch() {    
    global $action;

    $rtarget=getHttpVars("rtarget");
    $this->lay->set("rtarget",$rtarget);
    $this->lay->set("restrict",false);
    $dirid = GetHttpVars("dirid"); // to set restriction family
    $action->parent->AddJsRef($action->GetParam("CORE_PUBURL")."/FDL/Layout/edittable.js");
    $action->parent->AddJsRef($action->GetParam("CORE_PUBURL")."/FREEDOM/Layout/editdsearch.js");
    $famid=$this->getValue("se_famid");
    $classid=0;
    if ($dirid > 0) {
      $dir = new_Doc($this->dbaccess, $dirid);
      if (method_exists($dir,"isAuthorized")) {	
	if ($dir->isAuthorized($classid)) { 
	  // verify if classid is possible
	  if ($dir->norestrict) $tclassdoc=GetClassesDoc($this->dbaccess, $action->user->id,$classid,"TABLE");
	  else {
	    $tclassdoc=$dir->getAuthorizedFamilies();
	    $this->lay->set("restrict",true);
	  }
	} else  {
	  $tclassdoc=$dir->getAuthorizedFamilies();
	  $first = current($tclassdoc);
	  $famid = $first["id"];
	  $this->lay->set("restrict",true);
	}
      }
      else {
	$tclassdoc = GetClassesDoc($this->dbaccess, $action->user->id,$classid,"TABLE");
      }
    } else {
      $tclassdoc = GetClassesDoc($this->dbaccess, $action->user->id,$classid,"TABLE");
    }

    $this->lay->set("selfam",_("no family"));
    while (list($k,$cdoc)= each ($tclassdoc)) {
      $selectclass[$k]["idcdoc"]=$cdoc["id"];
      $selectclass[$k]["classname"]=$cdoc["title"];
      if ($cdoc["initid"] == $famid) {
	$selectclass[$k]["selected"]="selected";
	$this->lay->set("selfam",$cdoc["title"]);
      } else $selectclass[$k]["selected"]="";
    }
  
    $this->lay->SetBlockData("SELECTCLASS", $selectclass);

    $this->editattr();
  }

  function editspeedsearch() {
    return $this->editsearch();
  }

  /**
   * return document includes in search folder
   * @param bool $controlview if false all document are returned else only visible for current user  document are return
   * @param array $filter to add list sql filter for selected document
   * @param int $famid family identificator to restrict search 
   * @return array array of document array
   */
  function getContent($controlview=true,$filter=array(),$famid="") {
    if ($controlview) $uid=$this->userid;
    else $uid=1;
    $tdoc = getChildDoc($this->dbaccess, $this->initid ,0,"ALL", $filter, $uid, "TABLE",$famid,false,"title",true,$this->getValue("se_trash"));
    return $tdoc;
    
  }
}

?>