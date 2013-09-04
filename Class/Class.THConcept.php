<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package THESAURUS
*/

namespace Dcp\Thesaurus;
use \Dcp\AttributeIdentifiers as Attributes;
use \Dcp\AttributeIdentifiers\Thconcept as MyAttributes;
use \Dcp\Family as Family;
class Thconcept extends Family\Document
{
    
    function preRefresh()
    {
        // $err= $this->recomputeNarrower();
        $this->retrieveLangLabel();
    }
    function postStore()
    {
        $this->recomputeRelations();
        
        $this->setValue(MyAttributes::thc_level, $this->getLevel());
        $this->setValue(MyAttributes::thc_title, $this->getLangTitle());
        $this->modify();
    }
    
    function getCustomTitle()
    {
        return $this->getLangTitle();
    }
    /**
     * recompute narrower of father
     */
    function recomputeNarrower()
    {
        include_once ("FDL/Class.SearchDoc.php");
        $s = new \SearchDoc($this->dbaccess, "THCONCEPT");
        $s->addFilter("thc_thesaurus='" . intval($this->getRawValue("thc_thesaurus")) . "'");
        $s->addFilter("thc_broader='" . $this->id . "'"); // $s->addFilter("thc_broader ~ '\\\\y$id\\\\y'); // if many
        $t = $s->search();
        $tid = array();
        foreach ($t as $k => $v) {
            $tid[] = $v["initid"];
        }
        $this->setValue(MyAttributes::thc_narrower, $tid);
        $err = $this->modify();
        return $err;
    }
    
    function recomputeRelations()
    {
        $oldtg = $this->getOldRawValue(MyAttributes::thc_broader);
        $tg = $this->getRawValue(MyAttributes::thc_broader);
        if ($oldtg != $tg) {
            /**
             * @var Family\THCONCEPT $d
             */
            $d = new_doc($this->dbaccess, $oldtg);
            if ($d->isAlive()) $d->recomputeNarrower(); // update old
            $d = new_doc($this->dbaccess, $tg);
            if ($d->isAlive()) {
                $d->recomputeNarrower();
                $this->setValue(MyAttributes::thc_uribroader, $d->getRawValue(MyAttributes::thc_uri));
            } // update new
            
        } else {
            $d = new_doc($this->dbaccess, $tg);
            if ($d->isAlive()) {
                $this->setValue(MyAttributes::thc_uribroader, $d->getRawValue(MyAttributes::thc_uri));
            }
        }
    }
    
    function refreshFromURI()
    {
        include_once ("THESAURUS/Lib.Thesaurus.php");
        $broaduri = $this->getMultipleRawValues(MyAttributes::thc_uribroader);
        $broad = $this->getMultipleRawValues(MyAttributes::thc_broader);
        
        foreach ($broaduri as $k => $v) {
            if (!$broad[$k]) {
                $broad[$k] = getConceptIdFromURI($this->dbaccess, $v);
            }
        }
        $this->setValue(MyAttributes::thc_broader, $broad);
        
        $relateduri = $this->getMultipleRawValues(MyAttributes::thc_urirelated);
        $related = $this->getMultipleRawValues(MyAttributes::thc_related);
        foreach ($relateduri as $k => $v) {
            if (!$related[$k]) {
                $related[$k] = getConceptIdFromURI($this->dbaccess, $v);
            }
        }
        $this->setValue(MyAttributes::thc_related, $related);
    }
    
    function retrieveLangLabel()
    {
        include_once ("THESAURUS/Lib.Thesaurus.php");
        $langs = getLangConcepts($this->dbaccess, $this->initid);
        $tlang = $tlangid = $tlanglabel = array();
        foreach ($langs as $k => $v) {
            $tlang[] = $v["thcl_lang"];
            $tlangid[] = $v["initid"];
            $tlanglabel[] = $v["thc_preflabel"];
        }
        $this->setValue(MyAttributes::thc_lang, $tlang);
        $this->setValue(MyAttributes::thc_idlang, $tlangid);
        $this->setValue(MyAttributes::thc_langlabel, $tlanglabel);
    }
    /**
     * @return bool|Family\THCONCEPT
     */
    function getParentConcept()
    {
        $gen = $this->getRawValue(MyAttributes::thc_broader);
        if ($gen) {
            $d = new_doc($this->dbaccess, $gen);
            if ($d->isAlive()) return $d;
        }
        return false;
    }
    
    function getLevel()
    {
        $level = 0;
        $father = $this->getParentConcept();
        while ($father) {
            $level++;
            $father = $father->getParentConcept();
            if ($level > 100) break;
        }
        return $level;
    }
    /**
     * return recursive narrower
     */
    function getRNarrowers()
    {
        $pid = array();
        $nrs = $this->getMultipleRawValues(MyAttributes::thc_narrower);
        $nrsdoc = getDocsFromIds($this->dbaccess, $nrs, 1);
        
        foreach ($nrsdoc as $k => $nr) {
            $nrs = array_merge($nrs, $this->_getRNarrowers(\Doc::rawValueToArray($nr[MyAttributes::thc_narrower])));
        }
        return $nrs;
    }
    /**
     * return recursive narrower
     */
    private function _getRNarrowers($nrs)
    {
        $_nrs = $nrs;
        
        foreach ($nrs as $k => $nr) {
            $t = getTDoc($this->dbaccess, $nr);
            $_nrs1 = \Doc::rawValueToArray($t[MyAttributes::thc_narrower]);
            $_nrs2 = $this->_getRNarrowers($_nrs1);
            $_nrs = array_merge($_nrs, $_nrs1, $_nrs2);
        }
        return $_nrs;
    }
    /**
     * return localized label
     * @param string $lang languqge : simple notation like en,fr,ru,es
     */
    function getLabelLang($lang = false, $getFirstIfFailed = true)
    {
        if ($lang === false) $lang = strtolower(strtok(getParam("CORE_LANG") , '_'));
        $tlang = $this->getMultipleRawValues(MyAttributes::thc_lang);
        $tll = $this->getMultipleRawValues(MyAttributes::thc_langlabel);
        
        $kgood = - 1;
        
        foreach ($tlang as $k => $v) {
            if ($tlang[$k] == $lang) {
                $kgood = $k;
                break;
            }
        }
        
        $label = (isset($tll[$kgood])) ? $tll[$kgood] : '';
        if ($getFirstIfFailed && !$label) {
            $label = isset($tll[0]) ? $tll[0] : '';
        }
        return $label;
    }
    /**
     * return localized title
     */
    function getLangTitle($lang = false)
    {
        $label = $this->getLabelLang($lang, false);
        if ($label) {
            $label = trim($this->getRawValue(MyAttributes::thc_label) . ' ' . $label);
        }
        if ($label == '') {
            if ($this->getRawValue(MyAttributes::thc_label)) {
                $label = trim($this->getRawValue(MyAttributes::thc_label) . ' ' . $this->getLabelLang($lang));
            }
        }
        if ($label == '') {
            $label = $this->getRawValue(MyAttributes::thc_preflabel);
        }
        if ($label == '') {
            $label = $this->getRawValue(MyAttributes::thc_uri);
        }
        return $label;
    }
}
