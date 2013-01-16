<?php

/*
 * Author : Jason D'souza
 * Description : Common Settings file to be used in the retrieval management system.
 * Date : 21 - 08 - 2011
 * 
 */

//flickr related variables
$api_key = "a98e8be3a92658465611b1bb9c47fc04";
$secret = "94d4ac30f8eb7a99";

//database related variables
$database_host ="localhost";
$database_username="root";
$database_password="";
$database_name="crawl";

$dbconnect = mysql_connect($database_host, $database_username, $database_password);
if(!$dbconnect){
    die('Could not connect to mysql database '.mysql_error());
}
mysql_select_db($database_name, $dbconnect);


if(!class_exists('dqry')){
    class dqry{
        var $qry;
        var $rslt;
        var $count;
        public function __construct($qry) {
            $this->qry=$qry;
        }
        public function xqry(){
            if(substr($this->qry,0,6)== "SELECT"){
                $result = mysql_query($this->qry) or die("A MySQL error has occurred.<br />Your Query: " . $your_query . "<br /> Error: (" . mysql_errno() . ") " . mysql_error());
                $num_of_rows = mysql_num_rows($result);
                $this->count = $num_of_rows;
                $i=0;
                while($i < $num_of_rows){
                    $this->rslt[$i] =  mysql_fetch_array($result);
                    $i++;
                }
            }
            else{
                mysql_query($this->qry);
            }
        }
    }
}
?>