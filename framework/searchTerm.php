<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of searchTerm
 *
 * @author Jason
 */


require_once 'include.php';
class searchTerm {
    public function __construct($term) {
        $term;
        $dbins = new dqry("INSERT INTO queries (query, complete) VALUES('".$term."','0');");
        $dbins->xqry();
    }
}

?>
