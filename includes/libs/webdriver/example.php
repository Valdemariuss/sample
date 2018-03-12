<?php



#WD()->windowMaximize();

#WD();

$t1 = microtime(true);
$x = WD()->getWindowHandles();
$t2 = microtime(true);
print_r($x);

echo __("[#] %.2fs\n\n", $t2-$t1);

var_dump( WD()->selectWindow("CDwindow-bd141dbf-b988-41ba-9c8e-47173b6b9f5f") );


var_dump( WD()->getWindowHandle() );

WD()->closeWindow();

exit;

#WD()->execute("window.open('https://www.marathonbet.com/su/live/5321593','_blank');", array());

var_dump(WD()->getTitle());




#sleep(5);

#var_dump( WD()->getOrientation() );

#WD()->execute("window.open('https://www.marathonbet.com/su/live/5321593','_blank');", array());
#sleep(3);



WD()->execute("$('div.logo').hide();", array());

#WD()->sw

#WD()->closeWindow();

exit;



#echo "session = " . $wd->connect("chrome") . "\n";
#echo $wd->requestURL . "\n";
#var_dump($s); exit;


for($a=1; $a<=1; $a++){
    $t1 = microtime(true);
    #$webdriver->get("https://www.betfair.com/sport/tennis/event?eventId=28177764");
    #$t2 = microtime(true);
    #echo sprintf("[%d] %.2f sec\n", $a, $t2-$t1);



    #$webdriver->execute("document.getElementById('ssc-ht').style.display = 'none';", array());

    $wd->execute("window.open('https://www.betfair.com/sport/tennis/event?eventId=28177648','_blank');", array());
    $wd->execute("window.open('https://www.marathonbet.com/su/live/5321593','_blank');", array());

    $x = $wd->getWindowHandles();
    print_r($x);

    sleep(15);
}


/*$webdriver->get("http://google.com");
$element = $webdriver->findElementBy(LocatorStrategy::name, "q");
if ($element) {
    $element->sendKeys(array("php webdriver" ) );
    $element->submit();
}

sleep(5);*/

#$webdriver->close();

?>