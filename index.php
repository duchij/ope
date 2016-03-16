<?php 
        setlocale(LC_ALL, "");
        require_once 'app/init.class.php';
        $run = new init();
        $run->run($_REQUEST);
?>
