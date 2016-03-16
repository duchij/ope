<?php

class rtglog extends app {
    
    var $startTime;
    var $endTime;
    
    
    function __construct()
    {
       parent::__construct();
       $this->startTime = microtime(true);
    }
    
    
    
    public function init()
    {
        if ($this->getLockStatus())
        {
            $this->rtgLogRun();
        }
        else 
        {
            $this->log->logData(" not get lock on rtg sync","",false);
            exit;
        }
           
    }
    
    private function rtgLogRun()
    {
        
            $timeTo = time();
            $timeFrom = time() - 3*60*60;
            
            $dateTo = date("Y-m-d",$timeTo);
            $dateFrom = date("Y-m-d",$timeFrom);
            
            
            
            if ($dateTo == $dateFrom)
            {
                $timeToStr = date("Hi",$timeTo);
                $timeFromStr = date("Hi",$timeFrom);
            
                $query =  sprintf("
                            SELECT * FROM ADMINSQL.rdg_view
                                WHERE datum ='%s' AND (cas>='%s' AND cas<='%s')
            
                             ",$dateTo,$timeFromStr,$timeToStr);
            
                $this->saveRdgData($query);
            }
            else {
            
                $query = sprintf("
                    
                            SELECT * FROM ADMINSQL.rdg_view
                                WHERE datum ='%s' AND (cas>='%s' AND cas<='2359')
                    
                    ", $dateFrom,$timeFrom);
                
                $this->saveRdgData($query);
                
                $query = sprintf("
                            SELECT * FROM ADMINSQL.rdg_view
                                WHERE datum ='%s' AND (cas>='0000' AND cas<='%s')
                    ", $dateTo,$timeTo);
                
                $this->saveRdgData($query);
            
            }
       
        
         
    }
    
    private function getLockStatus()
    {
        $result = true;
        $row = $this->db->sql_row("SELECT GET_LOCK('medea',5) AS [lock]");
        
        if ($row["lock"] == 0)
        {
            $result = false;
        }
        
        return $result;
        
    }
    
    private function saveRdgData($query)
    {
        $this->log->logData($query,false,"sql for medea fetch");
        $medeaCon = $this->openOdbc();
        
        $resource = odbc_exec($medeaCon, $query);
        
        $table = array();
        while ($table[] = odbc_fetch_array($resource));
        
        $this->log->logData($table,false,"data fetched from medea",false);
        
        odbc_close($medeaCon);
        
        $tableLn = count($table);
        $j=0;
        if ($tableLn>0)
        {
            $saveData = array();
            for ($i=0; $i<$tableLn;$i++)
            {
                if (is_array($table[$i]) && !empty($table[$i]))
                {
                    $saveData[$j] = array();
                    $saveData[$j]["hash"] = $this->makeHash($table[$j]);
                    
                    foreach ($table[$j] as $key=>$value)
                    {
                        switch ($key) {
                            
                            case "cas":
                                $saveData[$j]["cas"] = substr($value,0,2).":".substr($value,2,2).":00";
                                $saveData[$j]["cas_medea"] = $value;
                                break;
                            case "datum":
                                $saveData[$j]["datum"]  = $value." ".$saveData[$j]["cas"];
                                break;
                            case "B":
                                if (!isset($value) || strlen($value) == 0)
                                {
                                    $saveData[$j]["B"]= NULL;
                                }
                                else
                                {
                                    $saveData[$j]["B"] = $value;
                                }
                                
                                break;
                            case "P":
                                if (!isset($value) || strlen($value) == 0)
                                {
                                    $saveData[$j]["P"] = NULL;
                                }
                                else
                                {
                                    $saveData[$j]["P"] = $value;
                                }
                                break;
                            case "uzelzkr":
                                $saveData[$j]["uzol_kratko"] = $value;
                                break;
                            case "id_prac":
                                $saveData[$j]["id_pracoviska_uzol"] = $value;
                                break;
                            case "uzelnazov":
                                $saveData[$j]["uzol_nazov"] = $this->win2ascii($value);
                                break;
                            case "scpac":
                                $saveData[$j]["scpac"] = $value;
                                break;
                            case "sczad":
                                $saveData[$j]["sczad"] = $value;
                                break;
                            case "K":
                                if (!isset($value)|| strlen($value) == 0)
                                {
                                    $saveData[$j]["K"] = null;
                                }
                                else
                                {
                                    $saveData[$j]["K"] = $value;
                                }
                                break;
                            case "N":
                                if (!isset($value) || strlen($value) == 0)
                                {
                                    $saveData[$j]["N"] = null;
                                }
                                else
                                {
                                  $saveData[$j]["N"] = $value;
                                }
                                break;
                            case "A":
                                if (!isset($value) || strlen($value) == 0)
                                {
                                    $saveData[$j]["A"] = null;
                                }
                                else
                                {
                                    $saveData[$j]["A"] = $value;
                                }
                                break;
//                                 case "tohash":
//                                     saveData[i]["hash"] = x2.make_hash(row.Value.ToString());
//                                     break;
                        }
                    }
                    $j++;
                }
            }
        }
        //var_dump($saveData);
        $sDataLn = count($saveData);
        if ($sDataLn>0)
        {
            $res = $this->db->insert_rows("rdg_view_sync_6", $saveData, "IGNORE");
           // var_dump($res);
           // return;
            $saveDataLn = count($saveData);
            
            if ($res["status"])
            {
                $lastRow = $this->db->sql_row("SELECT [id] AS [last_id] FROM [rdg_view_sync_6] ORDER BY [id] DESC LIMIT 1");
                
                $logData = array();
                $logData["rdg_view_id"] = $lastRow["last_id"];
                $logData["last_date"] = $saveData[$saveDataLn-1]["datum"];
                $logData["last_time"]= $saveData[$saveDataLn-1]["cas"];
                $logData["succes"] =  "yes";
                
                $res1 = $this->db->insert_row("rdg_view_log_2", $logData);
                
                $this->smarty->assign("data","Import run OK");
                $this->smarty->display("index.tpl");
                
            }
            else
            {
                $logData = array();
                $logData["rdg_view_id"] = NULL;
                $logData["last_date"] = $saveData[$saveDataLn-1]["datum"];
                $logData["last_time"]= $saveData[$saveDataLn-1]["cas"];
                $logData["succes"] =  "no";
                
                $res1 = $this->db->insert_row("rdg_view_log_2", $logData);
                
                $this->smarty->assign("data","Check log with error");
                $this->smarty->display("index.tpl");
            }
        }
        else
        {
            $this->log->logData($saveData,false,"No data parsed from MEDEA",false);
        }
        
        $this->endTime = microtime(true);
        $scriptTime = $this->endTime-$this->startTime;
        
        $this->log->logData($scriptTime,false,"script running for {$scriptTime} seconds",false);
        
        
    }
    
    /*private function win2ascii($text)
    {
        return strtr($text,
            "\xe1\xe4\xe8\xef\xe9\xec\xed\xbe\xe5\xf2\xf3\xf6\xf5\xf4\xf8\xe0\x9a\x9d\xfa\xf9\xfc\xfb\xfd\x9e\xc1\xc4\xc8\xcf\xc9\xcc\xcd\xbc\xc5\xd2\xd3\xd6\xd5\xd4\xd8\xc0\x8a\x8d\xda\xd9\xdc\xdb\xdd\x8e",
            "aacdeeillnoooorrstuuuuyzAACDEEILLNOOOORRSTUUUUYZ"
        );
    
    }*/
     
    
    private function makeHash($data)
    {
        
        $pecko = "P";
        if (empty($data["P"]) || strlen($data["P"])==0)
        {
            $pecko="0";
        }
        
        $result = sprintf("%s-%s-%s-%s-%s-%s-%s",
                $pecko,
                $data["sczad"],
                $data["scpac"],
                $data["id_prac"],
                $data["cas"],
                $data["datum"],
                $data["uzelzkr"]
            );
        
        $result = md5($result);
        return $result;
    }
    
}

?>