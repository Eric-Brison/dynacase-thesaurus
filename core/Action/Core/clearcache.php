<?php
/**
 * Generated Header (not documented yet)
 *
 * @author Anakeen 2000 
 * @version $Id: clearcache.php,v 1.2 2003/08/18 15:46:41 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package WHAT
 * @subpackage CORE
 */
 /**
 */

// $Id: clearcache.php,v 1.2 2003/08/18 15:46:41 eric Exp $
// $Log: clearcache.php,v $
// Revision 1.2  2003/08/18 15:46:41  eric
// phpdoc
//
// Revision 1.1  2002/04/15 14:19:59  eric
// ajout clear cache objet
//



function clearcache(&$action) {

  //  session_unset();
      session_unregister("CacheObj");
  
  redirect($action,"CORE","HEAD",$action->GetParam("CORE_STANDURL"));
}

?>
