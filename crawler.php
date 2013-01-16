<?php

/*
 * TODO - check for no databse records
 * stop redundant date into database
 * stop already crawled information from getting into the databse.
 * 
 */

require_once("framework/include.php");

function isCli() { 
     if(php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR'])) {
          echo "\n";
     } else {
          echo "<br />";
     }
}

$flickr_data_container = new phpFlickr("a98e8be3a92658465611b1bb9c47fc04","94d4ac30f8eb7a99");
$gtQryLst = new dqry("SELECT * FROM queries");
$gtQryLst->xqry();
$queryInfo = $gtQryLst->rslt;
if($gtQryLst->count != 0) {
    foreach($queryInfo as $queryTerm){
        if($queryTerm['complete'] == 0) {
            $text = $queryTerm['query'];
            echo "Getting users for query term : ".$text;
            isCli();
            $query = array("text"=>$text,"page"=>1 ,"per_page"=>30);
            $nps = $flickr_data_container->photos_search($query); //get the Normal PhotoSet
            $photoset = new photosetprocess($nps,$text);
            isCli();
        }
        else {
            echo $queryTerm['query']." has already been crawled";
        }
    }
}
else {
    echo "No Queries in the table. Please add queries!";
}
?>
