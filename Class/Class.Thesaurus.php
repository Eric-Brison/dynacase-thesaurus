<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package THESAURUS
*/

namespace Dcp\Thesaurus;
use \Dcp\AttributeIdentifiers as Attributes;

use \Dcp\Family as Family;

class Thesaurus extends Family\Document
{
    public $cviews = array(
        "THESAURUS:CONCEPTTREE"
    );
    const maxRefreshTime = 600;
    /**
     * return sql filter to search document
     * @param \NormalAttribute $oa attribute identificator where do the search
     * @param int $thv value of concept to search
     * @return string sql filter
     */
    function getSqlFilter($oa, $thv)
    {
        $sql = "no $thv";
        $multi = ($oa->getOption("multiple") == "yes" || $oa->inArray());
        if ($multi) {
            if (is_array($thv)) {
                $sql = "multi array";
                $thnr = array();
                foreach ($thv as $thid) {
                    /**
                     * @var Family\THCONCEPT $th
                     */
                    $th = new_doc($this->dbaccess, $thid);
                    if ($th->isAlive()) {
                        $thnr = array_merge($thnr, $th->getRNarrowers());
                        $thnr[] = $thid;
                    }
                }
                if (count($thnr) == 1) $sql = sprintf("%s ~ '\\\\m%s\\\\M'", $oa->id, intval($thnr[0]));
                else $sql = $oa->id . " ~ '\\\\m(" . pg_escape_string(implode('|', $thnr)) . ")\\\\M'";
            } else {
                
                $sql = "multi atom";
                /**
                 * @var Family\THCONCEPT $th
                 */
                $th = new_doc($this->dbaccess, $thv);
                if ($th->isAlive()) {
                    $thnr = $th->getRNarrowers();
                    $thnr[] = $thv;
                    if (count($thnr) == 1) $sql = sprintf("%s ~ '\\\\m%s\\\\M'", $oa->id, intval($thv));
                    else $sql = $oa->id . " ~ '\\\\m(" . pg_escape_string(implode('|', $thnr)) . ")\\\\M'";
                }
            }
        } else {
            if (is_array($thv)) {
                $sql = "single array";
                $thnr = array();
                foreach ($thv as $thid) {
                    /**
                     * @var Family\THCONCEPT $th
                     */
                    $th = new_doc($this->dbaccess, $thid);
                    if ($th->isAlive()) {
                        $thnr = array_merge($thnr, $th->getRNarrowers());
                        $thnr[] = $thid;
                    }
                }
                if (count($thnr) == 1) $sql = sprintf("%s = '%s'", $oa->id, $thnr[0]);
                else $sql = GetSqlCond($thnr, $oa->id);
            } else {
                $sql = "single atom";
                /**
                 * @var Family\THCONCEPT $th
                 */
                $th = new_doc($this->dbaccess, $thv);
                if ($th->isAlive()) {
                    $thnr = $th->getRNarrowers();
                    $thnr[] = $thv;
                    if (count($thnr) == 1) $sql = sprintf("%s = '%s'", $oa->id, intval($thv));
                    else $sql = GetSqlCond($thnr, $oa->id);
                }
            }
        }
        
        return $sql;
    }
    /**
     * refresh relations from uri
     * @apiExpose
     */
    function refreshConcepts()
    {
        setMaxExecutionTimeTo(self::maxRefreshTime);
        $s = new \SearchDoc($this->dbaccess, Family\Thconcept::familyName);
        $s->addFilter("thc_thesaurus='" . $this->initid . "'");
        $s->setObjectReturn();
        $s->search();
        /**
         * @var Family\THCONCEPT $doc
         */
        while ($doc = $s->getNextDoc()) {
            $doc->recomputeNarrower();
            $doc->setValue(Attributes\Thconcept::thc_title, $doc->getLangTitle());
            $doc->refresh();
            $doc->modify();
        }
        
        $s->search();
        while ($doc = $s->getNextDoc()) {
            $doc->setValue(Attributes\Thconcept::thc_level, $doc->getLevel());
            $doc->modify();
        }
    }
    /**
     * view to see concept tree
     * @templateController
     */
    function concepttree($target = "_self", $ulink = true, $abstract = false)
    {
        $s = new \SearchDoc($this->dbaccess, Family\Thconcept::familyName);
        $s->addFilter("thc_thesaurus='%d'", $this->initid);
        $s->setObjectReturn();
        $s->orderby = Attributes\Thconcept::thc_level;
        $s->search();
        $brs = array();
        $tout = array();
        while ($doc = $s->getNextDoc()) {
            $br = $doc->getRawValue("thc_broader");
            $id = $doc->id;
            $brs[$id] = (isset($brs[$br]) ? $brs[$br] : '') . '.' . $id;
            
            $tout[$id] = array(
                "levelcolor" => 5 + ($doc->getRawValue(Attributes\Thconcept::thc_level)) % 5,
                "level20" => $doc->getRawValue(Attributes\Thconcept::thc_level) * 20,
                "uri" => $doc->getRawValue(Attributes\Thconcept::thc_uri) ,
                "id" => $id,
                "broader" => $doc->getRawValue(Attributes\Thconcept::thc_broader) ,
                //  "order" => substr($brs[$id], 1,strrpos($brs[$id],".")),
                "order" => substr($brs[$id], 1) ,
                "rawTitle" => $doc->getTitle() ,
                "title" => $this->getDocAnchor($id, "_blank", true, $doc->getTitle())
            );
        }
        // need to sort by level and title
        $collator = new \Collator(getParam('CORE_LANG', 'fr_FR'));
        uasort($tout, function ($a, $b) use ($collator)
        {
            /**
             * @var \Collator $collator
             */
            return $collator->compare($a['rawTitle'], $b['rawTitle']);
        });
        
        $o = 1;
        foreach ($tout as $k => $v) {
            $tout[$k]["titleorder"] = $o++;
        }
        
        foreach ($tout as $k => $v) {
            $torder = explode('.', $v["order"]);
            $tTitleOrder = array();
            foreach ($torder as $idOrder) {
                $tTitleOrder[] = $tout[$idOrder]["titleorder"];
            }
            $tout[$k]["newOrder"] = implode(".", $tTitleOrder);
        }
        
        usort($tout, function ($a, $b)
        {
            return $this->_cmpthorder($a, $b);
        });
        $this->lay->setBlockData("CONCEPTS", $tout);
    }
    /**
     * to sort concept
     */
    static private function _cmpthorder($a, $b)
    {
        return version_compare($a['newOrder'], $b['newOrder']);
    }
    /*
     * @begin-method-ignore
     * this part will be deleted when construct document class until end-method-ignore
    */
}
