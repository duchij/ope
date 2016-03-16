<?php

class pac extends app {
    
    var $startTime;
    var $endTime;
    
    function __construct()
    {
        parent::__construct();
    } 
    
    public function init($data)
    {
        $this->runTest();
    }
    
    private function runTest()
    {
        $medeaCon = $this->openOdbc2();

         //$query =  "SELECT   *
           //        FROM ADMINSQL.VIEW_ZMENY_CR2
             //           WHERE DATUM_CAS_ZPRACOVANIA> '2015-06-30 13:00:00.000' order by ID_ZMENY with(nolock)";
        
       $query =  "SELECT   *
                  FROM ADMINSQL.VIEW_ZMENY_CR2
                        WHERE ID_ZMENY > 3555671 with(nolock)";
        
        $table = array();
        
        $this->startTime = microtime(true);
        $res = odbc_exec($medeaCon, $query);
        $this->endTime = microtime(true);
        while ($table[] = odbc_fetch_array($res));
        
        
        $this->log->logData($table, false,"medea2 test");
        
        $totalTime = $this->endTime-$this->startTime;
        
        $this->log->logData($totalTime,false,"query to medea db");
        
        
       
    }
}

?>