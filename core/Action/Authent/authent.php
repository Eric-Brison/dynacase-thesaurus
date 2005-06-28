<?php
/**
 * Re-authenticate a user
 * Send a 401 Unauthorized HTTP header to force re-authentification or by redirect to index.php
 * which need also an authentification
 *
 * @author Anakeen 2003
 * @version $Id: authent.php,v 1.13 2005/06/28 13:53:24 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package WHAT
 * @subpackage CORE
 */
/**
 */
include_once('Class.User.php');



/**
 * Send a 401 Unauthorized HTTP header
 */
function authenticate() {
  //   Header( "WWW-Authenticate: Basic realm=\"WHAT Connection\", stale=FALSE");
  //Header( "WWW-Authenticate: Basic realm=\"WHAT Connection\", stale=true");
  //Header( "HTTP/1.0 401 Unauthorized");
  
  $CoreNull = "";
  $core = new Application();
  $core->Set("CORE",$CoreNull);
  $action = new Action();
  $action->Set("",$core);

  header('WWW-Authenticate: Basic realm="'.$action->getParam("CORE_REALM","WHAT Connection").'"');
  header('HTTP/1.0 401 Unauthorized');
  // Header("Location:guest.php");
  echo _("Vous devez entrer un nom d'utilisateur valide et un mot de passe correct pour acceder a cette ressource");
  exit;
}

global $_SERVER;
global $_POST;



if(!isset($_SERVER['PHP_AUTH_USER']) || ($_POST["SeenBefore"] == 1 && !strcmp($_POST["OldAuth"],$_SERVER['PHP_AUTH_USER'] )) ) {

  authenticate();
}
else {
  Header("Location: http://".$_SERVER['SERVER_NAME'].":".$_SERVER['SERVER_PORT']."/what/index.php?sole=R");
  exit;
}
?>
