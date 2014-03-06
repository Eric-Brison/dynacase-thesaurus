<?php
/*
 * thesaurus Library
 *
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package THESAURUS
*/

include_once ("FDL/Class.SearchDoc.php");
include_once ("FDL/Class.DocCount.php");
/**
 * return concept document from URI reference
 * @param string $dbaccess database coordinates
 * @param string $uri URI to find
 * @return Doc document find - false if not find
 */
function getConceptFromURI($dbaccess, $uri)
{
    $s = new SearchDoc($dbaccess, "THCONCEPT");
    //  $s->addFilter("thc_thesaurus=".$this->getValue("thc_thesaurus"));
    $s->addFilter("thc_uri='%s'", $uri);
    $s->setObjectReturn();
    $s->overrideViewControl();
    $t = $s->search();
    if ($s->count() == 1) return $s->getNextDoc();
    return false;
}
/**
 * return concept Id from URI reference
 * @param string $dbaccess database coordinates
 * @param string $uri URI to find
 * @return int document identificator find - false if not find
 */
function getConceptIdFromURI($dbaccess, $uri)
{
    $s = new SearchDoc($dbaccess, "THCONCEPT");
    //  $s->addFilter("thc_thesaurus=".$this->getValue("thc_thesaurus"));
    $s->addFilter("thc_uri='%s'", $uri);
    
    $s->overrideViewControl();
    $t = $s->search();
    if ($s->count() == 1) return $t[0]["initid"];
    return false;
}
/**
 * return thesaurus document from URI reference
 * @param string $dbaccess database coordinates
 * @param string $uri URI to find
 * @return Doc document find - false if not find
 */
function getThesaurusFromURI($dbaccess, $uri)
{
    $s = new SearchDoc($dbaccess, "THESAURUS");
    //  $s->addFilter("thc_thesaurus=".$this->getValue("thc_thesaurus"));
    $s->addFilter("thes_uri='%s'", $uri);
    $s->setObjectReturn();
    $s->overrideViewControl();
    $t = $s->search();
    if ($s->count() == 1) return $s->getNextDoc();
    return false;
}
/**
 * return localized concept in a language document from concept
 * @param string $dbaccess database coordinates
 * @param int $idc document identificator of concept
 * @param string $lang language to find
 * @return Doc document find - false if not find
 */
function getLangConcept($dbaccess, $idc, $lang)
{
    $s = new SearchDoc($dbaccess, "THLANGCONCEPT");
    //  $s->addFilter("thc_thesaurus=".$this->getValue("thc_thesaurus"));
    $s->addFilter("thcl_thconcept='%s'", $idc);
    $s->addFilter("thcl_lang='%s'", $lang);
    
    $s->setObjectReturn();
    $s->overrideViewControl();
    $t = $s->search();
    
    if ($s->count() == 1) return $s->getNextDoc();
    return false;
}
/**
 * return all localized concept document from concept
 * @param string $dbaccess database coordinates
 * @param int $idc document identificator of concept
 * @return array of document values
 */
function getLangConcepts($dbaccess, $idc)
{
    $s = new SearchDoc($dbaccess, "THLANGCONCEPT");
    //  $s->addFilter("thc_thesaurus=".$this->getValue("thc_thesaurus"));
    $s->addFilter("thcl_thconcept='%s'", $idc);
    $s->overrideViewControl();
    
    $t = $s->search();
    return $t;
}
/**
 * return all  concept of thesaurus <= level
 * @param string $dbaccess database coordinates
 * @param int $idt thesaurus identificator of concept
 * @param int $level level : 0 is top level
 * @return array of document values
 */
function getConceptsLevel($dbaccess, $idt, $level)
{
    $s = new SearchDoc($dbaccess, "THCONCEPT");
    $s->addFilter("thc_thesaurus='%d'", $idt);
    $s->addFilter("thc_level <= %d", $level);
    $s->overrideViewControl();
    
    $t = $s->search();
    return $t;
}
/**
 * return all  concept of thesaurus <= level
 * @param string $dbaccess database coordinates
 * @param int $idt thesaurus identificator of concept
 * @param int $level level : 0 is top level
 * @return array of document values
 */
function getChildConcepts($dbaccess, $conid)
{
    $s = new SearchDoc($dbaccess, "THCONCEPT");
    $s->addFilter("'%d' = any(thc_broader)", $conid);
    $s->overrideViewControl();
    
    $t = $s->search();
    return $t;
}
/**
 * return true if has narrowers
 * @param string $dbaccess database coordinates
 * @param int $idt thesaurus identificator of concept
 * @param int $level level : 0 is top level
 * @return bool
 */
function hasChildConcepts($dbaccess, $conid)
{
    $s = new SearchDoc($dbaccess, "THCONCEPT");
    $s->addFilter("'%d' = any(thc_broader)", $conid);
    $s->slice = 1;
    $s->orderby = '';
    $s->overrideViewControl();
    $s->search();
    return ($s->count() > 0);
}
/**
 * return number of document matching concept
 */
function getThCardinal($dbaccess, $famid, $thvalue, $aid = "")
{
    static $fid = "";
    static $thoa = false;
    static $amulti = "";
    static $th = false;
    static $dcs = false;
    $cardinal = - 1;
    
    if (($fid != $famid) || (($aid != "") && ($aid != $thoa->id))) {
        // optimize for future use in loop
        $fdoc = new_doc($dbaccess, $famid);
        if (!$fdoc->isAlive()) return (sprintf(_("document %s not alive") , $famid));
        $at = $fdoc->getNormalAttributes();
        foreach ($at as $k => $oa) {
            if (($aid == "") || ($aid == $oa->id)) {
                if ($oa->type == "thesaurus") {
                    $thid = $oa->format;
                    $fid = $famid;
                    $thoa = $oa;
                    $tho = new_doc($dbaccess, $thid);
                    if ($tho->isAlive()) $th = $tho;
                    else return (sprintf(_("thesaurus %s not alive") , $thid));
                    
                    $q = new QueryDb($dbaccess, "Doccount");
                    $q->addQuery("famid=" . intval($famid));
                    $q->addQuery("aid='" . pg_escape_string($oa->id) . "'");
                    $rdc = $q->Query(0, 0, "TABLE");
                    $dcs = array();
                    if ($q->nb > 0) {
                        foreach ($rdc as $v) {
                            $dcs[$v["filter"]] = $v["c"];
                        }
                    }
                    break;
                }
            }
        }
        include_once ("FDL/Class.SearchDoc.php");
    }
    if ($th) {
        /**
         * @var \Dcp\Family\THESAURUS $th
         */
        if (isset($dcs[$thvalue])) {
            $cardinal = $dcs[$thvalue];
        } else {
            $dc = new docCount($dbaccess, array(
                $fid,
                $thoa->id,
                $thvalue
            ));
            $s = new SearchDoc($dbaccess, $fid);
            $thsql = $th->getSqlFilter($thoa, $thvalue);
            
            $s->addFilter($thsql);
            //$s->slice=$slice;
            $s->orderby = '';
            $cardinal = $s->onlyCount();
            $dc->famid = $famid;
            $dc->aid = $thoa->id;
            $dc->filter = $thvalue;
            $dc->c = $cardinal;
            $err = $dc->Add();
        }
    }
    return $cardinal;
}
?>
