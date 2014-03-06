<?php
/*
 * Import SKOS thesaurus
 *
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package THESAURUS
*/

include_once ("FDL/Class.Doc.php");
include_once ("THESAURUS/Lib.Thesaurus.php");
/**
 * Display thesaurus tree
 * @param Action &$action current action
 * @global string $filter Http var : search text key
 * @global string $aid Http var : thesaurus attribute
 * @global string $id Http var : thesaurus id
 */
function inputtree(Action & $action)
{
    $id = $action->getArgument("id");
    $filter = $action->getArgument("filter");
    $aid = $action->getArgument("aid");
    $lang = $action->getArgument("lang");
    $level = $action->getArgument("level", 2);
    $multi = ($action->getArgument("multi") == "yes") ? 'multi' : false;
    
    $b1 = microtime(true);
    $dbaccess = $action->GetParam("FREEDOM_DB");
    if (!$lang) $lang = strtolower(strtok(getParam("CORE_LANG") , '_'));
    $oneisgood = false;
    $doc = new_doc($dbaccess, $id);
    $t0 = array();
    $b2 = 0;
    if ($doc->isAlive()) {
        
        $t = getConceptsLevel($dbaccess, $doc->initid, $level);
        $b2 = microtime(true);
        foreach ($t as $v) {
            if ($v["thc_level"] == 0) {
                $label = getLabelLang($v, $lang);
                $isgood = (($filter == "") || (preg_match("/$filter/i", $v["thc_label"] . $label, $reg)));
                $oneisgood|= $isgood;
                $child = getUltree($t, $v["initid"], $filter, $childgood, $lang);
                $oneisgood|= $childgood;
                $t0[] = array(
                    "title" => htmlspecialchars($v["thc_label"], ENT_QUOTES) ,
                    "desc" => htmlspecialchars($label, ENT_QUOTES) ,
                    "isfiltergood" => $isgood,
                    "ischildgood" => $childgood,
                    "nosee" => (!$childgood) && (!$isgood) ,
                    "openit" => ($childgood) && (!$isgood) ,
                    "thid" => $v["initid"],
                    "child" => $child
                );
            }
        }
    }
    $action->lay->set("first", true);
    $action->lay->eSet("aid", $aid);
    $action->lay->set("multi", (bool)$multi);
    $action->lay->setBlockData("LIs", $t0);
    $action->lay->set("time", sprintf("%0.3f [%.03f]", $b2 - $b1, microtime(true) - $b1));
    
    if (!$oneisgood) $action->lay->set("error", htmlspecialchars(sprintf(_("no result matching %s") , $filter) , ENT_QUOTES));
    else $action->lay->set("error", "");
}
function getLabelLang($v, $lang)
{
    $tlang = Doc::rawValueToArray($v["thc_lang"]);
    $tll = Doc::rawValueToArray($v["thc_langlabel"]);
    
    $kgood = - 1;
    
    foreach ($tlang as $k => $v) {
        if ($tlang[$k] == $lang) {
            $kgood = $k;
            break;
        }
    }
    
    return (isset($tll[$kgood])) ? $tll[$kgood] : (isset($tll[0]) ? $tll[0] : '');
}

function getUltree(&$t, $initid, $filter, &$oneisgood, $lang)
{
    $lay = new Layout(getLayoutFile("THESAURUS", "inputtree.xml"));
    $b = array();
    $oneisgood = false;
    foreach ($t as $v) {
        if ($v["thc_broader"] == '{' . $initid . '}') {
            $label = getLabelLang($v, $lang);
            $isgood = (($filter == "") || (preg_match("/$filter/i", $v["thc_label"] . $label, $reg)));
            $oneisgood|= $isgood;
            $child = getUltree($t, $v["initid"], $filter, $childgood, $lang);
            $oneisgood|= $childgood;
            $b[] = array(
                "title" => htmlspecialchars($v["thc_label"], ENT_QUOTES) ,
                "desc" => htmlspecialchars($label, ENT_QUOTES) ,
                "thid" => $v["initid"],
                "isfiltergood" => $isgood,
                "ischildgoodnos" => $childgood,
                "nosee" => (!$childgood) && (!$isgood) ,
                "openit" => ($childgood) && (!$isgood) ,
                "child" => $child
            );
        }
    }
    if (count($b) == 0) {
        return "";
    }
    $lay->set("first", false);
    $lay->setBlockData("LIs", $b);
    return $lay->gen();
}
