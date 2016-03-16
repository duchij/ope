<?php
require_once 'mysql.class.php';
require_once 'log.class.php';

class main {
    
    var $includeDir = "./include";
    var $iniDir = "./local_settings";
    var $db;
    var $log;
    var $medeaCon;
    
    var $odbcUser = "user_sql6";
    var $odbcPass = "brana9262105";
    // Some examples show "Driver={FreeTDS};" but this will not work
    var $odbcDsn = "Driver={Progress OpenEdge 10.2B Driver};Server=10.10.0.4;Port=7887;Database=medea;hostname=medea;uid=user_sql6;password=brana9262105";
    
    var $pdo = "odbc:Driver={Progress OpenEdge 10.2B Driver};
        Server=10.10.0.4;
        Port=7887;
        Database=medea;
        hostname=medea;
        uid=user_sql6;
        password=brana9262105;";
    
    
    
    function __construct()
    {
//         setlocale(LC_ALL, "sk_SK.UTF-8");
        $iniData = parse_ini_file($this->iniDir."/settings.ini");
//         var_dump($iniData);
//         exit;
//         exit;
        $this->db = new db(new mysqli($iniData["server"],$iniData["user"],$iniData["password"],$iniData["db"]));
        $this->log = new log();
        
    }
    
    public function klinlog()
    {
        odbc_close_all();
        $this->medeaCon = odbc_connect($this->odbcDsn,$this->odbcUser,$this->odbcPass);
        if($this->medeaCon === false) {
            echo odbc_errormsg();
            exit;
        }
        else {
           $this->runKlinLog();
        }   
        
        
        
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
            $this->log->logData($row,"error from klinlog",true);
        }
        

    }
    
    private function importKlinLog($logRow)
    {
        if (isset($logRow["last_date"]))
        {
          //  echo "tu2";
            $dateLast = strtotime($logRow["last_date"]);
            $dateNow = time();
            
            $seconds = $dateNow - $dateLast;
            $hours = $seconds/60/60;
            
           
            $date1 = date("Y-m-d",$dateLast);
            $date2 = date("Y-m-d",$dateNow);
          
            //echo "...".$hours;
            //return;
            
            if ($date1 == $date2 )
            {
                
                
                
                $medeaFrom = date("H",$dateLast)."".date("i",$dateLast);
                $medeaTo =  date("H",$dateNow)."".date("i",$dateNow);
                
                if ($hours > 1)
                {
                    $medeaFrom = date("H",$dateNow-1200)."".date("i",$dateNow-1200);
                    $medeaTo =  date("H",$dateNow)."".date("i",$dateNow);
                }
                
                $query = sprintf("SELECT iduzivatel, datum, cas, uzel, uzelzkr, uzelnazov, scpac, pristup, upresnenie
                    FROM ADMINSQL.klinlog_view
                        WHERE (datum='%s')
                        AND (cas>='%s')
                        AND (cas<='%s')
                        AND (scpac<>0) ORDER BY cas ASC WITH(NOLOCK)",$date1,$medeaFrom,$medeaTo);
                
                $this->log->logData($query, "query na rovanek datumy",false);
//                 odbc_exec($this->medeaCon, "SET NAMES 'UTF8';");
//                 odbc_exec($this->medeaCon, "SET client_encoding='UTF-8';");
               
                $resource = odbc_exec($this->medeaCon,$query);
                while($table[] = odbc_fetch_array($resource));
                
                $this->log->logData($table,"rovnake datumy",false);
                odbc_close($this->medeaCon);
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
//                  odbc_exec($this->medeaCon, "SET NAMES 'UTF8';");
//                  odbc_exec($this->medeaCon, "SET client_encoding='UTF-8';");
                 $resource = odbc_exec($this->medeaCon,$query);
                 while($table[] = odbc_fetch_array($resource));
                 odbc_close($this->medeaCon);
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
//                  odbc_exec($this->medeaCon, "SET NAMES 'UTF8';");
//                  odbc_exec($this->medeaCon, "SET client_encoding='UTF-8';");
                 $resource = odbc_exec($this->medeaCon,$query);
                 
                 while($table[] = odbc_fetch_array($resource));
                 
                 $this->log->logData($table,"rovnake datumy",false);
                 odbc_close($this->medeaCon);
                 if (is_array($table[0]))
                 {
                    $this->saveData($table);
                 }
            }
            
            
            //exit;
        }
        else
        {
            $medeaDate = date("Y-m-d");
            $startTime = time()-(1200);
            $endTime = time();
            
            $medeaFrom = date("H",$startTime)."".date("i",$startTime);
            $medeaTo =  date("H",$endTime)."".date("i",$endTime);
            
//             echo $medeaFrom."   ".$medeaTo;
//             return;
            
            $query = sprintf( "SELECT iduzivatel, datum, cas, uzel, uzelzkr, uzelnazov, scpac, pristup, upresnenie
                    FROM ADMINSQL.klinlog_view
                        WHERE (datum='%s')
                        AND (cas>='%s')
                        AND (cas<='%s')
                        AND (scpac<>0) ORDER BY cas ASC WITH(NOLOCK)",$medeaDate,$medeaFrom,$medeaTo);
            $this->log->logData($query, "query na rovanek datumy",false);
            
            $resource = odbc_exec($this->medeaCon,$query);
            while($table[] = odbc_fetch_array($resource));
            odbc_close($this->medeaCon);
            if (is_array($table[0]))
            {
                $this->saveData($table);
            }
        }
        
    } 
    
    private function saveData($table)
    {
       // echo print_r($table,true);
        //var_dump($table);
      //  return;
        $this->log->logData($table,"table",false);
        $tableLn = count($table);
        $importData = array();
        
        for ($i=0; $i<$tableLn; $i++)
        {
            if (is_array($table[$i]))
            {
                $importData[$i] = array();
                $importData[$i]["hash"] = $this->makeHash($table[$i]);
        
            
                foreach ($table[$i] as $key=>$value)
                {
                    switch($key)
                    {
                        case "iduzivatel":
                            $importData[$i]["iduzivatel"] = $value;
                            break;
                        case "datum":
                            $mCas = $table[$i]["cas"];
                            $dCas = substr($mCas,0, 2).":".substr($mCas,2, 2).":".substr($mCas,4, 2);
                            $importData[$i]["datum"] = $value." ".$dCas;
                            break;
                        case "cas":
                            $importData[$i]["medea_cas"] = $value;
                            break;
                        case "uzel":
                            $importData[$i]["uzel"] = $value;
                            break;
                        case "uzelzkr":
                            $importData[$i]["uzelzkr"] = $value;
                            break;
                        case "uzelnazov":
                            $importData[$i]["uzelnazov"] = $value;
                            break;
                        case "scpac":
                            $importData[$i]["scpac"] = $value;
                            break;
                        case "pristup":
                            $importData[$i]["pristup"] = iconv("CP1250","UTF-8",$value);
//                             echo "<p>pristup: ".$value;
//                             echo "<br>.....".iconv("CP1250","UTF-8",$value);
//                             "<br></p>";
                            break;
                        case "upresnenie":
                          //  echo $value."<br>".mb_detect_encoding($value)."<br>";
                            $importData[$i]["upresnenie"] = iconv("CP1250","UTF-8",$value);
                           
//                             echo "<p>upresnenie: ".$value."</p>";
//                             echo "<br>.....".iconv("CP1250","UTF-8",$value);
//                             "<br></p>";
                         break;
                    }
                }
            }
            
        }
        //return;
        $this->log->logData($importData,"import",false);
       // return;
        //return;
        //ini_set('mssql.charset', 'UTF-8');
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
        
        $strMd5 = base64_encode($strMd5);
        
        return $strMd5;
        
    }  

    
}