<?php
/*
 * Prva tabulka
 * CREATE TABLE `klinlog_sync_main` ( `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT, `iduzivatel` bigint(20) unsigned NOT NULL, `datum` date NOT NULL, `cas` bigint(20) NOT NULL, `uzel` int(11) NOT NULL, `uzelzkr` varchar(10) CHARACTER SET ascii NOT NULL, `scpac` bigint(20) unsigned NOT NULL, `hash` char(32) CHARACTER SET ascii NOT NULL, `started` datetime DEFAULT NULL, `ended` datetime DEFAULT NULL, PRIMARY KEY (`id`), UNIQUE KEY `hash` (`hash`), KEY `iduzivatel` (`iduzivatel`), KEY `datum` (`datum`), KEY `scpac` (`scpac`), KEY `started` (`started`), KEY `ended` (`ended`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_slovak_ci
 * 
 * Druha tabulka
 * CREATE TABLE `klinlog_sync_data` ( `main_hash` char(32) CHARACTER SET ascii NOT NULL, `datum` date NOT NULL, `cas` int(11) NOT NULL, `uzelnazov` text COLLATE utf8_slovak_ci NOT NULL, `pristup` varchar(30) COLLATE utf8_slovak_ci NOT NULL, `upresnenie` longtext COLLATE utf8_slovak_ci NOT NULL, UNIQUE KEY `main_hash_datum_cas` (`main_hash`,`datum`,`cas`), KEY `datum` (`datum`), KEY `medea_cas` (`cas`), KEY `main_id` (`main_hash`), CONSTRAINT `klinlog_sync_data_ibfk_1` FOREIGN KEY (`main_hash`) REFERENCES `klinlog_sync_main` (`hash`) ON DELETE CASCADE ON UPDATE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_slovak_ci
 *
 */

class klinLogSync {
    
    var $progressDb;
    var $progressData;
    
    function __construct(){
        $iniData = parse_ini_file("progress.ini");
        
        putenv("ODBCINI=".$iniData["ODBCINI"]);
        putenv("ODBCINST=".$iniData["ODBCINST"]);
        
        $this->progressData = array(
                "dns"   =>$iniData["odbc_dns"],
                "user"  =>$iniData["odbc_user"],
                "passwd"=>$iniData["odbc_passw"],
        );
    }
    
    private function openOdbcCon()
    {
        $this->progressDb = odbc_connect($this->progressData["dns"], $this->progressData["user"], $this->progressData["passwd"]);
    }
    
    private function closeOdbcCon()
    {
        odbc_close($this->progressDb);
    }
    
    private function getTable($query)
    {
        $res = odbc_exec($this->progressDb, $query);
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
    
    public function syncData()
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
        
        
        $this->openOdbcCon();
        $table = $this->getTable($query);
        $this->closeOdbcCon();
        
        foreach ($table as &$row)
        {
            $row["hash"] = $this->makeHash($row);
            $res = $this->updateRow($row);
            if ($res["affected"] == 0){
                $insData = array(
                    "iduzivatel"    =>$row["iduzivatel"],
                    "datum"         =>$row["datum"],
                    "cas"           =>$row["cas"],
                    "uzel"          =>$row["uzel"],
                    "uzelzkr"       =>$row["uzelzkr"],
                    "scpac"         =>$row["scpac"],
                    "hash"          =>$row["hash"],
                    "started"       =>$row["datum"]." ".$this->makeTime($row["cas"]),
                );
                
                $res1 = $this->db->insert("klinlog_sync_main", $insData,"IGNORE");
                
                if ($res1["status"]){
                    $insData1 = array(
                        "main_hash"     =>$row["hash"],
                        "datum"         =>$row["datum"],
                        "cas"           =>$row["cas"],
                        "uzelnazov"     =>$row["uzelnazov"],
                        "pristup"       =>$row["pristup"],
                        "upresnenie"    =>$row["upresnenie"],
                    );
                    // var_dump($insData1);
                    $res2 = $this->db->insert("klinlog_sync_data",$insData1,"IGNORE");
                }
                else {
                    $this->log($res1);
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
                $res2 = $this->db->insert("klinlog_sync_data",$insData1,"IGNORE");
                 
                if ($res2["status"]==false){
                    $this->log($res2);
                }
            }
        }
    }
    
}
?>