<?php

class paclog extends app {
    
    var $startTime;
    var $endTime;
    
    function __construct()
    {
        parent::__construct();
    } 
    
    public function init($data)
    {
        
        //var_dump(get_extension_funcs("iconv"));
        //echo iconv("cp1250","utf8","lalal");
        
        $this->importData($settings=array());
    }
    
    private function assocFields()
    {
        $fields = array(
                        1=>"ID_PACIENT_MEDEA",
                        2=>"RODNE_CISLO",
                        3=>"PRIEZVISKO",
                        4=>"MENO",
                        5=>"TITUL",
                        6=>"ID_ADRESA_MEDEA",
                        7=>"ULICA",
                        8=>"PSC",
                        9=>"MESTO",
                        10=>"STAT",
                        11=>"TELEFON",
                        12=>"E_MAIL",
                        13=>"STAT_PACIENTA",
                        14=>"ID_POJISTENI_MEDEA",
                        15=>"CISLO_POISTENCA",
                        16=>"STAT_POISTENIA",
                        17=>"PLATNOST_POISTENIA_OD",
                        18=>"PLATNOST_POISTENIA_DO",
                        19=>"ZDRAVOTNA_POISTOVNA",
                        20=>"DATUM_ZPRACOVANIA",
                        21=>"CAS_ZPRACOVANIA",
                        22=>"DATUM_CAS_ZPRACOVANIA",
                        23=>"ZMENA_TBL",
                        24=>"ID_ZMENY"
            
        );
        return $fields;
        
    }
    
    private function debug($data)
    {
        $this->smarty->assign("data",print_r($data,true));
        $this->smarty->display("index.tpl");
    }
    
    private function getTable($medeaCon,$query)
    {
        $res = odbc_exec($medeaCon, $query);
        $colsName = $this->assocFields();
        $colsLn = count($colsName);
        $result = array();
        $goRow = 0 ;
        while ($row = odbc_fetch_row($res))
        {
            $result[$goRow] = array();
            $rowTmpAprr = array();
            for ($col=1; $col<=$colsLn;$col++)
            {
                //$rowTmpArr[$colsName[$col]] = iconv("CP1250","UTF-8",(odbc_result($res,$col)));
                $rowTmpArr[$colsName[$col]] = iconv("cp1250","UTF-8",odbc_result($res,$col));
            }
            $result[$goRow] = $rowTmpArr;
            $goRow++;
        }
       //$this->debug($row);
        return $result;
        
    }
    /**
     * 
     * @param mixed $inData
     */
    public function getData($inData)
    {
        if (is_array($inData))
        {
            
        }
    }
    private function getLastId()
    {
        $query="SELECT [progress_id_zmeny] FROM [last_pac_sync] ORDER BY [id] DESC LIMIT 1";
        
        $row = $this->db->sql_row($query);
        
        return $row;
    }
    
    private function saveLastId($data)
    {
        $saveData = array(
            "progress_id_zmeny" => $data["ID_ZMENY"],
            "progress_datum_cas_zpracovania" => $data["DATUM_CAS_ZPRACOVANIA"],
            "progress_cislo_poistenca" =>$data["CISLO_POISTENCA"]
        );
        
        $res = $this->db->insert_row_nt("last_pac_sync", $saveData);
        
        if ($res["status"])
        {
            $this->log->logData($res,false,"inserted last patient data sync with progress",false);
        }
        else
        {
            $this->log->logData($res,true,"error inserted last patient data sync with progress",true);
        }
        
    }
    
    private function importData($settings)
    {       
       putenv("ODBCINI=/opt/datadirect71/odbc.ini");
	   putenv("ODBCINST=/opt/datadirect71/odbcinst.ini");


        $medeaCon = $this->openOdbc2();
//exit;
        if (count($settings)==0)
        {
            $bRow = $this->getLastId();
            
            if (isset($bRow["progress_id_zmeny"]) && !empty($bRow["progress_id_zmeny"]))
            {
                $query =  sprintf("
                    SELECT *
                        FROM ADMINSQL.VIEW_ZMENY_CR2
                          WHERE ID_ZMENY > '%d' ORDER BY ID_ZMENY WITH(nolock)"
                    ,intval($bRow["progress_id_zmeny"]));
            }
            else 
            {
                $now = time() - 2*60*60;
                $toDay = date("Y-m-d H:i:s.000",$now);
                
                $query =  sprintf("
                        SELECT *
                            FROM ADMINSQL.VIEW_ZMENY_CR2
                               WHERE DATUM_CAS_ZPRACOVANIA > '%s' ORDER BY ID_ZMENY WITH(nolock)"
                    ,$toDay);
            }
        }
        else if (isset($settings["last_id"]))
        {
            $query =  sprintf("
                    SELECT *
                        FROM ADMINSQL.VIEW_ZMENY_CR2
                          WHERE ID_ZMENY > '%d' ORDER BY ID_ZMENY WITH(nolock)"
                ,intval($settings["last_id"]));
        }
        else if (isset($settings["birth_num"]))
        {
            $query =  sprintf("
                    SELECT *
                        FROM ADMINSQL.VIEW_ZMENY_CR2
                          WHERE RODNE_CISLO = '%s' ORDER BY ID_ZMENY WITH(nolock)"
                ,intval($settings["birth_num"]));
        }
        else if (isset($settings["date"]))
        {
            $query = sprintf("
                SELECT *
                    FROM ADMINSQL.VIEW_ZMENY_CR2
                          WHERE DATUM_ZPRACOVANIA = '%s' ORDER BY ID_ZMENY WITH(nolock)
                ",$settings["date"]);
        }
        else if (isset($settings["sql"]))
        {
            $query = $settings["sql"];
        }
        
        $table = array();
        
        
        $this->startTime = microtime(true);
        
        $table = $this->getTable($medeaCon, $query);
        if (count($table)>0)
        {
            $pData = $table[count($table)-1];
            $this->saveLastId($pData);
        }
        $this->endTime = microtime(true);
        
        //odbc_free_result($medeaCon);
        
        $totalTime = $this->endTime-$this->startTime;
        
        $result = array(
                    "startTime" => $this->startTime,
                    "endTime"   => $this->endTime,
                    "results"   => $table,
                    "totalTime" => $totalTime,
                    "usedSql"   => $query,
                    "rowsCount"=>count($table)
            
        );
        
       // $this->debug($result);
        
       $this->log->logData($result,false,"query to medea db");
        
       return $result;
    }
}

?>
