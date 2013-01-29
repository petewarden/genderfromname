<?php
// GenderFromName.php
//
// Originally by Jon Orwant, <orwant@readable.com>
// Created 10 Mar 97
//
// Version 0.30 - Jul 29 2003 by
// Eamon Daly, <eamon@eamondaly.com>
//
// Ported to PHP - Dec 31 2009 by
// Pete Warden, <pete@petewarden.com>
//
// Taken out of the global namespace - Jan 28 2013 by
// Jonathan Mayhak, <jmayhak@gmail.com>

/**
 * Use this class to search through a user provided list of first names (male and female)
 * to find the gender that best matches.
 *
 * ex.
 *   $result = GenderGuesser::init()
 *       ->setFirstName('jon')
 *       ->setMaleFirstNames(array('jon' => 0.9))
 *       ->setFemaleFirstNames(array('melissa' => 0.95))
 *       ->setSeverity(1)
 *       ->guess(); // returns array('gender' => 'm', 'confidence' => 0) where 0 is total confidence
 */
class GenderGuesser
{

    private $_debug;
    private $_debug_message;

    private $_match_list = array(
            'one_only',
            'either_weight',
            'one_only_metaphone',
            'either_weight_metaphone',
            'v2_rules',
            'v1_rules'
            );

    private $_first_name;
    private $_severity;

    private $_female_first_names = array();
    private $_male_first_names = array();

    public static function init()
    {
        // set defaults
        $instance = new self();
        $instance->_debug = 0;
        $instance->_debug_message = '';
        $instance->_severity = 2;
        return $instance;
    }

    function guess()
    {
        $DEBUG = $this->_debug;
        $DEBUG_MSG = $this->_debug_message;
        $MATCH_LIST = $this->_match_list;

        $gender = null;
        $name = $this->_first_name;
        $looseness = $this->_severity;

        if (!$name) {
            error_log("No name specified");
            return null;
        }

        $name = strtolower($name);

        if ($DEBUG)
            $DEBUG_MSG = "Matching '$name'\n";

        for ($i = 0; $i < $looseness; $i++) {
            if (!isset($MATCH_LIST[$i]))
                continue;

            if ($DEBUG)
                $DEBUG_MSG .= "\t{$MATCH_LIST[$i]}...\n";

            $gender = $this->$MATCH_LIST[$i]($name);

            if ($DEBUG && isset($gender))
                $DEBUG_MSG .= "\t==> HIT ($gender)\n";

            if (isset($gender))
                break;
        }

        $confidence = $i;

        if ($DEBUG)
            error_log($DEBUG_MSG);

        if (!isset($gender))
            return null;
        else
            return array('gender' => $gender, 'confidence' => $confidence);

    }

    function setFirstName($first_name)
    {
        $this->_first_name = $first_name;
        return $this;
    }

    /**
     * Severity is the number of functions to go through that
     * attempt to find the gender. Functions from $this->_match_list.
     *
     * If a match is found in the first, then the others won't run.
     */
    function setSeverity($severity)
    {
        $this->_severity = $severity;
        return $this;
    }

    function debug()
    {
        $this->_debug = 1;
        return $this;
    }

    function setDebugMessage($message)
    {
        $this->_debug_message = $message;
        return $this;
    }

    function setMaleFirstNames($names)
    {
        $this->_male_first_names = $names;
        return $this;
    }

    function setFemaleFirstNames($names)
    {
        $this->_female_first_names = $names;
        return $this;
    }

    function one_only($name) {
        $Males = $this->_male_first_names;
        $Females = $this->_female_first_names;

        $gender = null;

        // Match one list only

        $male_hit = isset($Males[$name]);
        $female_hit = isset($Females[$name]);

        if ($female_hit && !$male_hit) {
            $gender = 'f';
        }
        else if ($male_hit && !$female_hit) {
            $gender = 'm';
        }

        return $gender;
    }

    function either_weight($name) {
        $DEBUG = $this->_debug;
        $DEBUG_MSG = $this->_debug_message;
        $Males = $this->_male_first_names;
        $Females = $this->_female_first_names;

        $gender = null;

        // Match either, weight

        $male_hit = isset($Males[$name])? $Males[$name] : 0;
        $female_hit = isset($Females[$name])? $Females[$name] : 0;

        if (($female_hit>0) || ($male_hit>0)) {
            $gender = ($female_hit > $male_hit) ? 'f' : 'm';
        }

        if ($DEBUG && isset($gender))
            $DEBUG_MSG .= "\tF: $female_hit, M: $male_hit\n";

        return $gender;
    }

    function one_only_metaphone($name) {
        $DEBUG = $this->_debug;
        $DEBUG_MSG = $this->_debug_message;
        $Males = $this->_male_first_names;
        $Females = $this->_female_first_names;

        $metaphone_function = 'metaphone';
        if (is_callable('double_metaphone')){
            $metaphone_function = 'double_metaphone';
        }

        $gender = null;

        // Match one list only, use DoubleMetaphone

        $meta_name = $metaphone_function($name);
        $metaphone_hit = '';

        // Pete- Changed the original Perl code which did a copy of the name
        // arrays and resorted them every time. Instead, the default array
        // was already sorted, and sort any user-supplied arrays before
        // storing them.

        $male_hit = 0;
        $female_hit = 0;

        foreach ($Females as $list_name => $weight)
        {
            if ($female_hit>0)
                break;

            $meta_list_name = $metaphone_function($list_name);

            if ($meta_name === $meta_list_name) {
                $female_hit = $weight;

                if ($DEBUG)
                    $DEBUG_MSG .= sprintf("\tF: %s => %s => %s: %f\n",
                            $name, $list_name, $meta_list_name, $weight);
            }
        }

        foreach ($Males as $list_name => $weight)
        {
            if ($male_hit>0)
                break;

            $meta_list_name = $metaphone_function($list_name);

            if ($meta_name === $meta_list_name) {
                $male_hit = $weight;

                if ($DEBUG)
                    $DEBUG_MSG .= sprintf("\tF: %s => %s => %s: %f\n",
                            $name, $list_name, $meta_list_name, $weight);
            }
        }

        if (($female_hit>0) && !($male_hit>0)) {
            $gender = 'f';
        }
        else if (($male_hit>0) && !($female_hit>0)) {
            $gender = 'm';
        }

        return $gender;
    }

    function either_weight_metaphone($name) {
        $DEBUG = $this->_debug;
        $DEBUG_MSG = $this->_debug_message;
        $Males = $this->_male_first_names;
        $Females = $this->_female_first_names;

        $metaphone_function = 'metaphone';
        if (is_callable('double_metaphone')){
            $metaphone_function = 'double_metaphone';
        }

        // Match either, weight, use DoubleMetaphone

        $meta_name = $metaphone_function($name);
        $metaphone_hit = '';

        // Pete- Changed the original Perl code which did a copy of the name
        // arrays and resorted them every time. Instead, the default array
        // was already sorted, and sort any user-supplied arrays before
        // storing them.

        $male_hit = 0;
        $female_hit = 0;

        foreach ($Females as $list_name => $weight)
        {
            if ($female_hit>0)
                break;

            $meta_list_name = $metaphone_function($list_name);

            if ($meta_name === $meta_list_name) {
                $female_hit = $weight;

                if ($DEBUG)
                    $DEBUG_MSG .= sprintf("\tF: %s => %s => %s: %f\n",
                            $name, $list_name, $meta_list_name, $weight);
            }
        }

        foreach ($Males as $list_name => $weight)
        {
            if ($male_hit>0)
                break;

            $meta_list_name = $metaphone_function($list_name);

            if ($meta_name === $meta_list_name) {
                $male_hit = $weight;

                if ($DEBUG)
                    $DEBUG_MSG .= sprintf("\tF: %s => %s => %s: %f\n",
                            $name, $list_name, $meta_list_name, $weight);
            }
        }

        if (($female_hit>0) || ($male_hit>0)) {
            $gender = ($female_hit > $male_hit) ? 'f' : 'm';
        }

        return $gender;
    }

    function v2_rules($name) {
        $gender = null;

        // Match using Orwant's rules from v0.20 of Text::GenderFromName

        // Note that this no longer 'falls through' as in v0.20. Jon makes
        // mention of the fact that the v0.10 rules are ordered, but Jon's
        // additions appear to be exclusive.

        // jon and john
        if (preg_match('/^joh?n/', $name)) { $gender = 'm'; }
        // tom and thomas and tomas and toby
        else if (preg_match('/^th?o(m|b)/', $name)) { $gender = 'm'; }
        else if (preg_match('/^frank/', $name)) { $gender = 'm'; }
        else if (preg_match('/^bil/', $name)) { $gender = 'm'; }
        else if (preg_match('/^hans/', $name)) { $gender = 'm'; }
        else if (preg_match('/^ron/', $name)) { $gender = 'm'; }
        else if (preg_match('/^ro(z|s)/', $name)) { $gender = 'f'; }
        else if (preg_match('/^walt/', $name)) { $gender = 'm'; }
        else if (preg_match('/^krishna/', $name)) { $gender = 'm'; }
        else if (preg_match('/^tri(c|sh)/', $name)) { $gender = 'f'; }
        // pascal and pasqual
        else if (preg_match('/^pas(c|qu)al$/', $name)) { $gender = 'm'; }
        else if (preg_match('/^ellie/', $name)) { $gender = 'f'; }
        else if (preg_match('/^anfernee/', $name)) { $gender = 'm'; }

        return $gender;
    }

    function v1_rules($name) {
        $gender = null;

        // Match using rules from v0.10 of Text::GenderFromName

        // most names ending in a/e/i/y are female
        if (preg_match('/^.*[aeiy]$/', $name)) $gender = 'f';
        // allison and variations
        if (preg_match('/^all?[iy]((ss?)|z)on$/', $name)) $gender = 'f';
        // cathleen, eileen, maureen
        if (preg_match('/een$/', $name)) $gender = 'f';
        // barry, larry, perry
        if (preg_match('/^[^s].*r[rv]e?y?$/', $name)) $gender = 'm';
        // clive, dave, steve
        if (preg_match('/^[^g].*v[ei]$/', $name)) $gender = 'm';
        // carolyn, gwendolyn, vivian
        if (preg_match('/^[^bd].*(b[iy]|y|via)nn?$/', $name)) $gender = 'f';
        // dewey, stanley, wesley
        if (preg_match('/^[^ajklmnp][^o][^eit]*([glrsw]ey|lie)$/', $name)) $gender = 'm';
        // heather, ruth, velvet
        if (preg_match('/^[^gksw].*(th|lv)(e[rt])?$/', $name)) $gender = 'f';
        // gregory, jeremy, zachary
        if (preg_match('/^[cgjwz][^o][^dnt]*y$/', $name)) $gender = 'm';
        // leroy, murray, roy
        if (preg_match('/^.*[rlr][abo]y$/', $name)) $gender = 'm';
        // abigail, jill, lillian
        if (preg_match('/^[aehjl].*il.*$/', $name)) $gender = 'f';
        // janet, jennifer, joan
        if (preg_match('/^.*[jj](o|o?[ae]a?n.*)$/', $name)) $gender = 'f';
        // duane, eugene, rene
        if (preg_match('/^.*[grguw][ae]y?ne$/', $name)) $gender = 'm';
        // fleur, lauren, muriel
        if (preg_match('/^[flm].*ur(.*[^eotuy])?$/', $name)) $gender = 'f';
        // lance, quincy, vince
        if (preg_match('/^[clmqtv].*[^dl][in]c.*[ey]$/', $name)) $gender = 'm';
        // margaret, marylou, miri;
        if (preg_match('/^m[aei]r[^tv].*([^cklnos]|([^o]n))$/', $name)) $gender = 'f';
        // clyde, kyle, pascale
        if (preg_match('/^.*[ay][dl]e$/', $name)) $gender = 'm';
        // blake, luke, mi;
        if (preg_match('/^[^o]*ke$/', $name)) $gender = 'm';
        // carol, karen, shar;
        if (preg_match('/^[cks]h?(ar[^lst]|ry).+$/', $name)) $gender = 'f';
        // pam, pearl, rachel
        if (preg_match('/^[pr]e?a([^dfju]|qu)*[lm]$/', $name)) $gender = 'f';
        // annacarol, leann, ruthann
        if (preg_match('/^.*[aa]nn.*$/', $name)) $gender = 'f';
        // deborah, leah, sarah
        if (preg_match('/^.*[^cio]ag?h$/', $name)) $gender = 'f';
        // frances, megan, susan
        if (preg_match('/^[^ek].*[grsz]h?an(ces)?$/', $name)) $gender = 'f';
        // ethel, helen, gretchen
        if (preg_match('/^[^p]*([hh]e|[ee][lt])[^s]*[ey].*[^t]$/', $name)) $gender = 'f';
        // george, joshua, theodore
        if (preg_match('/^[^el].*o(rg?|sh?)?(e|ua)$/', $name)) $gender = 'm';
        // delores, doris, precious
        if (preg_match('/^[dp][eo]?[lr].*s$/', $name)) $gender = 'f';
        // anthony, henry, rodney
        if (preg_match('/^[^jpswz].*[denor]n.*y$/', $name)) $gender = 'm';
        // karin, kim, kristin
        if (preg_match('/^k[^v]*i.*[mns]$/', $name)) $gender = 'f';
        // bradley, brady, bruce
        if (preg_match('/^br[aou][cd].*[ey]$/', $name)) $gender = 'm';
        // agnes, alexis, glynis
        if (preg_match('/^[acgk].*[deinx][^aor]s$/', $name)) $gender = 'f';
        // ignace, lee, wallace
        if (preg_match('/^[ilw][aeg][^ir]*e$/', $name)) $gender = 'm';
        // juliet, mildred, millicent
        if (preg_match('/^[^agw][iu][gl].*[drt]$/', $name)) $gender = 'f';
        // ari, bela, ira
        if (preg_match('/^[abeiuy][euz]?[blr][aeiy]$/', $name)) $gender = 'm';
        // iris, lois, phyllis
        if (preg_match('/^[egilp][^eu]*i[ds]$/', $name)) $gender = 'f';
        // randy, timothy, tony
        if (preg_match('/^[art][^r]*[dhn]e?y$/', $name)) $gender = 'm';
        // beatriz, bridget, harriet
        if (preg_match('/^[bhl].*i.*[rtxz]$/', $name)) $gender = 'f';
        // antoine, jerome, tyrone
        if (preg_match('/^.*oi?[mn]e$/', $name)) $gender = 'm';
        // danny, demetri, dondi
        if (preg_match('/^d.*[mnw].*[iy]$/', $name)) $gender = 'm';
        // pete, serge, shane
        if (preg_match('/^[^bg](e[rst]|ha)[^il]*e$/', $name)) $gender = 'm';
        // angel, gail, isabel
        if (preg_match('/^[adfgim][^r]*([bg]e[lr]|il|wn)$/', $name)) $gender = 'f';

        return $gender;
    }
}

