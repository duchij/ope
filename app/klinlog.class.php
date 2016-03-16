<?php
//require_once 'mysql.class.php';
//require_once 'log.class.php';

class klinlog extends app {
    
    var $iList = array();
    
    function __construct()
    {
        parent::__construct();
    }
    
    /***
     * Toto je verejna cast volam z nej fnc ktora potom startuje import dat do 
     * korespondujuceho logu do MySQL/MARIADb...
     */
    
    public function init()
    {
        $this->importKlinLog2();
              
    }
    
    private function getTable($medeaCon,$query)
    {
        $res = odbc_exec($medeaCon, $query);
        $result = array();
        $fields = odbc_num_fields($res);
        
        while ($row = odbc_fetch_row($res)) {
            $rowTmpArr = array();
            for ($f=1; $f <= $fields;$f++) {
                $rowTmpArr[odbc_field_name($res,$f)] = iconv("CP1250","UTF-8",odbc_result($res,$f));
            }
            $result[] = $rowTmpArr;
        }
        return $result;
    
    }
    
    private function importKlinLog2()
    {
        $dt  = new DateTime();
        $lastTenMin = $dt->modify("-10 minute");
        $date = $lastTenMin->format("Y-m-d");
        $time = $lastTenMin->format("Hi");
        $query = sprintf("SELECT iduzivatel, datum, cas, uzel, uzelzkr, uzelnazov, scpac, pristup, upresnenie
                    FROM ADMINSQL.klinlog_view
                        WHERE (datum='%s')
                        AND (cas>='%s')
                        AND (iduzivatel<>'0') 
                        ORDER BY cas ASC WITH(NOLOCK)",$date,$time);
        
        $medeaCon = $this->openOdbc();
        $table = $this->getTable($medeaCon, $query);
        odbc_close_all();
        
        $this->debug($table);
        foreach ($table as &$row)
        {
           $row["hash"] = $this->makeHash($row);
           $res = $this->updateRow($row);
           if ($res["affected"] == 0){
               $insData = array(
                   "iduzivatel"=>$row["iduzivatel"],
                   "datum"=>$row["datum"],
                   "cas"=>$row["cas"],
                   "uzel"=>$row["uzel"],
                   "uzelzkr"=>$row["uzelzkr"],
                   "scpac"=>$row["scpac"],
                   "hash"=>$row["hash"],
                   "started"=>$row["datum"]." ".$this->makeTime($row["cas"]),
                   );
               $res1 = $this->db->insert_row_withoutDupKey("klinlog_sync_main", $insData,"IGNORE");
               if ($res1["status"]){
                   $insData1 = array(
                            "main_hash"=>$row["hash"],
                            "datum"=>$row["datum"],
                            "cas"=>$row["cas"],
                            "uzelnazov"=>$row["uzelnazov"],
                            "pristup"=>$row["pristup"],
                            "upresnenie"=>$row["upresnenie"],
                       );
                  // var_dump($insData1);
                   $res2 = $this->db->insert_row_withoutDupKey("klinlog_sync_data",$insData1,"IGNORE");
               }
               else {
                   var_dump($res1);
               }
               
                //var_dump($res1);
           }else{
             //$insData1  
               $insData1 = array(
                   "main_hash"=>$row["hash"],
                   "datum"=>$row["datum"],
                   "cas"=>$row["cas"],
                   "uzelnazov"=>$row["uzelnazov"],
                   "pristup"=>$row["pristup"],
                   "upresnenie"=>$row["upresnenie"],
               );
               // var_dump($insData1);
               $res2 = $this->db->insert_row_withoutDupKey("klinlog_sync_data",$insData1,"IGNORE");
               
               if ($res2["status"]==false){
                   var_dump($res2);
                   exit;
               }
           }
        }
    }
    
    private function updateRow($data)
    {
        $uDateTime = $data["datum"]." ".$this->makeTime($data["cas"]);
        $sql = sprintf("UPDATE [klinlog_sync_main] SET [ended]='%s' WHERE [hash]='%s'",$uDateTime,$data["hash"]);
        $res = $this->db->sql_execute($sql);
        
        return $res;
    }
    
    private function makeTime($time)
    {
        return substr($time,0,2).":".substr($time,2,2).":".substr($time,4,2);
    }
       
    
    private function makeHash($data)
    {
        $str = sprintf("%s-%s-%s-%s",
            $data["datum"],
            $data["iduzivatel"],
            $data["uzel"],
            $data["scpac"]
        );
        $strMd5 = md5($str);
        return $strMd5;
    }  
}