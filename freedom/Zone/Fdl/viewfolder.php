<?php
/**
 * View folder containt
 *
 * @author Anakeen 2003
 * @version $Id: viewfolder.php,v 1.74 2006/11/22 11:13:30 marc Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage 
 */
 /**
 */



include_once("FDL/Lib.Dir.php");
include_once("FDL/Class.DocAttr.php");
include_once("FDL/freedom_util.php");
include_once("FDL/Class.QueryDir.php");

// -----------------------------------
// -----------------------------------
function viewfolder(&$action, $with_abstract=false, $with_popup=true,
		    $column=false,
		    $slice="-",  // view all document (not slice by slice)
		    $sqlfilters=array(),// more filters to see specials doc
		    $famid="")       // folder containt special fam id 
{
  // -----------------------------------


  // Get all the params      
  $dirid=GetHttpVars("dirid"); // directory to see
  $refresh=GetHttpVars("refresh","no"); // force folder refresh
  $startpage=GetHttpVars("page","0"); // page number
  $target=GetHttpVars("target","fdoc"); // target for hyperlinks
  $sqlorder=GetHttpVars("sqlorder","title"); // order sort attribute
  $viewone=(GetHttpVars("viewone","N")=="Y"); // direct view if only one
  if ($slice=="-") $slice=$action->GetParam("FDL_FOLDERMAXITEM",1000);
  $xml = (GetHttpVars("xml")==1 ? true : false ); // directory to see

  // $column = ($with_popup && ($action->getParam("FREEDOM_VIEW")=="column"));
  
  // Set the globals elements


  $baseurl=__xmlentities($action->GetParam("CORE_BASEURL"));
  $action->lay->set("baseurl", $baseurl);
  $standurl=__xmlentities($action->GetParam("CORE_STANDURL"));
  $action->lay->set("standurl", $standurl);
  $dbaccess = $action->GetParam("FREEDOM_DB");

  $action->lay->set("server", getparam("CORE_ABSURL")); 
  $cssf = getparam("CORE_STANDURL")."&app=CORE&action=CORE_CSS&session=".$action->session->id."&layout=FDL:RSS.CSS";
  $action->lay->set("rsscss", $cssf); 
  $action->lay->set("datepub", strftime("%d %b %Y %H:%M:%S %Z",time()));


  $dir = new_Doc($dbaccess,$dirid);

  $dirid=$dir->id;  // use initial id for directories
  $action->lay->set("foldername", $dir->getTitle());
  $distinct=false;

  // control open
  if ($dir->defDoctype=='S') {    
    $aclctrl="execute";
  } else $aclctrl="open";
  if (($err=$dir->Control($aclctrl)) != "") $action->exitError($err);

  
  $action->lay->Set("dirtitle",stripslashes($dir->getTitle()));
  $action->lay->Set("dirid",$dirid);

  $action->parent->AddJsRef($action->GetParam("CORE_JSURL")."/subwindow.js");
  $action->parent->AddJsRef($action->GetParam("CORE_JSURL")."/geometry.js");




  $action->lay->set("VALTERN",($action->GetParam("FDL_VIEWALTERN","yes")=="yes"));


  if ($dirid == "")  {
    $action->exitError(_("cannot see unknow folder"));

  }
  
  if ($startpage>0) {
    $pagefolder = $action->Read("pagefolder");
    $start = $pagefolder[$startpage];
  } else $start=0;


  $terr = getChildDocError($dbaccess, $dirid);
  if (count($terr) > 0) {
    redirect($action,"FDL",
	     "FDL_CARD&id=$dirid",
	     $standurl,true);
  }
  $ldoc = getChildDoc($dbaccess, $dirid,$start,$slice,$sqlfilters,$action->user->id,"TABLE",$famid, 
		      $distinct, $sqlorder);

  

  if ($viewone && (count($ldoc)== 1)) {
    
    if ($ldoc[0]["doctype"]=="D")  redirect($action,"FREEDOM","OPENFOLIO&id=".$ldoc[0]["id"],
					    $standurl);
    else redirect($action,"FDL","FDL_CARD&latest=Y&id=".$ldoc[0]["id"],
		  $standurl);
    exit;
  }

  if ($with_popup) {
    // Set Popup
    include_once("FDL/popup_util.php");
    // ------------------------------------------------------
    // definition of popup menu
    popupInit("popuplist",array('vprop','editdoc','cancel','copy','addbasket','duplicate','ifld','delete'));

  }


  $kdiv=1;
  $tdoc=array();

  $nbseedoc=0;
  if (is_array($ldoc)) {
    

    // get date format 
    if ($action->GetParam("CORE_LANG") == "fr_FR") { // date format depend of locale
      setlocale (LC_TIME, "fr_FR");
      $fdate= ($xml?"%d %b %Y %H:%M:%S %Z":"%d/%m/%y");
    } else {
      $fdate=($xml?"%d %b %Y %H:%M:%S %Z":"%x");
    }
      
    $nbdoc=0;
    $prevFromId = -2;

    if ($column==1) {
      usort($ldoc,"orderbyfromid");
      $tfamdoc=array();
    } else {
      if ((GetHttpVars("sqlorder")=="")&&($slice > 999)) uasort($ldoc,"orderbytitle");
    }

    $doc = createDoc($dbaccess,$famid,false);

    foreach($ldoc as $k=>$zdoc )  {

      if ($with_abstract ) {
	if ($zdoc["fromid"]!=$doc->fromid) {
	  if (!isset($tcdoc[$zdoc["fromid"]]))  {
	    $tcdoc[$zdoc["fromid"]]=createDoc($dbaccess,$zdoc["fromid"],false);	 
	  }
	  $doc=&$tcdoc[$zdoc["fromid"]];
	}
	
      }

     
      $doc->Affect($zdoc,(($column==1)||($with_abstract )));
      
      if ($doc->isConfidential()) continue;
      $nbseedoc++;

      // view control
      //unnecessary now// if ($doc-> Control("view") != "") continue;


      $nbdoc++; // one more visible doc

      $docid=$doc->id;

      $tdoc[$k]["id"] = $docid;
      // search title for freedom item


      $title=$doc->getTitle();
      $tdoc[$k]["title"] = $title;

      if ($doc->doctype =="C" && !$xml) 	$tdoc[$k]["title"] = __xmlentities("<B>". $title ."</B>");

      if (strlen($title) > 20) $tdoc[$k]["abrvtitle"] = substr($title,0,12)." ... ".substr($title,-5);
      else $tdoc[$k]["abrvtitle"] =  $title;

      if (isset($doc->_highlight) && $doc->_highlight!="")  {
	$tdoc[$k]["highlight"] = $doc->_highlight;
      } else $tdoc[$k]["highlight"] = $title;
      $tdoc[$k]["icontitle"] = $tdoc[$k]["highlight"];

      $tdoc[$k]["profid"] = $doc->profid;
      $tdoc[$k]["revdate"] = strftime ($fdate, $doc->revdate);

      $tdoc[$k]["iconsrc"]= __xmlentities($doc->geticon());
  
      $tdoc[$k]["divid"] = $kdiv;

      $tdoc[$k]["locked"] ="";
      $tdoc[$k]["emblem"] = __xmlentities($action->GetImageUrl("1x1.png"));
      $tdoc[$k]["emblemt"] ="";
      $tdoc[$k]["emblemw"] ="0";
      $tdoc[$k]["canedit"] =1;
      $tdoc[$k]["postitid"] = ($doc->postitid>0)?$doc->postitid:false;

	
      
      if ($doc->confidential > 0) {
	$tdoc[$k]["emblem"] = $action->GetImageUrl("confidential.gif");
	$tdoc[$k]["emblemt"] = _("confidential");
	$tdoc[$k]["emblemw"] ="12";
	$tdoc[$k]["canedit"] =false;
	$tdoc[$k]["locked"] = __xmlentities(sprintf("<img src=\"%s\" title=\"%s\" width=\"20px\">",$tdoc[$k]["emblem"],$tdoc[$k]["emblemt"]));
      } else if ($doc->locked == -1) {
	$tdoc[$k]["emblem"] = $action->GetImageUrl("revised.gif");
	$tdoc[$k]["emblemt"] = _("fixed");
	$tdoc[$k]["emblemw"] ="12";
	$tdoc[$k]["canedit"] =false;
	$tdoc[$k]["locked"] = __xmlentities(sprintf("<img src=\"%s\" title=\"%s\" width=\"20px\">",$tdoc[$k]["emblem"],$tdoc[$k]["emblemt"]));
      } else if ((abs($doc->locked) == $action->parent->user->id)) {

	$tdoc[$k]["emblem"] = $action->GetImageUrl("clef1.gif");
	$tdoc[$k]["emblemt"] = _("locked");
	$tdoc[$k]["emblemw"] ="12";
	$tdoc[$k]["locked"] = __xmlentities(sprintf("<img src=\"%s\" title=\"%s\" width=\"20px\">",$tdoc[$k]["emblem"],$tdoc[$k]["emblemt"]));

      } else if ($doc->locked != 0) {
	$tdoc[$k]["emblem"] = __xmlentities($action->GetImageUrl("clef2.gif"));
	$tdoc[$k]["emblemt"] = _("locked");
	$tdoc[$k]["emblemw"] ="12";
	$tdoc[$k]["canedit"] =false;
	$tdoc[$k]["locked"] = __xmlentities(sprintf("<img src=\"%s\" title=\"%s\" width=\"20px\">",$tdoc[$k]["emblem"],$tdoc[$k]["emblemt"]));

      } else if ($doc->control("edit") != "")  {
	$tdoc[$k]["emblem"] = $action->GetImageUrl("nowrite.gif");
	$tdoc[$k]["emblemt"] = _("read-only");
	$tdoc[$k]["emblemw"] ="12";
	$tdoc[$k]["canedit"] =false;
	$tdoc[$k]["locked"] = __xmlentities(sprintf("<img src=\"%s\" title=\"%s\" width=\"20px\">",$tdoc[$k]["emblem"],$tdoc[$k]["emblemt"]));
      }
      //else if ($doc->lmodify == "Y") if ($doc->doctype == 'F') $tdoc[$k]["locked"] = $action->GetIcon("changed2.gif",N_("changed"), 20,20);
	
      $tdoc[$k]["iconsrc"]= __xmlentities($doc->geticon());
	
      if ($with_popup) {
	// ------------------------------
	// define popup accessibility

	popupInvisible("popuplist",$kdiv,'vprop'); // don't use : idem like simple clic
	popupActive("popuplist",$kdiv,'cancel');
	popupActive("popuplist",$kdiv,'copy');
	popupActive("popuplist",$kdiv,'addbasket');
	popupActive("popuplist",$kdiv,'ifld');
	popupActive("popuplist",$kdiv,'duplicate');

	if ($dirid > 0) popupActive("popuplist",$kdiv,'delete');
	else popupInactive("popuplist",$kdiv,'delete');

	if ($doc->doctype=='C') {
	  popupInvisible("popuplist",$kdiv,'editdoc');
	} else {
	  $cud = ($doc->CanLockFile() == "");
	  if ($cud) {
	    popupActive("popuplist",$kdiv,'editdoc');
	  } else {
	    popupInactive("popuplist",$kdiv,'editdoc');
	  }
	}
	  
	if ($dir->defDoctype != 'D') {
	  // it's a search :: inhibit duplicate and suppress reference
	  popupInvisible("popuplist",$kdiv,'duplicate');
	  popupInvisible("popuplist",$kdiv,'delete');	  
	}
      }

      $kdiv++;
      if ($doc->isRevisable()) $tdoc[$k]["revision"]= $doc->revision;
      else $tdoc[$k]["revision"]="";
      if ($doc->wid > 0) $tdoc[$k]["state"]= $action->Text($doc->state);
      else $tdoc[$k]["state"]="";
      
	
	      
      if (($doc->doctype == 'D')||($doc->doctype == 'S')) $tdoc[$k]["isfld"]= "true";
      else $tdoc[$k]["isfld"]= "false";
	
	
      // ----------------------------------------------------------
      //                 ABSTRACT MODE
      // ----------------------------------------------------------
      if ($with_abstract ) {
	if (!$doc->isConfidential()) {
	  // search abstract attribute for freedom item
	  $doc->ApplyMask(); // apply mask attribute
	  if ($with_abstract === 2 ){    
	    $tdoc[$k]["ABSTRACTVALUES"]=getAbstractDetail($doc,$target);	  
	  } else {
	    $tdoc[$k]["ABSTRACTVALUES"]=$doc->viewDoc($doc->defaultabstract,"finfo");	
	    $tdoc[$k]["LOrR"]=($k%2==0)?"left":"right";  
	  }
	} else $tdoc[$k]["ABSTRACTVALUES"]="";
      }
	
      // ----------------------------------------------------------
      //                 COLUMN MODE
      // ----------------------------------------------------------
      if ($column) {
	if ($doc->fromid != $prevFromId) {
	  if (($column==1) || (count($tfamdoc)==0)) {
	    $adoc = $doc->getFamDoc();
	    if (count($tdoc) > 1) {
	      $doct = $tdoc[$k];
	      array_pop($tdoc);
	      $action->lay->SetBlockData("BVAL".$prevFromId, $tdoc);
	      $tdoc=array();

	      $tdoc[$k]=$doct;
	    }
	    $prevFromId=$doc->fromid;
	    $tfamdoc[] = array("iconfamsrc"=>__xmlentities($tdoc[$k]["iconsrc"]),
			       "ftitle"=>$adoc->title,
			       "fid"=>$doc->fromid,
			       "blockattr" => "BATT".$doc->fromid,
			       "blockvalue" => "BVAL".$doc->fromid);
	      
	    // create the TR head 
	    $lattr=$adoc->GetAbstractAttributes();
	    $taname=array();
	    $emptytableabstract=array();
	    while (list($ka,$attr) = each($lattr))  {	
	      $emptytableabstract[$attr->id]["value"]="-";
	      $taname[$attr->id]["aname"]=_($attr->labelText);
	    }
	    $action->lay->SetBlockData("BATT".$doc->fromid,$taname);
	  }
	}
	  

	$tvalues=array();

	if ($doc->isConfidential()) {
	  foreach($lattr as $ka=>$attr)  {	
	    $tvalues[]="x";	    
	  }
	} else {
	  foreach($lattr as $ka=>$attr)  {	
	    //$tvalues[]=$doc->getValue($attr->id,"-");
	    if ($attr->type=="image") $tvalues[]='<img src="'.$doc->getHtmlValue($attr,$doc->getValue($attr->id,"-"),$target).'&height=30"  height="30">';
	    else  $tvalues[]=$doc->getHtmlValue($attr,$doc->getValue($attr->id,"-"),$target);
	  }
	}
	$tdoc[$k]["values"]=implode('</td><td class="tlist">',$tvalues);
	
      }
      //if ($column == 1) $prevFromId=$doc->fromid;
    }
  }

  // Out
  //------------------------------
  // display popup action
  $tboo[0]["boo"]="";
  $action->lay->SetBlockData("VIEWPROP",$tboo);

  $action->lay->Set("nbdiv",$kdiv-1);
  if ($column){
    $action->lay->SetBlockData("BVAL".$prevFromId, $tdoc);
    $action->lay->SetBlockData("TABLEBODY", $tfamdoc);
  } else  $action->lay->SetBlockData("TABLEBODY", $tdoc);

  if ($with_popup) {
    // display popup js
    popupGen($kdiv-1);
  
  }

  if ($with_popup || $column) {
    // js : manage icons
    $licon = new Layout($action->Getparam("CORE_PUBDIR")."/FDL/Layout/manageicon.js", $action);
    $licon->Set("nbdiv",$kdiv-1);
    $action->parent->AddJsCode($licon->gen());
  }

  // when slicing
  $pagefolder[$startpage+1] = $nbseedoc+$start;
  $action->Register("pagefolder",$pagefolder);
  $action->lay->Set("next",$startpage+1);
  $action->lay->Set("prev",$startpage-1);

  $action->lay->Set("nbdoc",$nbdoc);
  $action->lay->Set("wtarget",$target);



  
  return $nbdoc;
}

function orderbyfromid($a, $b) {
  
    if ($a["fromid"] == $b["fromid"]) {
      return strcasecmp($a["title"],$b["title"]);
      return 0;
    }
    if ($a["fromid"] > $b["fromid"]) return 1;
  
  return -1;
}


function orderbytitle($a, $b) {  
   return strcasecmp($a["title"],$b["title"]);
}

function getAbstractDetail(&$doc,$target) {
  $tout=array();
  $lattr=$doc->GetAbstractAttributes();
  $emptytableabstract=array();

  foreach($lattr as $ka=>$attr)  {	
    $val = $doc->GetHtmlAttrValue($ka,$target);

    //$taname[$attr->id]["aname"]=_($attr->labelText);
    if ($val) $tout[] = $val;
  }
  return implode(" - ",$tout);

}

function __xmlentities($string) {
  $xml = (GetHttpVars("xml")==1 ? true : false ); // directory to see
  if (!$xml) return($string);
  else return preg_replace(array('/&/', '/"/', "/'/", '/</', '/>/'), array('&amp;' , '&quot;', '&apos;' , '&lt;' , '&gt;', '&apos;' ), $string);
}

?>
