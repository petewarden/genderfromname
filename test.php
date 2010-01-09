<?php

require_once('genderfromname.php');

$genders = array('Josephine' => 'f',
               'Michael' => 'm',
               'Dondi' => 'm',
               'Jonny' => 'm',
               'Pascal' => 'm',
               'Velvet' => 'f',
               'Eamon' => 'm',
               'FLKMLKSJN' => '');


$names = array('Josephine',
             'Michael',
             'Dondi', 
             'Jonny',
             'Pascal',
             'Velvet',
             'Eamon',
             'FLKMLKSJN');

$MATCH_LIST[] = 'user_sub';

$tests = $MATCH_LIST;

print '<html><head><title>GenderFromName PHP module test</title></head><body>';

// Test for Text::DoubleMetaphone. Skip those tests if not installed.

if (!is_callable('double_metaphone'))
{
    print 'Skipping double_metaphone tests - module not found. See http://svn.php.net/viewvc/pecl/doublemetaphone/<br>';
    foreach ($tests as $index => $function)
    {
        if (preg_match('/metaphone/', $function))
            unset($tests[$index]);
    }
}

$DEBUG = 1;

$index = 0;
foreach ($tests as $testname) {
    // Test just this one rule.
    $MATCH_LIST = array($testname);

    $firstname = $names[$index];
    $secondname = $names[$index+1];

    $firstresult = gender($firstname);
    $secondresult = gender($secondname);

    $firstexpected = $genders[$firstname];
    $secondexpected = $genders[$secondname];
    
    print_result($testname, $firstname, $firstresult, $firstexpected);
    print_result($testname, $secondname, $secondresult, $secondexpected);
       
    $index += 1;
}

print '</body></html>';

function user_sub($name) {
    if (preg_match('/^eamon/', $name)) return 'm';
}

function print_result($testname, $name, $result, $expected)
{
    if (isset($result))
    {
        if ($result['gender']!==$expected)
            print "ERROR: $testname produced '{$result['gender']}' for $name, expected '$expected'<br>";
        else
            print "SUCCESS: $testname produced '{$result['gender']}' for $name<br>";
    }
    else
    {
        print "UNKNOWN: $testname produced nothing for $name<br>";    
    }

}

?>