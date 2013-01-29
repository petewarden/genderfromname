<?php

require_once('genderfromname.php');

// get some name data
include('data/males.php');
include('data/females.php');

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

print '<html><head><title>GenderFromName PHP module test</title></head><body>';

// Test for Text::DoubleMetaphone. Skip those tests if not installed.

$index = 0;
// Test just this one rule.
$firstname = $names[$index];
$secondname = $names[$index+1];

$firstresult = GenderGuesser::init()
    ->setFirstName($firstname)
    ->setMaleFirstNames($Males)
    ->setFemaleFirstNames($Females)
    ->setSeverity(1)
    ->debug()
    ->guess();

$secondresult = GenderGuesser::init()
    ->setFirstName($secondname)
    ->setMaleFirstNames($Males)
    ->setFemaleFirstNames($Females)
    ->setSeverity(1)
    ->debug()
    ->guess();

$firstexpected = $genders[$firstname];
$secondexpected = $genders[$secondname];

print_result($testname, $firstname, $firstresult, $firstexpected);
print_result($testname, $secondname, $secondresult, $secondexpected);

print '</body></html>';

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
