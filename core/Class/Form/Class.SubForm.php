<?
// ---------------------------------------------------------------------------
// $Id: Class.SubForm.php,v 1.1 2002/01/08 12:41:34 eric Exp $
// $Source: /home/cvsroot/anakeen/freedom/core/Class/Form/Class.SubForm.php,v $
// ---------------------------------------------------------------
//  O   Anakeen - 2000
// O*O  Anakeen Development Team
//  O   dev@anakeen.com
// ---------------------------------------------------------------
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or (at
//  your option) any later version.
//
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
// or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License
// for more details.
//
// You should have received a copy of the GNU General Public License along
// with this program; if not, write to the Free Software Foundation, Inc.,
// 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
// ---------------------------------------------------------------
// $Log: Class.SubForm.php,v $
// Revision 1.1  2002/01/08 12:41:34  eric
// first
//
// Revision 1.3  2000/10/26 12:52:13  yannick
// Bug : perte du mot de passe
//
// Revision 1.2  2000/10/23 14:21:27  marc
// Use of select in forms
//
// Revision 1.1  2000/10/19 16:34:45  yannick
// Pour Marc
//
//

$CLASS_SUBFORM_PHP = '$Id: Class.SubForm.php,v 1.1 2002/01/08 12:41:34 eric Exp $';

Class SubForm
{
// This class is used to produce HTML/JS code when you want to
// create a separate window which exchange values with its parent
// window (for instance an edit/update window or a query window)
var $mainjs='
function submit_withpar(height,width,name,[id],url) {
  subwindow(height,width,name,url+\'&[id]=\'+[id]);
}
';

var $jsmaincall='submit_withpar([height],[width],\'[name]\',\'[id]\',\'[url]\')';

var $mainform='
<form name="[name]" method="POST" action="[url]">
[BLOCK PAR]
  <input type="hidden" name="[name]" value="[val]"> [ENDBLOCK PAR]
</form>
';

var $subjs='
function sendform() {
  var p = self.opener.document.forms.[name];
  var lf = self.document.[name];
[BLOCK PAR]
  p.[name].value = lf.[name].value; [ENDBLOCK PAR]

[BLOCK SEL]
  p.[name].value = lf.[name].options[lf.[name].selectedIndex].value; [ENDBLOCK SEL]
  p.submit();
}';  

var $param = array(); // contains all exchanged vars in the form
                      // "key" => "val" , val is the initial value 
                      // of the key.


function SubForm($name,$width=100,$height=100,$mainurl="",$suburl="") {
  $this->name=$name;
  $this->width=$width;
  $this->height=$height;
  $this->mainurl=$mainurl;
  $this->suburl=$suburl;
}

function SetParams($array) {
  $this->param=array_merge($array,$this->param);
}

function SetParam($key,$val="",$type="") {
  $this->param[$key]["val"]=$val;
  $this->param[$key]["typ"]=$type;
}

function SetKey($key) {
  $this->key = $key;
}

function GetMainForm() {
  $lay = new Layout("","",$this->mainform);
  $tab=array();
  reset($this->param);
  $c = -1;
  while (list($k,$v) = each($this->param)) {
    $tab[$c]["name"]=$k;
    $tab[$c]["val"]=$v["val"];
    $c++;
  }
  $lay->SetBlockData("PAR",$tab);
  $lay->Set("url",$this->mainurl);
  $lay->Set("name",$this->name);
  return($lay->gen());
}
  

function GetMainJs() {
  $lay = new Layout("","",$this->mainjs);
  $lay->Set("formname",$this->name);
  $lay->Set("id",$this->key);
  return($lay->gen()); 
}

function GetSubJs() {
  $lay = new Layout("","",$this->subjs);
  $tab=array();
  reset($this->param);
  $isel = $c = -1;
  while (list($k,$v) = each($this->param)) {
    if ($v["typ"] == "sel" ) {
      $isel++;
      $tabsel[$isel]["name"] = $k;
    } else {
      $c++;
      $tab[$c]["name"]=$k;
    }
  }
  if ($isel>-1) {
    $lay->SetBlockData("SEL",$tabsel);
  } else {
    $lay->SetBlockData("SEL",NULL);
  }
  if ($c>-1) {
    $lay->SetBlockData("PAR",$tab);
  } else {
    $lay->SetBlockData("PAR",NULL);
  }
  $lay->Set("name",$this->name);
  return($lay->gen()); 
}

function GetLinkJsMainCall() {
  $lay = new Layout("","",$this->jsmaincall);
  $lay->Set("url",$this->suburl);
  $lay->Set("width",$this->width);
  $lay->Set("height",$this->height);
  $lay->Set("name",$this->name);
  return($lay->gen());
}

function GetEmptyJsMainCall() {
  $lay = new Layout("","",$this->jsmaincall);
  $lay->Set("id","");
  $lay->Set("url",$this->suburl);
  $lay->Set("width",$this->width);
  $lay->Set("height",$this->height);
  $lay->Set("name",$this->name);
  return($lay->gen());
}

// CLASS END
}
?>
