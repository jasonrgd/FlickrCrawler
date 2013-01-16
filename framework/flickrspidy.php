<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of flickrspidy
 *
 * @author Jason
 */

class photosetprocess {
    var $photos;
    var $total_photos;
    var $photos_retrieved;
    var $is_complete;
    var $qtext;
    var $fullpath;
    var $focus;
    public function __construct($photos,$qtext,$focus=0,$full_local_path="E:/SIMS/storage/farmhash"){
        $this->fullpath = $full_local_path;
        //print_r($photos);
        $this->focus=$focus;
        $this->qtext = $qtext;
        $this->photos = $photos;
        if($this->focus == 0) {
            $this->total_photos = $photos['total'];
        }
        else {
            $total_photos;
            $tp_dbqry = new dqry("SELECT total_images FROM queries where query = '".$this->qtext."';");
            $tp_dbqry->xqry();
            foreach($tp_dbqry->rslt as $tpno) {
                $this->total_photos = $tpno['total_images'];
            }
            $this->total_photos += $photos['photos']['total'];
        }
        $updatetotal = new dqry("UPDATE  queries SET  total_images = '".$this->total_photos."' WHERE  query ='".$qtext."'");
        $updatetotal->xqry();
        $updatetotal = null;
        $this->beginCrawlProcess();       
    }
    
    function __destruct() {
        isCli();
        if($this->focus == 0){
           // $destroy = new dqry("UPDATE queries SET complete = 1 WHERE  query ='".$this->qtext."';");
           // $destroy->xqry();
        }
   }
    
    function beginCrawlProcess(){
        if($this->focus == 0){
            $photos = $this->photos['photo'];
        }
        else {
            $photos = $this->photos['photos']['photo'];
        }
        foreach ($photos as $img){
            $flickr_data_container = new phpFlickr("a98e8be3a92658465611b1bb9c47fc04","94d4ac30f8eb7a99");
            $url = $flickr_data_container->buildPhotoUrl($img);                                                                    //print_r($img);
            $hash_name = $this->hashImage($img['id'].$img['secret']);
            $localpath = $this->createDirectory($hash_name);
            $this->saveImage($img,$localpath,$hash_name);
            $this->addDBEntry($img, $hash_name);
        }
    }
    
    function checkOwnerExists($ownerid){
        $ownerQry = new dqry("SELECT flickrnsid from owner WHERE flickrnsid like '".$ownerid."';");
        $ownerQry->xqry();
        if($ownerQry->count != 0){
            return true;
        }
        return false;
    }
    
    function hashImage($str){
        return sha1($str);
    }
    function createDirectory($hash_name){
        
        $localpath=$this->fullpath."/".substr($hash_name,0,2);
        for($i=0,$j=0;$i<3;$i++){
            if(!is_dir($localpath)){
                mkdir($localpath);
            }
            if($i < 2){
                $localpath .=("/".substr($hash_name,$j+2,2));
                $j+=2;
            }
        }
        return $localpath."/";
    }
    
    function saveImage($img,$localpath,$hash_name){
        $img_name = substr($hash_name, 6);
        $localpath .=$img_name.".jpg";
        $url = $this->buildPhotoUrl($img);
        $ch = curl_init ($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
        $rawdata=curl_exec($ch);
        curl_close ($ch);
        if(file_exists($localpath)){
            unlink($localpath);
        }
        $fp = fopen($localpath,'x');
        fwrite($fp, $rawdata);
        fclose($fp);
    }
    
    function addDBEntry($photo,$hashname) {
        $fdc = new phpFlickr("a98e8be3a92658465611b1bb9c47fc04","94d4ac30f8eb7a99");
        $photoInfo = $fdc->photos_getInfo($photo['id'],$photo['secret']);
        $ownerInfo = $fdc->people_getInfo($photo['owner']);
        if($this->focus == 0){
            $gppp = $fdc->people_getPublicPhotos($ownerInfo['id'],NULL,NULL,1);
            echo "Getting public photos of ".$ownerInfo['id'];
            isCli();
            //print_r($gppp);
            $photoset = new photosetprocess($gppp,$this->qtext,1);
        }
        else{
            if(!($this->checkOwnerExists($ownerInfo['id']))){
                $ownerQry = new dqry("INSERT INTO owner (ownerid, flickrnsid, first_date_taken, location, total_photos_taken) VALUES (NULL, '".$ownerInfo['id']."', '".$ownerInfo['photos']['firstdatetaken']."', '".$photoInfo['photo']['owner']['location']." ', '".$ownerInfo['photos']['count']."');");
                $ownerQry->xqry();
                $ownerQry = null;
                $ownerId = mysql_insert_id();
            }
            else{
                $ownerid = "78083608@N00";
                $ownerQry = new dqry("SELECT ownerid from owner WHERE flickrnsid like '".$ownerInfo['id']."';");
                $ownerQry->xqry();
                foreach(($ownerQry->rslt) as $ownerqry) {
                    $ownerId = $ownerqry['ownerid'];
                }
            }
            $photoLocation = $fdc->photos_geo_getLocation($photo['id']);
        
            $localurl=$this->fullpath."/".substr($hashname,0,2)."/".substr($hashname,2,2)."/".substr($hashname,4,2)."/".substr($hashname,6).".jpg";
        
            $qid;
            $qid_dbqry = new dqry("SELECT qid FROM queries where query = '".$this->qtext."';");
            $qid_dbqry->xqry();
            foreach($qid_dbqry->rslt as $queryid) {
                $qid = $queryid['qid'];
            }
        
                $imgQry = new dqry("INSERT INTO images (imageid,farm,server,id,secret, hash,localurl, title, description, ownerid, image_location_latitude, image_location_longitude, accuracy, datetaken, dateupload, qid) VALUES (NULL, '".$photo['farm']."','".$photo['server']."','".$photo['id']."','".$photo['secret']."','".$hashname."' ,'".addslashes($localurl)."', '".$photoInfo['photo']['title']."', '".$photoInfo['photo']['description']."', '".$ownerId."', '".$photoLocation['location']['latitude']."', '".$photoLocation['location']['longitude']."','".$photoLocation['location']['accuracy']."','".$photoInfo['photo']['dates']['taken']."','".$photoInfo['photo']['dates']['posted']."','".$qid."');");
                $imgQry->xqry();
                $imgQry = null;
        
                $imgId = mysql_insert_id();
                foreach($photoInfo['photo']['tags']['tag'] as $tag){
                $query = "INSERT INTO tag (tagid, tagname, imageid) VALUES (NULL, '".$tag['raw']."','".$imgId."');";
                mysql_query($query);
            }
        }
   }
    function buildPhotoURL ($photo, $size = "Medium") {
			//receives an array (can use the individual photo data returned
			//from an API call) and returns a URL (doesn't mean that the
			//file size exists)
			$sizes = array(
				"square" => "_s",
				"thumbnail" => "_t",
				"small" => "_m",
				"medium" => "",
				"medium_640" => "_z",
				"large" => "_b",
				"original" => "_o"
			);
			
			$size = strtolower($size);
			if (!array_key_exists($size, $sizes)) {
				$size = "medium";
			}
			
			if ($size == "original") {
				$url = "http://farm" . $photo['farm'] . ".static.flickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['originalsecret'] . "_o" . "." . $photo['originalformat'];
			} else {
				$url = "http://farm" . $photo['farm'] . ".static.flickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['secret'] . $sizes[$size] . ".jpg";
			}
			return $url;
		}
                
                function isCli() { 
                    if(php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR'])) {
                        echo "\n";
                    } else {
                        echo "<br />";
                    }
                }
}


?>