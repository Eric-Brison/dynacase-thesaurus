<?php
/**
 * Generated Header (not documented yet)
 *
 * @author Anakeen 2000 
 * @version $Id: Class.FormDate.php,v 1.2 2003/08/18 15:46:42 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package WHAT
 * @subpackage CORE
 */
 /**
 */

// ---------------------------------------------------------------------------
// $Id: Class.FormDate.php,v 1.2 2003/08/18 15:46:42 eric Exp $
// $Source: /home/cvsroot/anakeen/freedom/core/Class/Form/Class.FormDate.php,v $
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
// $Log: Class.FormDate.php,v $
// Revision 1.2  2003/08/18 15:46:42  eric
// phpdoc
//
// Revision 1.1  2002/01/08 12:41:34  eric
// first
//
// Revision 1.2  2001/02/16 11:42:16  marianne
// Ajout 'bonne' vieille classe
//
// ---------------------------------------------------------------
//

$CLASS_FORMDATE_PHP = '$Id: Class.FormDate.php,v 1.2 2003/08/18 15:46:42 eric Exp $';

Class FormDate
{
// This class is used to produce HTML/JS code when you want to
// create a separate window which exchange values with its parent
// window (for instance an edit/update window or a query window)
var $formdate='
<input type="hidden" name="[NOM_DATE]" value="[VALEUR_DATE]">
<small>
<select size="1" 
   onChange="checkdate(this.form,this.form.[NOM_DATE],this.form.[NOM_DATE]_day,this.form.[NOM_DATE]_month,this.form.[NOM_DATE]_year, 1999, 2009 );" 
   name="[NOM_DATE]_day">
[BLOCK JOUR]
<option value="[jour]" [selected_jour]>[jour]</option>
[ENDBLOCK JOUR]
</select>
<select size="1" 
   onChange="checkdate(this.form,this.form.[NOM_DATE],this.form.[NOM_DATE]_day,this.form.[NOM_DATE]_month,this.form.[NOM_DATE]_year, 1999, 2009 );" 
   name="[NOM_DATE]_month">
[BLOCK MOIS]
<option value="[val_mois]" [selected_mois]>[mois]</option>
[ENDBLOCK MOIS]
</select>
<select size="1" 
   onChange="checkdate(this.form,this.form.[NOM_DATE],this.form.[NOM_DATE]_day,this.form.[NOM_DATE]_month,this.form.[NOM_DATE]_year, 1999, 2009 );" 
   name="[NOM_DATE]_year">
[BLOCK ANNEE]
<option value="[annee]" [selected_annee]>[annee]</option>
[ENDBLOCK ANNEE]
</select>
';

function FormDate($time,$name) {
  $this->date=	$time;
  $this->name=	$name;
  $this->mois=	strftime("%m",$time);
  $this->jour=	strftime("%d",$time);
  $this->annee=	strftime("%Y",$time);
}

function Get() {
  $lay = new Layout("","",$this->formdate);
  $lay->Set("NOM_DATE",$this->name);
  $lay->Set("VALEUR_DATE",$this->date);

  // Jours
  for ($d=1; $d<=31; $d++) {
    $tab_jour[$d]["jour"]=$d;
    if ($d==$this->jour) {
      $tab_jour[$d]["selected_jour"]="selected";
    } else {
      $tab_jour[$d]["selected_jour"]="";
    }
  }  
  $lay->SetBlockCorresp("JOUR", "jour", "jour"); 
  $lay->SetBlockCorresp("JOUR", "selected_jour", "selected_jour"); 
  $lay->SetBlockData("JOUR",$tab_jour);

  // Mois
  for ($d=1; $d<=12; $d++) {
    $mois=mktime(0,0,0,$d,1,$this->annee); 
    $tab_mois[$d]["mois"]=strftime("%B",$mois);;
    $tab_mois[$d]["val_mois"]=$d;
    if ($d==$this->mois) {
      $tab_mois[$d]["selected_mois"]="selected";
    } else {
      $tab_mois[$d]["selected_mois"]="";
    }
  }  
  $lay->SetBlockCorresp("MOIS", "val_mois", "val_mois"); 
  $lay->SetBlockCorresp("MOIS", "mois", "mois"); 
  $lay->SetBlockCorresp("MOIS", "selected_mois", "selected_mois"); 
  $lay->SetBlockData("MOIS",$tab_mois);

  // Annee
  $annee_deb=$this->annee-10;
  $annee_fin=$this->annee+5;
  for ($d=$annee_deb; $d<=$annee_fin; $d++) {
    $tab_annee[$d]["annee"]=$d;
    if ($d==$this->annee) {
      $tab_annee[$d]["selected_annee"]="selected";
    } else {
      $tab_annee[$d]["selected_annee"]="";
    }
  }  
  $lay->SetBlockCorresp("ANNEE", "annee", "annee"); 
  $lay->SetBlockCorresp("ANNEE", "selected_annee", "selected_annee"); 
  $lay->SetBlockData("ANNEE",$tab_annee);

/*
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
*/
  return($lay->gen()); 
}

// CLASS END
}
?>
