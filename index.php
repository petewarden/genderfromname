<?php

/**
 * Test page for the GenderFromName module. If there's a 'name' URL parameter, it will use the module
 * to guess the gender based on the first word in the name
 * By Pete Warden <pete@petewarden.com>
 * See http://petewarden.typepad.com/ for more details
 *
 */

require_once ('genderfromname.php');

if (isset($_REQUEST['name'])) {
	$name = urldecode($_REQUEST['name']);
} else {
	$name = '';
}

?>
<html>
<head>
<title>Test page for the GenderFromName module</title>
</head>
<body>
<div style="padding:20px;">
<center>
<form method="GET" action="index.php">
Name: <input type="text" size="40" name="name" value="<?=$name?>"/>
</form>
</center>
</div>
<div>
<center>
<?php
if ($name!='') {

    $DEBUG = 1;

    $strictness = 9; // Set this to 1 or 2 for more restrictive matching

    $nameparts = explode(' ', $name);
    $firstname = $nameparts[0];

    // get some name data
    include('data/males.php');
    include('data/females.php');

    $result = GenderGuesser::init()
        ->setFirstName($firstname)
        ->setMaleFirstNames($Males)
        ->setFemaleFirstNames($Females)
        ->setSeverity($strictness)
        ->guess();

    if (isset($result))
    {
        $gender = $result['gender'];
        $confidence = $result['confidence'];
    
        if ($gender==='f')
            $gendername = 'female';
        else
            $gendername = 'male';
    
        print "GenderFromName guesses that '$name' is $gendername, with confidence of $confidence (0 is most confident)"; 
    }
    else
    {
        print "GenderFromName couldn't make a good guess what $name's gender is";     
    }

}
?>
</center>
</div>
</body>
</html>