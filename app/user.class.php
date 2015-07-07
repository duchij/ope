<?php
class user extends app {
    
    function __construct()
    {
        parent::__construct();
    }
    
    public function init()
    {
        if ($this->getLockStatus())
        {
            $this->runImportUser();
            //$this->loadNewData(array());
        }
        else
        {
            $this->log->logData("get no lock on user sync","");
        }
            
    }
    
    private function getLockStatus()
    {
        $result = true;
        $row = $this->db->sql_row("SELECT GET_LOCK('user',5) AS [lock]");
    
        if ($row["lock"] == 0)
        {
            $result = false;
        }
    
        return $result;
    }
    
    private function runImportUser()
    {
        
        $res = $this->db->sql_row("SELECT * FROM [user_view_log] ORDER BY [date] DESC LIMIT 1");
//         var_dump($res);
        
       // return;
        
        if (!isset($res["id"]))
        {
            $this->startImportUsers();
        }
        else
        {
            $this->loadNewData($res);
        }
        
    }
    
    private function loadNewData($logData)
    {
        $medeaCon  = $this->openOdbc();
        $row = odbc_exec($medeaCon, "SELECT COUNT(*) AS 'users_c' FROM ADMINSQL.uzivatel_view");
       
        while ($data[] = odbc_fetch_array($row));
        odbc_close($medeaCon);
       if ($data[0]["users_c"] != $logData["user_row_count"])
       { 
            $this->startImportUsers();
       }
    } 
    
    private function startImportUsers()
    {
        $medeaCon = $this->openOdbc();
        $resource  = odbc_exec($medeaCon, "SELECT * FROM ADMINSQL.uzivatel_view");

        while ($table[] = odbc_fetch_array($resource));
        
       // var_dump($table);
       // return;
        
        odbc_close(($medeaCon));
        
        $tableLn = count($table);
        $saveData = array();
        if ($tableLn>0)
        {
            $j=0;
            for ($i=0;$i<$tableLn;$i++)
            {
                if (is_array($table[$i]))
                {
                
                    $saveData[$j] = array();
                    $saveData[$j]["medea_kod"] = $table[$i]["kod"];
                    $saveData[$j]["medea_id"] = $table[$i]["id"];
                    $saveData[$j]["surname"] = iconv("cp1250","utf-8",$table[$i]["prijmeni"]);
                    $saveData[$j]["name"] = iconv("cp1250", "utf-8", $table[$i]["meno"]);
                    $saveData[$j]["titel"] = $table[$i]["titul"];
                    $saveData[$j]["workname"] = iconv("cp1250","utf-8",$table[$i]["pracjmeno"]);
                    
                    $j++;
                }  
            }
            
            $res = $this->db->insert_rows("user_view_sync", $saveData, "IGNORE");
            
            if ($res["status"])
            {
               $userData = array();
               $userData["user_row_count"] = $tableLn-1;
               $res1 = $this->db->insert_row_nt("user_view_log", $userData);
               $this->log->logData("Full import OK","");
               echo 'ok';
            }
            else
            {
                
                $this->log->logData($res1,"first import error",true);
                echo 'error';
            }
            
        }
        
        
    }
    
    
}