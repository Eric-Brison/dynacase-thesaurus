<?php

include_once("FDL/Class.Dir.php");
include_once("FDL/Lib.Dir.php");
include_once("EXTERNALS/fdl.php");




// liste des soci�t�s
function lsociety($dbaccess, $name) {
  //'lsociety(D,US_SOCIETY):US_IDSOCIETY,US_SOCIETY,
  global $action;
  $dirid= 0;
  

  $societies =  lfamilly($dbaccess, 124, $name, $dirid, array("fromid=124"));



  $societies +=  lfamilly($dbaccess, 126, $name, $dirid, array("si_idsoc isnull"));
  
  return $societies;
}


// liste des soci�t�s
function lsite($dbaccess, $name) {
  //'lsociety(D,US_SOCIETY):US_IDSOCIETY,US_SOCIETY,

  $dirid= 0;
  

  return lfamilly($dbaccess, 124, $name, $dirid);
  
}

// liste des soci�t�
function laddrsoc($dbaccess, $idc) {
  //'laddrsoc(D,US_IDSOCIETY):US_SOCADDR,US_WORKADDR,US_WORKTOWN,US_WORKPOSTALCODE,US_WORKWEB,US_CEDEX,US_COUNTRY


  $doc = new Doc($dbaccess, $idc);

  if ($doc->isAffected()) {
    $tr[] = array("adresse soci�t�",
		  "yes",
		  $doc->getValue("SI_ADDR"),
		  $doc->getValue("SI_TOWN"),
		  $doc->getValue("SI_POSTCODE"),
		  $doc->getValue("SI_WEB"),
		  $doc->getValue("SI_CEDEX"),
		  $doc->getValue("SI_COUNTRY"));
  }
  
  $tr[] = array("adresse propre",
		  " ",
		  "?",
		  "?",
		  "?",
		  "?",
		  "?",
		  "?");
  
  return $tr;
  
}


// identification soci�t�
function gsociety($dbaccess, $idc) {     
  //gsociety(D,US_IDSOCIETY):US_SOCIETY
  $doc = new Doc($dbaccess, $idc);
  $cl = array($doc->title);

  return ($cl);
  }


// get enum list from society document
function enumscatg() {
  $dbaccess=getParam("FREEDOM_DB");
  $soc = new Doc($dbaccess, 124);

  if ($soc->isAffected()) {
    $a = $soc->getAttribute("si_catg");
    return $a->phpfunc;
  }
  return "";
}


?>
