<?php
/**
 * Utilities functions for freedom
 *
 * @author Anakeen 2004
 * @version $Id: Lib.Util.php,v 1.19 2008/04/25 09:18:15 jerome Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 * @subpackage 
 */
 /**
 */

function newFreeVaultFile($dbaccess) {
  include_once("VAULT/Class.VaultFile.php");
  return new VaultFile($dbaccess, strtoupper(getDbName($dbaccess)));
}
function getGen($dbaccess) {
  $freedomenv = getenv("freedomenv");
  if ($freedomenv != "" ) {
    return "GEN/".$freedomenv;
  }
  return "GEN/default";
}



/**
 * convert French date to iso8601
 * @param string $fdate DD/MM/YYYY HH:MM:SS (CET)
 * @param string $wtz with timezone add time zone in the end if true
 * @return string date YYYY-MM-DD HH:MM:SS
 */
function toIso8601($fdate,$wtz=false) {
  $isoDate="";
  if (preg_match("/^(\d\d)\/(\d\d)\/(\d\d\d\d)\s(\d\d)?:?(\d\d)?:?(\d\d)?\s+?(\w+)?$/", $fdate,$reg)) {   
    $isoDate=sprintf("%04d-%02d-%02d %02d:%02d:%02d",
		     $reg[3],$reg[2],$reg[1],$reg[4],$reg[5],$reg[6]);
    if ($reg[8]!="") $tz=$reg[7];
  }
    // ISO 8601
  if ($wtz && $tz) $isoDate.=" ".$tz;

  return $isoDate;
}

function StringDateToJD($sdate) {
  $jd=FrenchDateToJD($sdate);
  if ($jd === false)  $jd=Iso8601ToJD($sdate);
  return $jd;
}

/**
 * convert French date to Julian day
 * the seconds are ignored
 * @param string $fdate DD/MM/YYYY HH:MM
 * @return float julian day (return false if incorrect date)
 */
function FrenchDateToJD($fdate) { 
if (preg_match("/^(\d\d)\/(\d\d)\/(\d\d\d\d)\s?(\d\d)?:?(\d\d)?:?(\d\d)?\s?(\w+)?$/", $fdate,$reg)) {   
   return cal2jd("CE",  $reg[3], $reg[2], $reg[1], $reg[4],$reg[5] , 0 );
  }
 return false;
}

/**
 * convert French date to unix timestamp
 * date must be > 01/01/1970 and < 2038
 * @param string $fdate DD/MM/YYYY HH:MM
 * @return float number of second since epoch (return -1 if incorrect date)
 */
function FrenchDateToUnixTs($fdate) {
  if (preg_match("/^(\d\d)\/(\d\d)\/(\d\d\d\d)\s?(\d\d)?:?(\d\d)?:?(\d\d)?\s?(\w+)?$/", $fdate,$r)) {   
    $dt = mktime($r[4], $r[5], $r[6], $r[2], $r[1], $r[3]);
  } else {
    $dt = -1;
  }
  return $dt;
}
/**
 * convert iso8601 date to Julian day
 * the seconds are ignored
 * @param string $isodate YYYY-MM-DD HH:MM
 * @return float julian day (return false if incorrect date)
 */
function Iso8601ToJD($isodate) {
 if (preg_match("/^(\d\d\d\d)-(\d\d)-(\d\d)\s?(\d\d)?:?(\d\d)?:?(\d\d)?\s?(\w+)?$/",$isodate ,$reg)) {   
   return cal2jd("CE",  $reg[1], $reg[2], $reg[3], $reg[4],$reg[5] , 0 );
  }
 return false;
}


function cal2jd( $era, $y, $m, $d, $h, $mn, $s ) {
  if (($y>1969) && ($y<2038) ) {
    $nd=unixtojd(mktime($h,$mn,$s,$m,$d,$y));
    $nm=(($h*60+$mn)-720)/1440;
    $nd+=round($nm,5);
    return $nd;
  } else {	
    
    if( $y == 0 ) {
      AddWarningMsg("There is no year 0 in the Julian system!");
      return "invalid";
    }
    if( $y == 1582 && $m == 10 && $d > 4 && $d < 15 && $era != "BCE" ) {
      AddWarningMsg("The dates 5 through 14 October, 1582, do not exist in the Gregorian system!");
      return "invalid";
    }

    if( $era == "BCE" ) $y = -$y + 1;
	if( $m > 2 ) {
		$jy = $y;
		$jm = $m + 1;
	} else {
		$jy = $y - 1;
		$jm = $m + 13;
	}

	$intgr = floor( floor(365.25*$jy) + floor(30.6001*$jm) + $d + 1720995 );

	//check for switch to Gregorian calendar
	$gregcal = 15 + 31*( 10 + 12*1582 );
	if( $d + 31*($m + 12*$y) >= $gregcal ) {
		$ja = floor(0.01*$jy);
		$intgr += 2 - $ja + floor(0.25*$ja);
	}

	//correct for half-day offset
	$dayfrac = $h/24.0 - 0.5;
	if( $dayfrac < 0.0 ) {
		$dayfrac += 1.0;
		$intgr--;
	}

	//now set the fraction of a day
	$frac = $dayfrac + ($mn + $s/60.0)/60.0/24.0;

    //round to nearest second
    $jd0 = ($intgr + $frac)*100000;
    $jd  = floor($jd0);
    if( $jd0 - $jd > 0.5 ) $jd++;
    return $jd/100000;
    
  }
  return "Date Error";
}

/**
 * return the day of the week (1 id Monday, 7 is Sunday)
 * @param float $jd julian date
 * @return int
 */
function jdWeekDay($jd) {
    //weekday
    
  $t  = doubleval($jd) + 0.5;
  $wd = floor( ($t/7 - floor($t/7))*7 + 0.000000000317 );   //add 0.01 sec for truncation error correction
  return $wd+1;
}


/**
 * return the number of the week in year
 * @param float $jd julian date
 * @return int between 1 and 53
 */
function jdWeekNumber($jd) {
    $j  = doubleval($jd) + 0.5;
    $d4 = ((($j + 31741 - ($j % 7)) % 146097) % 36524) % 1461;
    $l  = floor($d4 / 1460);
    $d1 = (($d4 - $l) % 365) + $l;
    $wn = floor($d1 / 7) + 1;
    return($wn);
}

/**
 * return date in string format
 * @param float $jd julian date
 * @param string $dformat the format (default iso8601)
 * @return string the formatted date
 */
function jd2cal( $jd,$dformat='' ) {


  //
  // get the date from the Julian day number
  //
   $intgr   = floor($jd);
   $frac    = $jd - $intgr;
   $gregjd  = 2299160.5;
  if( $jd >= $gregjd ) {				//Gregorian calendar correction
     $tmp = floor( ( ($intgr - 1867216.0) - 0.25 ) / 36524.25 );
    $j1 = $intgr + 1 + $tmp - floor(0.25*$tmp);
  } else
    $j1 = $intgr;

  //correction for half day offset
  $df = $frac + 0.5;
  if( $df >= 1.0 ) {
    $df -= 1.0;
    $j1++;
  }

  $j2 = $j1 + 1524.0;
  $j3 = floor( 6680.0 + ( ($j2 - 2439870.0) - 122.1 )/365.25 );
  $j4 = floor($j3*365.25);
  $j5 = floor( ($j2 - $j4)/30.6001 );

  $d = floor($j2 - $j4 - floor($j5*30.6001));
  $m = floor($j5 - 1.0);
  if( $m > 12 ) $m -= 12;
  $y = floor($j3 - 4715.0);
  if( $m > 2 )   $y--;
  if( $y <= 0 )  $y--;

  //
  // get time of day from day fraction
  //
  $hr  = floor($df * 24.0);
  $mn  = floor(($df*24.0 - $hr)*60.0);
  $f  = (($df*24.0 - $hr)*60.0 - $mn)*60.0;
  $sc  = floor($f);
  $f -= $sc;
  if( $f > 0.5 ) $sc++;
  if( $sc == 60 ) {
    $sc = 0;
    $mn++;
  }
  if( $mn == 60 )  {
    $mn = 0;
    $hr++;
  }
  if( $hr == 24 )  {
    $hr = 0;
    $d++;            //this could cause a bug, but probably will never happen in practice
  }

  if( $y < 0 ) {
    $y = -$y;
    $ce=' BCE';
    // form.era[1].checked = true;
  } else {
    $ce='';
    //   form.era[0].checked = true;
  }
  switch ($dformat) {
  case 'M':
    $retiso8601=$m;
    break;
  case 'Y':
    $retiso8601=$y;
    break;
  case 'd':
    $retiso8601=$d;
    break;
  case 'French':
    $retiso8601=sprintf("%02d/%02d/%04s",$d,$m,$y);
    break;
  case 'FrenchLong':
    $retiso8601=sprintf("%02d/%02d/%04s %02d:%02d %s",$d,$m,$y,$hr,$mn,$ce);
    break;
  default:
    $retiso8601=sprintf("%04d-%02d-%02s %02d:%02d%s",
			$y,$m,$d,$hr,$mn,$ce);
  }
  return $retiso8601;
}



/**
 * backslash quote and replace double-quote by html entity
 */
function addJsSlashes($s) {
  return str_replace(array("'","\""),array("\\'","&#34;"),$s);
}

/**
 * Remove Character Accents
 * Replaces accented characters in a string with their unaccented versions, for instance, converts "ÉéÜüÄäÖ" into "EeUuAaO". The function will handle any accented character for which there exists an HTML entity in PHP's translation table (i.e. pretty much any and all characters). Credits go to jennings at trad dot uji dot es for the original version of this incredibly useful little function. I used this function to good effect in OpenSEF.
 */
function unaccent_($text) {
  static $search, $replace;
  if (!$search) {
    $search = $replace = array();
    // Get the HTML entities table into an array
    $trans = get_html_translation_table(HTML_ENTITIES);
    // Go through the entity mappings one-by-one
    foreach ($trans as $literal => $entity) {
      // Make sure we don't process any other characters
      // such as fractions, quotes etc:
      if (ord($literal) >= 192) {
        // Get the accented form of the letter
        $search[] = $literal;
        // Get e.g. 'E' from the string '&Eacute'
        $replace[] = $entity[1];
      }
    }
  }
  return str_replace($search, $replace, $text);
}
function unaccent($string) {
   $string = strtr($string,
       "\xA1\xAA\xBA\xBF\xC0\xC1\xC2\xC3\xC5\xC7
       \xC8\xC9\xCA\xCB\xCC\xCD\xCE\xCF\xD0\xD1
       \xD2\xD3\xD4\xD5\xD8\xD9\xDA\xDB\xDD\xE0
       \xE1\xE2\xE3\xE5\xE7\xE8\xE9\xEA\xEB\xEC
       \xED\xEE\xEF\xF0\xF1\xF2\xF3\xF4\xF5\xF8
       \xF9\xFA\xFB\xFD\xFF",
       "!ao?AAAAAC
       EEEEIIIIDN
       OOOOOUUUYa
       aaaaceeeei
       iiidnooooo
       uuuyy"); 
   $string = strtr($string, array("\xC4"=>"Ae", "\xC6"=>"AE", "\xD6"=>"Oe", "\xDC"=>"Ue", "\xDE"=>"TH", "\xDF"=>"ss", "\xE4"=>"ae", "\xE6"=>"ae", "\xF6"=>"oe", "\xFC"=>"ue", "\xFE"=>"th"));
   return($string);
}

function unaccent_utf8($string) {
  return iconv("UTF-8","ASCII//TRANSLIT",$string);
}

/**
 * replace a string separate by $sep
 */
function sep_replace($ak,$idx,$by="-",$sep="\n") {
  $endoff=-1;
  do {
    $offset=$endoff+1;
    $endoff=strpos($ak,"\n",$offset);
    $idx--;
  } while (($idx>=0)&&($endoff!==false));

  if ($idx>=0) return $ak.str_repeat("\n-",$idx)."\n$by";
  else if ($endoff == false) {
    if ($offset==0) return "$by";
    else return substr($ak,0,$offset-1)."\n$by";
  }  else return substr($ak,0,$offset)."$by\n".substr($ak,$endoff+1);	
					 
}
?>
