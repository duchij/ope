<?php 

class slsimport extends app {
    
    var $dbf;
    
    function __construct() {
        parent::__construct();
        
    }
    
    public function init()
    {
        $this->startImport();
    }
    
    private function startImport()
    {
       $this->dbf = dbase_open("c:\\excel\\adresy.DBF",0);
        
        if ($this->dbf)
        {
            $this->startWork();
        }
        else
        {
            echo "error opening the database";
            dbase_close($this->dbf);
        }
    }
    
    
    private function startWork()
    {
        $numFl = dbase_numfields($this->dbf);
        
        $recLn = dbase_numrecords($this->dbf);
        
        
        
        for ($i=0;$i<$recLn;$i++)
        {
           $table = array();
           $saveData = array();
           
           $table = dbase_get_record_with_names($this->dbf,$i);
           
            if (isset($table["EVC"]) && preg_match("/[0-9]/",$table["EVC"]))
            {
           
               $saveData["evc"]     = $table["EVC"];
               $saveData["pmeno"]   = trim(iconv("CP1250", "UTF-8",$table["PMENO"]));
               
               $nameArr = explode(" ",$saveData["pmeno"]);
               
               $saveData["cmeno"] = trim($nameArr[1])." ".trim($nameArr[0]);
               
               $saveData["ulica"]   = trim(iconv("CP1250", "UTF-8",$table["ULICA"]));
               $saveData["mesto"]   = trim(iconv("CP1250", "UTF-8",$table["MESTO"]));
               $saveData["psc"]     = $table["PSC"];
               $saveData["katpopl"]     = $table["KATPOPL"];
               $saveData["datnar"]   = substr($table["DATNAR"],0,4)."-".substr($table["DATNAR"],4,2)."-".substr($table["DATNAR"],6,2);
               $saveData["vstupd"]   = substr($table["VSTUPD"],0,4)."-".substr($table["VSTUPD"],4,2)."-".substr($table["VSTUPD"],6,2);
               
               if ($table["deleted"] == "0")
               {
                    $saveData["deleted"] = 0;
               }
               else if ($table["deleted"] == "1")
               {
                   $saveData["deleted"] = 1;
               }
               
               $res = $this->db->insert_row("sls_dbf_import", $saveData);
               
               if (!$res["status"])
               {
                   $this->smarty->assign("data",print_r($res,true));
                   $this->smarty->display("debug.tpl");
                   exit;
               }
               else 
               {
                    $this->smarty->assign("data",print_r($table,true)."/r/n OK.... /r/n"); 
                    $this->smarty->display("debug.tpl");
               }
            }
           
        }
        
//         
    }
    
}
?>