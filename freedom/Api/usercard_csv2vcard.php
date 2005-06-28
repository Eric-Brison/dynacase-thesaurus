<?php
/**
 * Generated Header (not documented yet)
 *
 * @author Anakeen 2000 
 * @version $Id: usercard_csv2vcard.php,v 1.5 2005/06/28 08:37:46 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage 
 */
 /**
 */


// remove all tempory doc and orphelines values
include_once("FDL/Class.Doc.php");
include_once("FDL/Class.UsercardVcard.php");


$fimport = GetHttpVars("ifile"); // file to convert
$fvcf = GetHttpVars("ofile","php://stdin"); // output file
$appl = new Application();
$appl->Set("USERCARD",	   $core);


$dbaccess=$appl->GetParam("FREEDOM_DB");
if ($dbaccess == "") {
  print "Freedom Database not found : param FREEDOM_DB";
  exit;
}



  $doc = new_Doc($dbaccess,getFamIdFromName($dbaccess,"USER"));

  $lattr = $doc->GetNormalAttributes();
  $format = "DOC;".$doc->id.";<special id>;<special dirid>; ";

  while (list($k, $attr) = each ($lattr)) {
    $format .= $attr->labelText." ;";
  }

//print_r( $lattr);;

$usercard = new UsercardVcard();

 $fdoc = fopen($fimport,"r");

$deffam = $action->GetParam("DEFAULT_FAMILY", getFamIdFromName($dbaccess,"USER"));

 $usercard->open($fvcf,"w");
  while ($data = fgetcsv ($fdoc, 1000, ";")) {    
    $num = count ($data);
    if ($data[0] != "DOC") continue;
    if ($data[1] != $deffam) continue;
   

    $attr = array();
    reset($data);
    //array_shift($data);array_shift($data);array_shift($data);array_shift($data);
    while (list($k,$v)= each($data)) {
      if ($k > 3) $attr[$lattr[$k-4]->id]=$v;
    }

    $usercard->WriteCard($attr["us_lname"]." ".$attr["us_fname"], $attr);
  }
?>