<?php
/**
 * Main program to activate action in WHAT software
 *
 * All HTTP requests call index.php to execute action within application
 *
 * @author Anakeen 2000 
 * @version $Id: index.php,v 1.22 2004/01/13 09:05:58 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package WHAT
 * @subpackage 
 */
 /**
 */

// ---------------------------------------------------------------
// $Id: index.php,v 1.22 2004/01/13 09:05:58 eric Exp $
// $Source: /home/cvsroot/anakeen/freedom/core/index.php,v $
// ---------------------------------------------------------------
//  O   Anakeen - 2001
// O*O  Anakeen development team
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

#
# This is the main body of App manager
# It is used to launch application and 
# function giving them all necessary environment
# element
#
#
// First control
if(!isset($PHP_AUTH_USER)  ) {
  Header("Location:guest.php");
  exit;
}

include_once('Class.Action.php');
include_once('Class.Application.php');
include_once('Class.Session.php');
include_once('Lib.Http.php');
include_once('Class.Log.php');
include_once('Class.Domain.php');
include_once('Class.DbObj.php');

define("PORT_SSL", 443); // the default port for https
// ----------------------------------------
// pre include for session cache
if (file_exists($HTTP_GET_VARS["app"]."/include.php")) {
        include($HTTP_GET_VARS["app"]."/include.php");
}

$log=new Log("","index.php");

$CoreNull = "";
global $CORE_LOGLEVEL;

global $HTTP_GET_VARS;
if (!isset($HTTP_GET_VARS["app"])) $HTTP_GET_VARS["app"]="CORE";
if (!isset($HTTP_GET_VARS["action"])) $HTTP_GET_VARS["action"]="";


$standalone = GetHttpVars("sole");

if (isset($_COOKIE['session'])) $sess_num= $_COOKIE['session'];
else $sess_num=GetHttpVars("session");//$HTTP_GET_VARS["session"];

$session=new Session();
if (!  $session->Set($sess_num))  {
    print "<B>:~((</B>";
    exit;
  };



$core = new Application();
$core->Set("CORE",$CoreNull,$session);

if ($core->user->login != $PHP_AUTH_USER) {
  // reopen a new session
  $session->Set("");
  $core->SetSession($session);
}
//$core->SetSession($session);

$CORE_LOGLEVEL=$core->GetParam("CORE_LOGLEVEL", "IWEF");

// ----------------------------------------
// Init PUBLISH URL from script name
global $SERVER_NAME;
global $SCRIPT_NAME;
global $SERVER_PORT;

if (ereg("(.*)/index\.php", $SCRIPT_NAME, $reg)) {

  // determine publish url (detect ssl require)
 
  if ($SERVER_PORT != PORT_SSL)   $puburl = "http://".$SERVER_NAME.":".$SERVER_PORT.$reg[1];
  else $puburl = "https://".$SERVER_NAME.":".$SERVER_PORT.$reg[1];
} else {
  // it is not allowed
  print "<B>:~(</B>";
  exit;
}


$core->SetVolatileParam("CORE_PUBURL", "."); // relative links
$core->SetVolatileParam("CORE_ABSURL", $puburl); // absolute links
$core->SetVolatileParam("CORE_JSURL", "WHAT/Layout");





$core->SetVolatileParam("CORE_ROOTURL", "index.php?sole=R&");
$core->SetVolatileParam("CORE_BASEURL", "index.php?sole=A&");
$core->SetVolatileParam("CORE_SBASEURL", "index.php?sole=A&session={$session->id}&");
$core->SetVolatileParam("CORE_STANDURL","index.php?sole=Y&");


// ----------------------------------------
// Init Application & Actions Objects
if (($standalone == "") || ($standalone == "N")) {
  $action = new Action();
  $action->Set("MAIN",$core,$session);
} else {
  $appl = new Application();
  $appl->Set($HTTP_GET_VARS["app"],$core);

  if (($appl->machine != "") && ($SERVER_NAME != $appl->machine)) { // special machine to redirect    
      $puburl = "http://".$appl->machine.$REQUEST_URI;

      Header("Location: $puburl");
      exit;
  }

  // ----------------------------------------
    // test SSL mode needed or not
    // redirect if needed
  if ($appl->ssl == "Y") {
    if ($SERVER_PORT != PORT_SSL) {
      global $REQUEST_URI;   

      // redirect to go to ssl http
      $sslurl = "https://${SERVER_NAME}:${SERVER_PORT}${REQUEST_URI}";
      Header("Location: $sslurl");
      exit;
    }     
    
    $core->SetVolatileParam("CORE_BGCOLOR", $core->GetParam("CORE_SSLBGCOLOR"));
  } else {
    if ($SERVER_PORT == PORT_SSL) {
      global $REQUEST_URI;   

      // redirect to  suppress ssl http
      $puburl = "http://${SERVER_NAME}:${SERVER_PORT}${REQUEST_URI}";

      Header("Location: $puburl");
      exit;
    }
  }

  
  // -----------------------------------------------
    // now we are in correct protocol (http or https)

  $action = new Action();
  $action->Set($HTTP_GET_VARS["action"],$appl,$session);

}

$nav=$HTTP_USER_AGENT;
$pos=strpos($nav,"MSIE");
if ($action->Read("navigator","") == "") {
  if ( $pos>0) {
    $action->Register("navigator","EXPLORER");
    if (ereg("MSIE ([0-9.]+).*",$nav,$reg)) {
      $action->Register("navversion",$reg[1]);      
    }
  } else {
    $action->Register("navigator","NETSCAPE");
    if (ereg("([a-zA-Z]+)/([0-9.]+).*",$nav,$reg)) {
      $action->Register("navversion",$reg[2]);      
    }
  }
}
// init for gettext
setlocale(LC_MESSAGES,$action->Getparam("CORE_LANG"));  
setlocale(LC_MONETARY, $action->Getparam("CORE_LANG"));
setlocale(LC_TIME, $action->Getparam("CORE_LANG"));
//print $action->Getparam("CORE_LANG");
putenv ("LANG=".$action->Getparam("CORE_LANG")); // needed for old Linux kernel < 2.4
bindtextdomain ("what", "/home/httpd/what/locale");
textdomain ("what");

  
  $action->log->debug("gettext init for ".$action->parent->name.$action->Getparam("CORE_LANG"));

if (($standalone == "Y") || ($standalone == "N") || ($standalone == ""))
{
  echo ($action->execute ());
} 
else 
  if ($standalone == "R")
    {      
      $app = GetHttpVars("app","CORE");
      $act = GetHttpVars("action","");

      // compute others argument to propagate to redirect url
      global $HTTP_GET_VARS;
      $getargs="";
      while (list($k, $v) =each($HTTP_GET_VARS)) {
	if ( ($k != "session") &&
	     ($k != "app") &&
	     ($k != "sole") &&
	     ($k != "action") )
	$getargs .= "&".$k."=".$v;
      }
      redirect($action, "CORE", "MAIN&appd=${app}&actd=${act}".urlencode($getargs),$action->GetParam("CORE_STANDURL"));
    }
  else
    if ($standalone == "A")
      {
	
	if ((isset ($appl)) && ( $appl->with_frame != "Y" ))
	  {  
	    // This document is not completed : does not contain header and footer

	    // HTML body result
	    // achieve action
	    $body = ($action->execute ());
	    // write HTML header
	    $head = new Layout($action->GetLayoutFile("htmltablehead.xml"),$action);
	    // copy JS ref & code from action to header
	    $head->jsref = $action->parent->GetJsRef();
	    $head->jscode = $action->parent->GetJsCode();
	    
	    echo($head->gen());
	    // write HTML body
	    echo ($body);
	    // write HTML footer
	    $foot = new Layout($action->GetLayoutFile("htmltablefoot.xml"),$action);
	    echo($foot->gen());
	  }
	else
	  {
	    // This document is completed 
	    echo ($action->execute ());
	  }
	  
      }


?>
