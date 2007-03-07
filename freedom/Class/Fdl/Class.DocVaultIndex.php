<?php
/**
 * Class.DocVaultIndex.php manages a full index
 * for files attached to a Freedom document
 *
 * @author Anakeen 2000 
 * @version $Id: Class.DocVaultIndex.php,v 1.8 2007/03/07 18:42:24 eric Exp $
 * @license http://license.i-cesam.com/license.php
 * @package FREEDOM
 */
 /**
 */

include_once('Class.DbObj.php');
include_once('Class.QueryDb.php');
include_once('Class.Log.php');


Class DocVaultIndex extends DbObj
{
  var $fields = array ( "docid","vaultid");

  var $id_fields = array ("docid", "vaultid");

  var $dbtable = "docvaultindex";

  var $order_by="docid";

  var $sqlcreate = "
create table docvaultindex ( docid  int not null,
                             vaultid int not null
                   ); 
create unique index idx_docvaultindex on docvaultindex (docid, vaultid);";

  /**
   * return doc ids from a vault file 
   * @param id $vid vault id
   * @return array object
   */
  function getDocIds($vid) {
    $t = array();
    $query = new QueryDb($this->dbaccess, "DocVaultIndex");
    $query->basic_elem->sup_where=array ("vaultid = $vid");
    $t = $query->Query();

    return $t;
  } 
  /**
   * return first doc id from a vault file 
   * @param id $vid vault id
   * @return int id of document
   */
  function getDocId($vid) {
    $t = array();
    $query = new QueryDb($this->dbaccess, "DocVaultIndex");
    $query->AddQuery("vaultid = $vid");
    $t = $query->Query(0,1,"TABLE");
    if (is_array($t))  return $t[0]["docid"];
    return false;
  } 
  /**
   * return vault ids for a document
   * @param id $docid document id
   * @return array
   */
  function getVaultIds($docid) {
    $t = array();
    if (!$docid) return array();
    $query = new QueryDb($this->dbaccess, "DocVaultIndex");
    $query->AddQuery("docid = $docid");
    $t = $query->Query(0,0,"TABLE");
    $tvid=array();
    if (is_array($t)) {
      foreach ($t as $tv) {
	$tvid[]=$tv["vaultid"];
      }
    }
    return $tvid;
  }

  function DeleteDoc($docid) {
      $err=$this->exec_query("delete from ".$this->dbtable." where docid=".$docid  );
      return $err;
  }
  
  function DeleteVaultId($vid) {
      $err=$this->exec_query("delete from ".$this->dbtable." where vaultid=".$vid  );
      return $err;
  }

}
?>
