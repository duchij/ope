<?php
class sls extends app {
    
    var $excelCon;

    function __construct(){
        parent::__construct();
    }
    
    
    function init()
    {
//       $this->openExcelFile();
            $this->combineNames();
    }
    
    
    
    private function openExcelFile($fileName="mena_sls.xls",$dir="excel")
    {
        $dsn = "Driver={Microsoft Excel Driver (*.xls)};DriverId=790;Dbq=C:\\{$dir}\\{$fileName};DefaultDir=c:\\{$dir};ReadOnly=0";
        $user = "";
        $password = "";
        odbc_close_all();
         
        $this->excelCon = odbc_connect($dsn, $user, $password);
        
        if (!$this->excelCon)
        {
            echo odbc_errormsg($this->excelCon);
            exit;
        }
        
        $rows = 16382;
        
        $query = "SELECT [Ev_c],[Meno],[Titul],[Ulica],[PSC],[Obec],[Datnar],[VstupdoOZ],[name],[surname] FROM [zoznam$]";
        
        $res = odbc_exec($this->excelCon, $query);
        
        while ($table[] = odbc_fetch_array($res));
        
        $this->log->logData($table,"co");
      // exit;
        //var_dump($table);
        //exit;
        
        odbc_close($this->excelCon);
        
        $tableCn = count($table)-1;
       
      // exit;
        $factor = 10;
        $nRuns = floor($tableCn / $factor);
        $part = $tableCn % $factor;
       
       //echo $nRuns."   ".$part;
       //exit;
       
       $runPart = false;
       
       if ($part > 0) {
           $runPart = true;
       }
       
       for ($r=0; $r<$nRuns;$r++)
       {
           echo "<br><br>";
           $j = 0;
           $saveData = array();
           
           $impl = 0;
           
           if ($r>0)
           {
               $impl=1;
           } 

            $from = $r*$factor+$impl;
            $to = $r*$factor+$factor;
            
            echo $r.".....".$from."......".$to."<br>";
            
            for ($i=$from;$i<=$to;$i++)
            {
               if (count($table[$i]>0))
               {
                  $saveData[$j] = array();
                    
                    $saveData[$j] = $this->parseData($table[$i]);
                    
                    $saveData[$j]["fullname"] = iconv("cp1250","utf-8",$table[$i]["name"]." ".$table[$i]["surname"]);
                    $saveData[$j]["name_ascii"] = $this->win2ascii($table[$i]["name"]);
                    $saveData[$j]["surname_ascii"] = $this->win2ascii($table[$i]["surname"]);
                    $saveData[$j]["fullname_ascii"] = $this->win2ascii($table[$i]["name"]." ".$table[$i]["surname"]);
                    
                    $j++;
               }
               
               
            }
            $this->combineNames($saveData);
            
//             $res = $this->saveData($saveData);
//            if (!$res["status"])
//            {
//                var_dump($res);
//                exit;
//            }
       }
       
       if ($runPart)
       {
           $from = $nRuns*$factor+1;
           $to = $from+$part-1;
           $j=0;
           echo "<p>".$r.".....".$from."......".$to."</p><br>";
           for ($i=$from;$i<$to;$i++)
           {
            
               if(count($table[$i])>0)
               {
                   $saveData[$j] = array();
           
                   $saveData[$j] = $this->parseData($table[$i]);
           
                   $saveData[$j]["fullname"] = iconv("cp1250","utf-8",$table[$i]["name"]." ".$table[$i]["surname"]);
                   $saveData[$j]["name_ascii"] = $this->win2ascii($table[$i]["name"]);
                   $saveData[$j]["surname_ascii"] = $this->win2ascii($table[$i]["surname"]);
                   $saveData[$j]["fullname_ascii"] = $this->win2ascii($table[$i]["name"]." ".$table[$i]["surname"]);
           
                   $j++;
               }
           }
           $this->combineNames($saveData);
//            $res = $this->saveData($saveData);
//            if (!$res["status"])
//            {
//                var_dump($res);
//                exit;
//            }
       }
    }
    
    private function saveData($data)
    {

        
        
       //$this->combineNames($data); 
        
       return $this->db->insert_rows_norm("cl_sls_doctors", $data);
      
    }
    
    private function combineNames()
    {
       
        $defaultTimeLimit = ini_get('max_execution_time');
        set_time_limit(2000);
       $rows = 14765;
//         $rows = 500;
       $count = 100;
       echo "time limit......".$defaultTimeLimit;
       echo "rows to import......{$rows}<br>";
      
       $nRuns = floor($rows/ $count);
       
       $part = $rows % $count;
       
       $runPart = false;
        
       if ($part > 0) {
           $runPart = true;
       }
       
       echo "full runs......{$nRuns}<br>";
       echo "left ove............{$part}<br>";
      // exit;
       
       for ($r=0; $r<= $nRuns; $r++)
       {
          
           if ($r==0)
           {
                $mOffset = 0;
           }
           else 
           {
               $mOffset = $r*$count;
           }
           
           $query = sprintf("SELECT * FROM [sls_dbf_import] LIMIT %d,%d",$mOffset,$count);
           
           echo $query."..<br>";
           
           
           $result = $this->omega->sql_table($query);
           
           if ($result["status"])
           {
            
               echo "sql to read from sls......{$query}<br>";
                $data = $result["table"];
                //for ($c=0;$c<10;$c++)
                //{
                $this->getSLSRowData($data);
                //}
           }
           else 
           {
               echo "error to read sql from sls......{$result["error"]}</br>";
               exit;
           }
           echo "<br>.............................................<br>";
       }
       
       if ($runPart)
       {
           $query = sprintf("SELECT * FROM [sls_dbf_import] LIMIT %d,%d",$nRuns*$count,$part);
           echo $query."..<br>";
           
           $result = $this->omega->sql_table($query);
            
           if ($result["status"])
           {
           
               echo "sql to read from sls......{$query}<br>";
               $data = $result["table"];
//                for ($c=0;$c<10;$c++)
//                {
                    $this->getSLSRowData($data);
//                }
           }
           else
           {
               echo "error to read sql from sls......{$result["error"]}</br>";
               exit;
           }
           
           
           echo "<br>.............................................<br>";
       }
       set_time_limit($defaultTimeLimit);
           
    }
    private function checkExistsEntry($name)
    {
        
        $result = 0;
        $query = sprintf("SELECT [cmeno],[evc] FROM [sls_dbf_import] WHERE [cmeno]='%s' GROUP BY [evc]",$name);
        
        $res = $this->omega->sql_table($query);
        
        if ($res["status"])
        {
            $result = count($res["table"]);
        }
        else 
        {
            $this->log->logData($result,"chyba pri count names",true);
            exit;
        }
        
        return $result;
    }
    
    
    private function getSLSRowData($data)
    {
        $dataCn = count($data);
        //var_dump($data);
        for ($i=0;$i<$dataCn;$i++)
        {
            $query = sprintf("
                                SELECT  [t_sls.evc] AS [sls_code], [t_sls.cmeno] AS [sls_name],
                                        [t_sls.datnar] AS [sls_birthdate], [t_sls.vstupd] AS [sls_entry], 
                        
                                        [t_hce.uuid] AS [hce_uuid],
                                        [t_hce.code] AS [medic_hce_code]
                                        
                                FROM [sls_dbf_import] AS [t_sls]
                        
                                   INNER JOIN [medic_cl_hce] AS [t_hce] ON [t_hce.name] = [t_sls.cmeno]
                                  -- INNER JOIN [medic_cl_hci_hce_data] AS [t_hci_hce] ON [t_hci_hce.hce_code] = [t_hce.code]
                                  -- INNER JOIN [medic_cl_hci] AS [t_hci] ON [t_hci.code] = [t_hci_hce.hci_code]
                        
                                WHERE [t_sls.cmeno] ='%s'
                
                ",$data[$i]["cmeno"]);
            
            $res = $this->omega->sql_table($query);
            $this->log->logData($res,"chre");
             
            if ($res["status"])
            {
                if (count($res["table"])>0)
                {
                    $res = $this->saveSyncData($res["table"]);
                    
                    if ($res["status"])
                    {
                        echo "ok";
                    }
                    else
                    {
                        echo "error....".var_dump($res);
                        exit;                        
                    }
                    
                }
                else
                {
                    $reversedName = $this->tryReversedName($data[$i]["cmeno"]);
                    
                    if (strlen($reversedName)>0)
                    {
                        $query = sprintf("
                                SELECT  [t_sls.evc] AS [sls_code], [t_sls.cmeno] AS [sls_name],
                                        [t_sls.datnar] AS [sls_birthdate], [t_sls.vstupd] AS [sls_entry], 
                        
                                        [t_hce.uuid] AS [hce_uuid],
                                        [t_hce.code] AS [medic_hce_code]
                                        
                                FROM [sls_dbf_import] AS [t_sls]
                        
                                   INNER JOIN [medic_cl_hce] AS [t_hce] ON [t_hce.name] = [t_sls.cmeno]
                                  -- INNER JOIN [medic_cl_hci_hce_data] AS [t_hci_hce] ON [t_hci_hce.hce_code] = [t_hce.code]
                                  -- INNER JOIN [medic_cl_hci] AS [t_hci] ON [t_hci.code] = [t_hci_hce.hci_code]
                        
                                WHERE [t_sls.cmeno] ='%s'
                        
                                        ",$reversedName);
                        
                        $res = $this->omega->sql_table($query);
                        
                        $this->log->logData($res,"chre");

                        if (count($res["table"])>0)
                        {
                            $res = $this->saveSyncData($res["table"],$reversedName);
                        
                            if ($res["status"])
                            {
                                echo "ok";
                            }
                            else
                            {
                                echo "error....".var_dump($res);
                                exit;
                            }
                        
                        }
                        else
                        {
                            $this->log->logData($data[$i],"empty data");
                            $res1 = $this->saveEmptyData($data[$i]);
                            
                            if ($res1["status"])
                            {
                                echo "..ok..";
                            }
                            else
                            {
                                echo "error....".var_dump($res1);
                                exit;
                            }
                        }
                        
                        
                        
                    }
                    
                    else 
                    {
                        $this->log->logData($data[$i],"empty data");
                        $res1 = $this->saveEmptyData($data[$i]);
                        
                        if ($res1["status"])
                        {
                            echo "..ok..";
                        }
                        else
                        {
                            echo "error....".var_dump($res1);
                            exit;
                        }
                    }
                }
            }
            else
            {
                $this->log->logData($res,false,"error in parse sls",true);
                var_dump($res);
                exit;
            }
            
            
            //$this->log->logData($table,"sync");
        }
      
    }
    
    private function tryReversedName($name)
    {
        $result = "";
        $nameArr = explode(" ",$name);
        
        if (count($nameArr)==2)
        {
            $result=$nameArr[1]." ".$nameArr[0];
        }
        
        return $result;
    }
    
    private function saveEmptyData($data)
    {
        
        $countNames = $this->checkExistsEntry($data["cmeno"]);
        
       
        $saveData = array(
            "sls_code"          =>$data["evc"],
            "sls_name"          =>$data["cmeno"],
            //"sls_titel"          =>$data["titul"],
            "sls_birthdate"     =>$data["datnar"],
            "sls_entry"         =>$data["vstupd"],
            "status"            =>"empty",
            "name_occur"        =>$countNames
            
        );
        
        return $this->omega->insert_row("sls_cl_hce", $saveData);
        
    }
    
    private function saveSyncData($data,$rName="")
    {
        
//         $this->log
        $dataCn = count($data);
        
        for ($i=0;$i<$dataCn;$i++)
        {
           // $data[$i]["sls_birthdate"] = $this->convertSlsDate($data[$i]["sls_birthdate"]);
            $data[$i]["sls_birthdate"] = $data[$i]["sls_birthdate"];
            $data[$i]["status"] = "ok";
            $data[$i]["name_occur"] = $this->checkExistsEntry($data[$i]["sls_name"]);
            if (strlen($rName)>0)
            {
                $data[$i]["sls_name"] = $rName;
            }
            
        }
        $this->log->logData($data,"data to save");
        //return;
        return $this->omega->insert_rows_norm("sls_cl_hce", $data,"IGNORE");
    }
    
    private function convertSlsDate($date)
    {
        $dtTmp = explode(".",$date);
        return "{$dtTmp[2]}-{$dtTmp[1]}-{$dtTmp[0]}";
    }
    
    private function modifStr($sql)
    {
        $what = array("[","]");
        return str_replace($what,"`",$sql);
    }
    
    private function parseData($data)
    {
        $saveData = array();
        
        foreach ($data as $key=>$value)
        {
            
            switch ($key)
            {
                case "Ev_c":
                    $saveData["sls_ev_num"] = $value;
                    break;
                case "Meno":
                    $saveData["sls_name"] = iconv("cp1250","utf-8",$value);
                    break;
                case "Titul":
                    $saveData["sls_titel"] = iconv("cp1250","utf-8",$value);
                    break;
                case "Ulica":
                    $saveData["sls_street"] = iconv("cp1250","utf-8",$value);
                    break;
                case "PSC":
                    $saveData["sls_zip"] = iconv("cp1250","utf-8",$value);
                    break;
                case "Obec":
                    $saveData["sls_city"] = iconv("cp1250","utf-8",$value);
                    break;
                case "Datnar":
                    $saveData["sls_birthdate"] = iconv("cp1250","utf-8",$value);
                    break;
                case "VstupdoOZ":
                    $saveData["sls_entry"] = iconv("cp1250","utf-8",$value);
                    break;
                case "name":
                    $saveData["name"] = iconv("cp1250","utf-8",$value);
                    break; 
                case "surname":
                    $saveData["surname"] = iconv("cp1250","utf-8",$value);
                    break;
            }
        }
        return $saveData;
    }
    
    
}
?>