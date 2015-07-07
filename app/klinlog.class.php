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
        
        if ($this->getLockStatus())
        {
            $this->iList = $this->loadIgnoreList();
            $this->log->logData($this->iList,"ilist");
            
            $this->runKlinLog();
        }
        else
        {
            $this->log->logData("get not get lock on klinlog sync",false,"",false);
            exit;
        }
        
    }
   
    
    /*KOniec verejnej sekcie*/
    
    
    
    
    /*Cast Klinlogu*/
    private function getLockStatus()
    {
        $result = true;
        $row = $this->db->sql_row("SELECT GET_LOCK('klinlog',5) AS [lock]");
    
        if ($row["lock"] == 0)
        {
            $result = false;
        }
    
        return $result;
    
    }
    
    
    /***
     * Nahra Ignor List, co nepotrebujeme nahrat a preklopit do klin sync logu
     * @return array: key=>ascii forma ignorovanej polozky, value=>yes/no ak je prazdny tak ignoruj
     */
    
    private function loadIgnoreList()
    {
        $res = $this->db->sql_table("SELECT [ignore_ascii],[ignore_empty] FROM [klinlog_ignore]");
        $table = $res["table"];
        $tableLn = count($table);
        $result = array();
        
        for ($i=0; $i<$tableLn; $i++)
        {
            $result[$table[$i]["ignore_ascii"]] = $table[$i]["ignore_empty"];
        }
        
        return $result;
    }
    
    
    private function runKlinLog()
    {
       
        $row = $this->db->sql_row("SELECT * FROM [klinlog_view_log] ORDER BY [last_date] DESC LIMIT 1");
        //print_r($row);
        //exit;
        if (!isset($row["error"]))
        {
            $this->importKlinLog($row);
        }
        else
        {
            $this->log->logData($row,false,"error from klinlog",true);
        }
        

    }
    
    private function importKlinLog($logRow)
    {
        if (isset($logRow["last_date"]))
        {
            $dateLast = strtotime($logRow["last_date"]);
            $dateNow = time();
            
            $seconds = $dateNow - $dateLast;
            $hours = $seconds/60/60;
            
           
            $date1 = date("Y-m-d",$dateLast);
            $date2 = date("Y-m-d",$dateNow);
          
            
            if ($date1 == $date2 )
            {
                $medeaFrom = date("H",$dateLast)."".date("i",$dateLast);
                $medeaTo =  date("H",$dateNow)."".date("i",$dateNow);
                
                if ($hours > 1)
                {
                    $medeaFrom = date("Hi",$dateNow-1200);
                    $medeaTo =  date("Hi",$dateNow);
                }
                
                $query = sprintf("SELECT iduzivatel, datum, cas, uzel, uzelzkr, uzelnazov, scpac, pristup, upresnenie
                    FROM ADMINSQL.klinlog_view
                        WHERE (datum='%s')
                        AND (cas>='%s')
                        AND (cas<='%s')
                        AND (scpac<>0) ORDER BY cas ASC WITH(NOLOCK)",$date1,$medeaFrom,$medeaTo);
                
                $this->log->logData($query, "query na rovanek datumy",false);
                
                $medeaCon = $this->openOdbc();
               
                $resource = odbc_exec($medeaCon,$query);
                while($table[] = odbc_fetch_array($resource))
                
                odbc_close($medeaCon);
                if (is_array($table[0]))
                {
                    $this->saveData($table);
                }
                
            }
            else
            {
                 $medeaFrom = date("H",$dateLast)."".date("i",$dateLast);
                 
                 $query = sprintf("SELECT iduzivatel, datum, cas, uzel, uzelzkr, uzelnazov, scpac, pristup, upresnenie
                    FROM ADMINSQL.klinlog_view
                        WHERE (datum='%s')
                        AND (cas>='%s')
                        AND (cas<='2359')
                        AND (scpac<>0) ORDER BY cas ASC WITH(NOLOCK)",$date1,$medeaFrom);
                 $medeaCon = $this->openOdbc();
                 $resource = odbc_exec($medeaCon,$query);
                 while($table[] = odbc_fetch_array($resource));
                 odbc_close($medeaCon);
                 if (is_array($table[0]))
                 {
                    $this->saveData($table);
                 }
                 
                 $medeaTo = date("H",$dateNow)."".date("i",$dateNow);
                 
                 $query = sprintf("SELECT iduzivatel, datum, cas, uzel, uzelzkr, uzelnazov, scpac, pristup, upresnenie
                    FROM ADMINSQL.klinlog_view
                        WHERE (datum='%s')
                        AND (cas>='0001')
                        AND (cas<='%s')
                        AND (scpac<>0) ORDER BY cas ASC WITH(NOLOCK)",$date2,$medeaTo);
                 $medeaCon = $this->openOdbc();
                 $resource = odbc_exec($medeaCon,$query);
                 
                 while($table[] = odbc_fetch_array($resource));
                 
                 odbc_close($medeaCon);
                 
                 if (is_array($table[0]))
                 {
                    $this->saveData($table);
                 }
            }
        }
        else
        {
            $medeaDate = date("Y-m-d");
            $startTime = time()-(1200);
            $endTime = time();  
            
            $medeaFrom = date("H",$startTime)."".date("i",$startTime);
            $medeaTo =  date("H",$endTime)."".date("i",$endTime);
            
            $query = sprintf( "SELECT iduzivatel, datum, cas, uzel, uzelzkr, uzelnazov, scpac, pristup, upresnenie
                    FROM ADMINSQL.klinlog_view
                        WHERE (datum='%s')
                        AND (cas>='%s')
                        AND (cas<='%s')
                        AND (scpac<>0) ORDER BY cas ASC WITH(NOLOCK)",$medeaDate,$medeaFrom,$medeaTo);
            $this->log->logData($query, "query na rovanek datumy",false);
            $medeaCon = $this->openOdbc();
            $resource = odbc_exec($medeaCon,$query);
            
            while($table[] = odbc_fetch_array($resource));
            
            odbc_close($medeaCon);
            if (is_array($table[0]))
            {
                $this->saveData($table);
            }
        }
        
    } 
    
    private function saveData($table)
    {
        $tableLn = count($table);
        $importData = array();
        $j=0;
        for ($i=0; $i<$tableLn; $i++)
        {
        
            if (is_array($table[$i]))
            {
                $pristup = $this->win2ascii($table[$i]["pristup"]);
                
                if (!isset($this->iList[$pristup]))
                {
                    $importData[$j] = array();
                    $importData[$j] = $this->setInData($table[$i]);
                    $j++;
                }
                if (isset($this->iList[$pristup]))
                {
                    if ($this->iList[$pristup] == "yes" && strlen($table[$i]["upresnenie"])>0)
                    {
                        $importData[$j] = array();
                        $importData[$j] = $this->setInData($table[$i]);
                        $j++;
                    }
                }
            }
        }
        $this->log->logData($importData,"import",false);
        $res = $this->db->insert_rows("klinlog_view_sync", $importData, "IGNORE");
        $impLn = count($importData);
        if ($res["status"])
        {
            $lastRow = $this->db->sql_row("SELECT [id] FROM [klinlog_view_sync] ORDER BY  [id] DESC LIMIT 1");
            $lData = array();
            
            $lData["last_date"] = $importData[$impLn-1]["datum"];
            $lData["klinlog_view_id"] = $lastRow["id"];
            $lData["succes"] = "yes";

            $this->db->insert_row("klinlog_view_log", $lData);
        }
        else
        {
            $lData = array();
            $lData["last_date"] = $importData[$impLn-1]["datum"];
            // $lData["klinlog_view_id"] = $res["last_id"];
            $lData["succes"] = "no";
            $lData["log_msg"]=$res["error"];

            $this->db->insert_row("klinlog_view_log", $lData);
        }
    }
    
    private function setInData($table)
    {
        $importData = array();
        $importData["hash"] = $this->makeHash($table);
        
        foreach ($table as $key=>$value)
        {
            switch($key)
            {
                case "iduzivatel":
                    $importData["iduzivatel"] = $value;
                    break;
                case "datum":
                    $mCas = $table["cas"];
                    $dCas = substr($mCas,0, 2).":".substr($mCas,2, 2).":".substr($mCas,4, 2);
                    $importData["datum"] = $value." ".$dCas;
                    break;
                case "cas":
                    $importData["medea_cas"] = $value;
                    break;
                case "uzel":
                    $importData["uzel"] = $value;
                    break;
                case "uzelzkr":
                    $importData["uzelzkr"] = $value;
                    break;
                case "uzelnazov":
                    $importData["uzelnazov"] = iconv("cp1250","UTF-8",$value);
                    break;
                case "scpac":
                    $importData["scpac"] = $value;
                    break;
                case "pristup":
                    $utfConv = iconv("cp1250","UTF-8",$value);
                    $importData["pristup"] = $utfConv;
                    $importData["pristup_ascii"] = $this->win2ascii($value);
                    break;
                case "upresnenie":
                    $importData["upresnenie"] = iconv("cp1250","UTF-8",$value);
                    break;
            }
        }
        
        return $importData;
    }
    
    /*Koniec Klinlog casti*/
   
    
    private function makeHash($data)
    {
        $str = sprintf("%s-%s-%s-%s-%s-%s-%s",
            $data["datum"],
            $data["cas"],
            $data["uzel"],
            $data["uzelzkr"],
            $data["scpac"],
            $data["pristup"],
            $data["upresnenie"]);
        
        $strMd5 = md5($str);
        
        //$strMd5 = base64_encode($strMd5);
        
        return $strMd5;
        
    }  
    
    
    
}