<?php

class pac_imp {
    
    var $startTime;
    var $endTime;
    
    var $progressDb;
    
    var $progressUser;
    var $progressPasswd;
    
    function __construct()
    {
        //parent::__construct();
        $iniData = parse_ini_file("progress.ini");
        
        $this->progressDb   = $iniData["odbc_dns2"];
        $this->progressUser = $iniData["odbc_user2"];
        $this->progressPasswd = $iniData["odbc_passw2"];
    } 
    
    
    /***
     * Otvori openOdbc connection a vrati resource ak chyba tak konci
     * @return resource
     */
    private function openOdbc2()
    {
        putenv("ODBCINI=/opt/datadirect71/odbc.ini");
        putenv("ODBCINST=/opt/datadirect71/odbcinst.ini");
    
        //odbc_close_all();
    
        $res = odbc_pconnect($this->progressDb, $this->progressUser,$this->progressPasswd);
    
        if ($res === false)
        {
            echo odbc_errormsg();
            //$this->log->logData(odbc_errormsg(),false,"error to log to medea2",true);
            exit;
        }
        else
        {
            //$this->log->logData(odbc_errormsg(),true,"logged to progress db",false);
        }
        return $res;
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
    
    private function getTable($medeaCon,$query,$customSql)
    {
        $res = odbc_exec($medeaCon, $query);
        $colsName = array();
        
        if (!$customSql)
        {
            $colsName = $this->assocFields();
        }
        else 
        {
            $colsTmp = odbc_num_fields($res);
            
            for ($c=1;c<$colsTmp;$c++)
            {
                $colsName[$c] = odbc_field_name($res, $c);
            }
        }
        
        $colsLn = count($colsName);
        $result = array();
        $goRow = 0 ;
        
        while ($row = odbc_fetch_row($res))
        {
            $result[$goRow] = array();
            $rowTmpAprr = array();
            for ($col=1; $col<=$colsLn; $col++)
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
    /**
     * Funkcia vrati vysledky query smerom z Progress Tabulky Pacientov, ak sa neda ziaden parameter funkcia vyhlada vo vlastnej tabulke ci existuje posledne id_zmeny
     * ak nie vrati pacientov za posledne dve hodiny, v pripade pouzitia vlastneho sql funkcia prerata pozadovane nazvy poli v pripade ostatnych
     * vracia vsetky polia z tabulky, pozor nie je pouzity system select *
     * 
     * Funkcia uklada posledny id_zmeny z danej query do tabulky, pozri storeLastId
     * 
     * @param mixed[] $settings Array
     *       @param int last_id = progress last_id posledne idecko zo syncu / najrychlejsie
     *       @param int birth_num = rodne cislo bez lomky (najde vsetky zaznamy s rodnym cislom) - pomale
     *       @param date date = datum format rrrr-mm-dd vyhlada vsetky podla datumu spracovania
     *       @param string sql = vlastne sql, pozor tu sa prepocitavaju aj vlastne nazvy poli
     *       @param boolean storeLastId = ak true ulozi posledne ID_ZMENY do vlastnej tabulky ak false neulozi, pozor musi byt nadefinovane $this->db ev pozriet ina trieda na ukladanie
     *       
     * @return array startTime, endTime, totalTime, sqlUsed|string, customSql|string, results|array vysledok query,rowsCount|int pocet riadkov ziskanych z query
     */
    public function importData($settings=array())
    {       
           putenv("ODBCINI=/opt/datadirect71/odbc.ini");
    	   putenv("ODBCINST=/opt/datadirect71/odbcinst.ini");
    	   
    	   $storeLastId = true;
    	   
    	   if (isset($settings["storeLastId"]))
           {
                $storeLastId = $settings["storeLastId"]; 
           }
    	       
        $customSql = false;

        $medeaCon = $this->openOdbc2();
//exit;
        if (count($settings)==0)
        {
            if (isset($this->db))
            {
                $bRow = $this->getLastId();
            }
            
            if (isset($bRow["progress_id_zmeny"]) && !empty($bRow["progress_id_zmeny"]))
            {
                $query =  sprintf("
                    SELECT      ID_PACIENT_MEDEA, RODNE_CISLO, PRIEZVISKO, MENO, TITUL, ID_ADRESA_MEDEA, ULICA, PSC, MESTO, 
                                STAT, TELEFON, E_MAIL, STAT_PACIENTA, ID_POJISTENI_MEDEA, CISLO_POISTENCA, STAT_POISTENIA, PLATNOST_POISTENIA_OD, 
                                PLATNOST_POISTENIA_DO, ZDRAVOTNA_POISTOVNA, DATUM_ZPRACOVANIA, CAS_ZPRACOVANIA, DATUM_CAS_ZPRACOVANIA, ZMENA_TBL, ID_ZMENY
                        FROM ADMINSQL.VIEW_ZMENY_CR2
                          WHERE ID_ZMENY > '%d' ORDER BY ID_ZMENY WITH(nolock)"
                    
                    ,intval($bRow["progress_id_zmeny"]));
            }
            else 
            {
                $now = time() - 2*60*60;
                $toDay = date("Y-m-d H:i:s.000",$now);
                
                $query =  sprintf("
                        SELECT  ID_PACIENT_MEDEA, RODNE_CISLO, PRIEZVISKO, MENO, TITUL, ID_ADRESA_MEDEA, ULICA, PSC, MESTO, 
                                STAT, TELEFON, E_MAIL, STAT_PACIENTA, ID_POJISTENI_MEDEA, CISLO_POISTENCA, STAT_POISTENIA, PLATNOST_POISTENIA_OD, 
                                PLATNOST_POISTENIA_DO, ZDRAVOTNA_POISTOVNA, DATUM_ZPRACOVANIA, CAS_ZPRACOVANIA, DATUM_CAS_ZPRACOVANIA, ZMENA_TBL, ID_ZMENY
                            FROM ADMINSQL.VIEW_ZMENY_CR2
                               WHERE DATUM_CAS_ZPRACOVANIA > '%s' ORDER BY ID_ZMENY WITH(nolock)"
                    
                    ,$toDay);
            }
        }
        else if (isset($settings["last_id"]))
        {
            $query =  sprintf("
                    SELECT  ID_PACIENT_MEDEA, RODNE_CISLO, PRIEZVISKO, MENO, TITUL, ID_ADRESA_MEDEA, ULICA, PSC, MESTO, 
                            STAT, TELEFON, E_MAIL, STAT_PACIENTA, ID_POJISTENI_MEDEA, CISLO_POISTENCA, STAT_POISTENIA, PLATNOST_POISTENIA_OD, 
                            PLATNOST_POISTENIA_DO, ZDRAVOTNA_POISTOVNA, DATUM_ZPRACOVANIA, CAS_ZPRACOVANIA, DATUM_CAS_ZPRACOVANIA, ZMENA_TBL, ID_ZMENY
                        FROM ADMINSQL.VIEW_ZMENY_CR2
                          WHERE ID_ZMENY > '%d' ORDER BY ID_ZMENY WITH(nolock)"
                
                ,intval($settings["last_id"]));
        }
        else if (isset($settings["birth_num"]))
        {
            $query =  sprintf("
                    SELECT  ID_PACIENT_MEDEA, RODNE_CISLO, PRIEZVISKO, MENO, TITUL, ID_ADRESA_MEDEA, ULICA, PSC, MESTO, 
                            STAT, TELEFON, E_MAIL, STAT_PACIENTA, ID_POJISTENI_MEDEA, CISLO_POISTENCA, STAT_POISTENIA, PLATNOST_POISTENIA_OD, 
                            PLATNOST_POISTENIA_DO, ZDRAVOTNA_POISTOVNA, DATUM_ZPRACOVANIA, CAS_ZPRACOVANIA, DATUM_CAS_ZPRACOVANIA, ZMENA_TBL, ID_ZMENY
                        FROM ADMINSQL.VIEW_ZMENY_CR2
                          WHERE RODNE_CISLO = '%s' ORDER BY ID_ZMENY WITH(nolock)"
                
                ,intval($settings["birth_num"]));
        }
        else if (isset($settings["date"]))
        {
            $query = sprintf("
                SELECT      ID_PACIENT_MEDEA, RODNE_CISLO, PRIEZVISKO, MENO, TITUL, ID_ADRESA_MEDEA, ULICA, PSC, MESTO, 
                            STAT, TELEFON, E_MAIL, STAT_PACIENTA, ID_POJISTENI_MEDEA, CISLO_POISTENCA, STAT_POISTENIA, PLATNOST_POISTENIA_OD, 
                            PLATNOST_POISTENIA_DO, ZDRAVOTNA_POISTOVNA, DATUM_ZPRACOVANIA, CAS_ZPRACOVANIA, DATUM_CAS_ZPRACOVANIA, ZMENA_TBL, ID_ZMENY
                    FROM ADMINSQL.VIEW_ZMENY_CR2
                          WHERE DATUM_ZPRACOVANIA = '%s' ORDER BY ID_ZMENY WITH(nolock)
                
                ",$settings["date"]);
        }
        else if (isset($settings["sql"]))
        {
            $query = $settings["sql"];
            $customSql = true;
        }
        
        $table = array();
        
        
        $this->startTime = microtime(true);
        
        $table = $this->getTable($medeaCon, $query,$customSql);
        
        if (count($table)>0 && $storeLastId)
        {
            $pData = $table[count($table)-1];
            
            if (isset($this->db))
            {
                $this->saveLastId($pData);
            }
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
                    "rowsCount" =>count($table),
                    "customSql" =>$customSql
            
        );
        
       // $this->debug($result);
        
       //$this->log->logData($result,false,"query to medea db");
        
       return $result;
    }
}

?>
