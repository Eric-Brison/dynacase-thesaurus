<?php
/**
 * Folder managing
 *
 * @author Anakeen 2001
 * @version $Id: Class.QueryDir.php,v 1.25 2007/05/24 15:01:45 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM
 */
 /**
 */


include_once("Class.DbObj.php");
include_once("Class.QueryDb.php");
include_once("Class.Log.php");

  
/**
 * Folder managing 
 * @package FREEDOM
 *
 */
Class QueryDir extends DbObj
{
  public $fields = array ( "dirid","query","childid","qtype","fromid","doctype");
  /*public $sup_fields= array("fromid",
   "doctype"); */ // not be in fieldsset by trigger 
  public $id_fields = array ("dirid","childid");

  var $dbtable = "fld";

  var $order_by="dirid";

  var $fulltextfields = array ("");

  var $sqlcreate = "
create table fld ( 
                    dirid   int not null,
                    query   text,
                    childid   int,
                    qtype   char,
                    fromid int,
                    doctype char
                   );
create index fld_iqd on fld(qtype,dirid);
create index fld_iqc on fld(qtype,childid);
create unique index fld_u on fld(qtype,dirid,childid);
create sequence seq_id_fld start 100;
CREATE TRIGGER tfldfrom before insert on fld FOR EACH ROW execute procedure fromfld();";

#CREATE TRIGGER tfldrel after insert or update or delete on fld FOR EACH ROW execute procedure relfld();";



  function PreInsert()   {
      // test if not already exist 
      if ($this->qtype != "M") {
	$this->delete(false); // delete before insert
      }     
    } 
  function Exists()   {
    // test if  already exist 
    if ($this->qtype != "M") {
      $err = $this->exec_query(sprintf("select * from fld where dirid=%s and childid=%s",
				       $this->dirid,$this->childid));
      if ($this->numrows() > 0) {
	return true; // just to say it is not a real error
      }
      return false;
    }
  }
 
}
?>
