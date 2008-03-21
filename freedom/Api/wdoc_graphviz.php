<?php
/**
 * Generate worflow graph
 *
 * @author Anakeen 2007
 * @version $Id: wdoc_graphviz.php,v 1.12 2008/03/21 11:12:26 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage 
 */
 /**
  * @global id Http var : document id to affect
  * @global type Http var : type of graph
  * @global format Http var : file format pnh or svg
  * @global orient Http var :orientation TB (TopBottom)  or LR (LeftRight)
  * @global size Http var : global size of graph
 */



// refreah for a classname
// use this only if you have changed title attributes

include_once("FDL/Lib.Attr.php");
include_once("FDL/Class.DocFam.php");

$dbaccess=$appl->GetParam("FREEDOM_DB");
if ($dbaccess == "") {
  print "Freedom Database not found : param FREEDOM_DB";
  exit;
}


$docid = GetHttpVars("docid",0); // special docid
$type = GetHttpVars("type"); // type of graph
$orient = GetHttpVars("orient","LR"); // type of graph
$isize = GetHttpVars("size","10"); // size of graph
$ratio = GetHttpVars("ratio","auto"); // ratio of graph

$label=($type=="complet");
$doc=new_doc($dbaccess,$docid);

$rankdir=$orient;
if ($isize=="auto") $size="";
 else {
   if ($isize=="A4") {     
     $size="size=\"7.6,11!\";"; // A4 whith 1.5cm margin
   } else {
     if (ereg("([0-9\.]+),([0-9\.]+)",$isize, $reg)) {
       $size=sprintf("size=\"%.2f,%.2f!\";",floatval($reg[1])/2.54,floatval($reg[2])/2.54);
     } else {
       $isize=sprintf("%.2f",floatval($isize)/2.54);
       $size="size=\"$isize,$isize!\";";
     }
   }
 }
$statefontsize=13;
$conditionfontsize=12;
$labelfontsize=11;

foreach ($doc->cycle as $k=>$v) {
  $tmain='';
  if (isset($doc->autonext[$v["e1"]]) && ($doc->autonext[$v["e1"]]==$v["e2"])) $tmain='color=darkgreen,style="setlinewidth(3)",arrowsize=1.0';


  if ($label) { 
    $m1=$doc->transitions[$v["t"]]["m1"];
    $m2=$doc->transitions[$v["t"]]["m2"];

    $e1=_($v["e1"]);
    $e2=_($v["e2"]);
    if ($m1) {
      //      if ($tmain) $tmain.=",";
      //      $tmain.="taillabel=$m1";

      $line[]='"'.str_replace(" ","\\n",$m1.$k).'" [ label="'.$m1.'.", fixedsize=false,fontsize='.$conditionfontsize.',shape=diamond,color="'."yellow".'" ];';

      $line[]=sprintf('"%s" -> "%s" [labelfontcolor="#555555",decorate=false, color=darkblue, fontsize='.$labelfontsize.', labelfontname=sans, label="%s"];',
		    str_replace(" ","\\n",$e1),
		      str_replace(" ","\\n",$m1.$k), _($v["t"]));
      $e1=$m1.$k;
      
    }
    if ($m2) {
      $line[]='"'.str_replace(" ","\\n",$m2.$k).'" [ label="'.$m2.'",fixedsize=false,shape=box,color="'."orange".'", fontsize='.$conditionfontsize.' ];';
      $line[]=sprintf('"%s" -> "%s" [labelfontcolor="#555555",decorate=false, color=darkblue, fontsize='.$labelfontsize.',labelfontname=sans];',
		    str_replace(" ","\\n",$e1),
		    str_replace(" ","\\n",$m2.$k));
      $e1=$m2.$k;
      
    }
    $line[]=sprintf('"%s" -> "%s" [labelfontcolor="#555555",decorate=false, color=darkblue, fontsize='.$labelfontsize.',labelfontname=sans,label="%s" %s];',
		    str_replace(" ","\\n",$e1),
		    str_replace(" ","\\n",$e2),
		    _($v["t"]),$tmain);
  } else {
   
    $line[]=sprintf('"%s" -> "%s" [labelfontsize=6,color=darkblue %s];',
		    str_replace(" ","\\n",(_($v["e1"]))),
		    str_replace(" ","\\n",(_($v["e2"]))),$tmain);
  }
  //  $line[]='"'.utf8_encode(_($v["e1"])).'" -> "'.utf8_encode(_($v["e2"])).' [label="'..'";';
}
$line[]='"'.str_replace(" ","\\n",_($doc->firstState)).'" [shape = doublecircle,style=filled, width=1.5, fixedsize=true,fontsize='.$statefontsize.',fontname=sans];';;
$states=$doc->getStates();
foreach ($states as $k=>$v) {
  $color=$doc->getColor($v);
  if ($color)  $line[]='"'.str_replace(" ","\\n",_($v)).'" [fillcolor="'.$color.'" ];';
}

#        page=\"11.6,8.2\";
$ft=str_replace(" ",'\n',_($doc->firstState));

$dot="digraph \"".$doc->title."\" {
        ratio=\"$ratio\";
	rankdir=$rankdir;
        $size
        bgcolor=\"transparent\";
        {rank=1; \"$ft\";}
        splines=false;
	node [shape = circle, style=filled, fixedsize=true,width=1.5,  fontsize=$statefontsize, fontname=sans];\n";



$dot.= implode($line,"\n");
$dot.="\n}";

print utf8_encode($dot);
?>