<?php

include_once __DIR__.'/vendor/autoload.php';
$credentials=json_decode(file_get_contents(__DIR__.'/credentials.json'));
$client=new \knack\Client($credentials);


// $results=$client->getObjects();
// print_r($results);
// file_put_contents(__DIR__.'/test-out/objects.json', json_encode($results, JSON_PRETTY_PRINT));


$results=$client->getPages();
print_r($results);
file_put_contents(__DIR__.'/test-out/pages.json', json_encode($results, JSON_PRETTY_PRINT));



// $from=0; $to=40;
// for($i=$from;$i<$to;$i++){
// 	$results=$client->getRecords($i);
// 	file_put_contents(__DIR__.'/test-out/results-'.$i.'.json', json_encode($results, JSON_PRETTY_PRINT));
// }


// $from=0; $to=40;
// for($i=$from;$i<$to;$i++){
// 	$results=$client->getFields($i);
// 	file_put_contents(__DIR__.'/test-out/fields-'.$i.'.json', json_encode($results, JSON_PRETTY_PRINT));
// }
