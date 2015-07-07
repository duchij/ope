<?php 
require_once 'log.class.php';
class db {
	
	private $mysqli;
	var $server = '';
	var $user = '';
	var $passwd = '';
	var $dbase ='';
	var $log;
	//var $conn = new stdClass();
	
	function __construct($mysqli)
	{
		
		$this->mysqli = $mysqli;
		$this->log = new log();
	}
	
	
	
	/**
	 * Vrati upraveny sql string uz na pracu s db
	 * 
	 * @param string $sql formatovany string
	 * @return string formatovany retazec
	 * 
	 * @todo pridat lepsie parsovanie
	 */
	private function modifStr($sql)
	{
	    if (strpos($sql,"].[") !== false)
	    {
	        $sql = str_replace("].[", "`.`", $sql);
	        $what = array("[","]");
	        $sql = str_replace($what,"`",$sql);
	    }
	    else
	    {
	        $sql = str_replace(".", "`.`", $sql);
	        $what = array("[","]");
	        $sql =  str_replace($what,"`",$sql);
	    }
	    
	    
		
		return $sql;
	}
	private function closeDb()
	{
	    //$this->mysqli->close();
	}
	
	
	/**
	 * Vykona formatovany sql prikaz nie charakteru SELECT ale napr. UPDATE, DELETE a pod
	 * 
	 * @param string $sql formatovany string
	 * @return boolean vrati ci sa vykonal spravne
	 */
	public function sql_execute($sql)
	{
		$res = true;
		$sql = $this->modifStr($sql);
		
		
		$tmp = $this->mysqli->real_query($sql);
		$this->log->logData($sql,false,' mysql execute');
		
		if (!$tmp)
		{
			//trigger_error('Chyba SQL: ' . $sql . ' Error: ' . $this->mysqli->error, E_USER_ERROR);
			$this->log->logData('Chyba SQL: ' . $sql .'  Error: ' . $this->mysqli->error,false,"error in mysql execute",true);
			$res = false;
		}
		
		return $res;
		
	}
	
	/**
	 * Vrati tabulku vlozeneho selectu vo forme pola
	 * 
	 * @param string $sql formatovany string
	 * 
	 * @return array vrati asociativne pole v table je samotna tabulka 
	 */
	public function sql_table($sql)
	{
		$result = array("status"=>TRUE,"table"=>array(),"error"=>'');
		
		$sql = $this->modifStr($sql);
		$tmp = $this->mysqli->query($sql);
		
		if ($tmp)
		{
			$this->log->logData($sql,true,"sql_table");
			$num_rows =$tmp->num_rows;
			
			for ($i=0; $i<$tmp->num_rows; $i++)
			{
				$tmp->data_seek($i);
				$row = $tmp->fetch_array(MYSQL_ASSOC);
				array_push($result['table'],$row);
			}
			
			$tmp->free_result();
		}
		else
		{
			
			//trigger_error('Chyba SQL: <p>' . $sql . '</p> Error: ' . $this->mysqli->error);
			$result['status'] = false;
			$result['error'] = "SQL:<p>{$sql}</p>, error:<p>{$this->mysqli->error}</p>";
			
			$this->log->logData('Chyba SQL: ' . $sql . ' Error: ' . $this->mysqli->error,false,"error in sql_table fnc",true);
			
			//$tmp->free_result();
		}
		
		//$tmp->close();
		$this->closeDb();
		return $result;
	
	}
	
	public function sql_count_rows($sql)
	{
		$result = array();
		$sql = $this->modifStr($sql);
		$tmp = $this->mysqli->query($sql);
		if ($tmp)
		{
			$this->log->logData($sql,false,"sql for count rows");
			$result['rows'] = $tmp->num_rows;
			
		}
		else
		{
			trigger_error('Chyba SQL: ' . $sql . ' Error: ' . $this->mysqli->error, E_USER_ERROR);
			$result['error'] = "Error SQL: {$sql}, ".$this->mysqli->error;
			$this->log->logData('Chyba SQL: ' . $sql . ' Error: ' . $this->mysqli->error,false,"error in sql_count_rows",true);
		}
		
		//print_r($result);
		$this->closeDb();
		return $result;
	}
	
	/**
	 * Vrati jeden riadok z DB
	 * 
	 * @param string $sql formatovane query
	 * 
	 * @return array vrati riadok ako jednoduche pole
	 */
	public function sql_row($sql)
	{
	    
	    
		$result = array();
		$sql = $this->modifStr($sql);
		
		if (strpos($sql,"LIMIT") == FALSE)
		{
		    $sql .= " LIMIT 1";
		}
		
		
		
		$tmp = $this->mysqli->query($sql);
		if ($tmp)
		{
			$this->log->logData($sql,false,"sql_row fnc");
			$row = $tmp->fetch_assoc();
			if (is_array($row))
			{
				foreach ($row as $key=>$value)
				{
					$result[$key] = $value;
				}
			}
		}
		else
		{
			trigger_error("Error SQL: {$sql} <br> ".$this->mysqli->error);
			$result['error'] = "Error SQL: {$sql}<br> ".$this->mysqli->error;
			$this->log->logData('Chyba SQL: ' . $sql . ' Error: ' . $this->mysqli->error,false,"sql_row_fnc error",true);
		}
		$tmp->free_result();
		//print_r($result);
		return $result;
	
	}
	
	
	public function sql_new_row($table,$data)
	{
		$this->openDb();
		$colLen = count($data);
		$col_str = "";
		$col_val = "";
		$i=0;
		foreach ($data as $key=>$value)
		{
			if (($i+1) < $colLen)
			{
				$col_str .="`{$key}`,";
				$col_val .= "'{$value}',";
			}
			else
			{
				$col_str .="`{$key}`";
				$col_val .= "'{$value}'";
			}
		
			$i++;
		}
		$sql = sprintf("INSERT INTO `%s` (%s) VALUES (%s)",$table,$col_str,$col_val);
	
		if (!mysqli_query($this->dbLink,$sql))
		{
			$this->write_page('error',$sql."-".mysqli_error());
			$this->log->logData('Chyba SQL: ' . $sql . ' Error: ' . $this->mysqli->error,false,"error in sql_new row",true);
			$this->closeDb();
			return FALSE;
		}
		else
		{
			$this->log->logData($sql,false,"sql in sql_new_row_fnc");
			$this->closeDb();
			return TRUE;
		}
	
	}
	/**
	 * Vlozi pole riadkov do tabulky v transakci bez on key update value
	 *
	 * @param string $table nazov tabulky
	 * @param array $data zlozene pole
	 *
	 * @return array:mixed status:boolean, last_id:integer, error:string
	 */
	public function insert_rows_norm($table,$data,$parameter="")
	{
	    $result = array();
	     
	    $colLen = count($data);
	     
	    $colArr = array();
	    $colValArr = array();
	    $colUpArr = array();
	     
	    $col_str = "";
	    $col_val = "";
	    $col_update = "";
	    $i=0;
	     
	     
	     
	    for ($row=0; $row<$colLen; $row++)
	    {
    	    $tmpArr = array();
    	    $r=0;
    	    foreach ($data[$row] as $key=>$value)
    	    {
    	       if ($value != NULL)
    	        {
    	            $tmpArr[$r] = "'{$this->mysqli->real_escape_string($value)}'";
    	        }
    	        else
                {
    	            $tmpArr[$r]='NULL';
    	        }
    	     
    	    // 	            $tmpArr[$r] = "'{$value}'";
    	       $r++;
    	    }
    	    $colValArr[$row] = "(".implode(",",$tmpArr).")";
	    }
	     
	     
	    foreach ($data[0] as $key=>$value)
	    {
	
	    $colArr[$i]      ="`{$key}`";
// 	    //$colValArr[$i]   = "'{$this->mysqli->real_escape_string($value)}'";
// 	        $colUpArr[$i]    = sprintf(" `%s` = VALUES(`%s`)",$key,$key);
	
	        $i++;
	    }
	     
	    $col_str = implode(",",$colArr);
	    $col_val = implode(",",$colValArr);
	    $col_update = implode(",",$colUpArr);
	     
	    $sql = sprintf("INSERT %s INTO `%s` (%s) VALUES %s ",$parameter,$table,$col_str,$col_val);
	     
	    //$sql = $this->mysqli->real_escape_string($sql);
	  
	    //echo $sql;
		    //return;
	    $trans = $this->mysqli->autocommit(false);
	     
	     
	    if (!$tmp = $this->mysqli->query($sql))
	    {
	        $this->mysqli->rollback();
	       $result['error'] = 'Chyba SQL: ' . $sql . ' Error: ' . $this->mysqli->error. '....'. E_USER_ERROR;
	        $this->log->logData('Chyba SQL: ' . $sql . ' Error: ' . $this->mysqli->error,false,"error in insert_rows_norm ",true);
	        $result['status'] = FALSE;
	        //$this->closeDb();
	    }
        else
        {
	
            $this->log->logData($sql,false,"sql in insert_rows_norm");
            $this->mysqli->commit();
            $result['status'] = TRUE;
            $result['last_id'] = $this->mysqli->insert_id;
             
            //$this->closeDb();
        }
		             
		            //free_result();
        $this->closeDb();
        return $result;
    }
	
	
	/**
	 * Vlozi pole riadkov do tabulky v transakci s on key update value
	 * 
	 * @param string $table nazov tabulky
	 * @param array $data zlozene pole 
	 * 
	 * @return multitype:boolean NULL 
	 */
	public function insert_rows($table,$data,$parameter="")
	{
	    $result = array();
	    
	    $colLen = count($data);
	    
	    $colArr = array();
	    $colValArr = array();
	    $colUpArr = array();
	    
	    $col_str = "";
	    $col_val = "";
	    $col_update = "";
	    $i=0;
	    
	    
	    
	    for ($row=0; $row<$colLen; $row++)
	    {
	        $tmpArr = array();
	        $r=0;
	        foreach ($data[$row] as $key=>$value)
	        {
	            if ($value != NULL)
	            {
	               $tmpArr[$r] = "'{$this->mysqli->real_escape_string($value)}'";
	            }
	            else
	            {
	                $tmpArr[$r]='NULL';
	            }
	            
// 	            $tmpArr[$r] = "'{$value}'";
	           $r++;
	        }
	        $colValArr[$row] = "(".implode(",",$tmpArr).")";
	    }
	    
	    
	    foreach ($data[0] as $key=>$value)
	    {
	        	
	        $colArr[$i]      ="`{$key}`";
	        //$colValArr[$i]   = "'{$this->mysqli->real_escape_string($value)}'";
	        $colUpArr[$i]    = sprintf(" `%s` = VALUES(`%s`)",$key,$key);
	        	
	        $i++;
	    }
	    
	    $col_str = implode(",",$colArr);
	    $col_val = implode(",",$colValArr);
	    $col_update = implode(",",$colUpArr);
	    
	    $sql = sprintf("INSERT %s INTO `%s` (%s) VALUES %s ON DUPLICATE KEY UPDATE %s",$parameter,$table,$col_str,$col_val,$col_update);
	    
	    //$sql = $this->mysqli->real_escape_string($sql);
	    
	    //echo $sql;
	    //return;
	    $trans = $this->mysqli->autocommit(false);
	    
	    
	    if (!$tmp = $this->mysqli->query($sql))
	    {
	        $this->mysqli->rollback();
	        $result['error'] = 'Chyba SQL: ' . $sql . ' Error: ' . $this->mysqli->error. '....'. E_USER_ERROR;
	        $this->log->logData('Chyba SQL: ' . $sql . ' Error: ' . $this->mysqli->error,false,"error in insert_rows",true);
	        $result['status'] = FALSE;
	        //$this->closeDb();
	    }
	    else
	    {
	         
	        $this->log->logData($sql,false,"sql from insert_rows");
	        $this->mysqli->commit();
	        $result['status'] = TRUE;
	        $result['last_id'] = $this->mysqli->insert_id;
	        
	        //$this->closeDb();
	    }
	    
	    //free_result();
	    $this->closeDb();
	    return $result;
	}
	
	/**
	 * Vlozi jeden riadok do vybranej tabulky v transakci s on key update value
	 *
	 * @param string $table nazov tabulky kam vlozit
	 * @param array $data jednoduche pole,
	 *
	 * @return boolean
	 */
	function insert_row($table,$data)
	{
		
		$result = array();
		$colLen = count($data);
		
		$colArr = array();
		$colValArr = array();
		$colUpArr = array();
		
		$col_str = "";
		$col_val = "";
		$col_update = "";
		$i=0;
		
		foreach ($data as $key=>$value)
		{
			
			$colArr[$i]      ="`{$key}`";
			$colValArr[$i]   = "'{$this->mysqli->real_escape_string($value)}'";
			$colUpArr[$i]    = sprintf(" `%s` = VALUES(`%s`)",$key,$key);
			
			$i++;
		}
		
		$col_str = implode(",",$colArr);
		$col_val = implode(",",$colValArr);
		$col_update = implode(",",$colUpArr);
		
		$sql = sprintf("INSERT INTO `%s` (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s",$table,$col_str,$col_val,$col_update);
		
		//$sql = $this->mysqli->real_escape_string($sql);
		
		//echo $sql;
		//return;
		$trans = $this->mysqli->autocommit(false);
		
		
		if (!$tmp = $this->mysqli->query($sql))
		{
		    $this->mysqli->rollback();
			$result['error'] = trigger_error('Chyba SQL: ' . $sql . ' Error: ' . $this->mysqli->error, E_USER_ERROR);
			$this->log->logData('Chyba SQL: ' . $sql . ' Error: ' . $this->mysqli->error,false,"error in insert_row",true);
			$result['status'] = FALSE;
			//$this->closeDb();
		}
		else
		{
		   
			$this->log->logData($sql,false,"sql from insert_row");
			$this->mysqli->commit();
			$result['status'] = TRUE;
			$result['last_id'] = $this->mysqli->insert_id;
			
			
			//$this->closeDb();
		}
		
	    //$tmp->free_result();
	    $this->closeDb();
		return $result;
	}
	
	/**
	 * Vlozi jeden riadok do vybranej tabulky bez transakcie s on key update value
	 *
	 * @param string $table nazov tabulky kam vlozit
	 * @param array $data jednoduche pole,
	 *
	 * @return boolean
	 */
	
	function insert_row_nt($table,$data)
	{
	
	    $result = array();
	    $colLen = count($data);
	
	    $colArr = array();
	    $colValArr = array();
	    $colUpArr = array();
	
	    $col_str = "";
	    $col_val = "";
	    $col_update = "";
	    $i=0;
	
	    foreach ($data as $key=>$value)
	    {
	        	
	        $colArr[$i]      ="`{$key}`";
	        $colValArr[$i]   = "'{$this->mysqli->real_escape_string($value)}'";
	        $colUpArr[$i]    = sprintf(" `%s` = VALUES(`%s`)",$key,$key);
	        	
	        $i++;
	    }
	
	    $col_str = implode(",",$colArr);
	    $col_val = implode(",",$colValArr);
	    $col_update = implode(",",$colUpArr);
	
	    $sql = sprintf("INSERT INTO `%s` (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s",$table,$col_str,$col_val,$col_update);
	
	    //$sql = $this->mysqli->real_escape_string($sql);
	
	    //echo $sql;
	    //return;
	   // $trans = $this->mysqli->autocommit(false);
	
	
	    if (!$tmp = $this->mysqli->query($sql))
	    {
	        //$this->mysqli->rollback();
	        $result['error'] = 'Chyba SQL: ' . $sql . ' Error: ' . $this->mysqli->error;
	        $this->log->logData('Chyba SQL: ' . $sql . ' Error: ' . $this->mysqli->error,false,"error in insert_row_nt ",true);
	        $result['status'] = FALSE;
	        //$this->closeDb();
	    }
	    else
	    {
	         
	        $this->log->logData($sql,false,"sql in insert_row_nt");
	        $result['status'] = TRUE;
	        $result['last_id'] = $this->mysqli->insert_id;
	       // $this->mysqli->commit();
	        //$this->closeDb();
	    }
	
	    //$tmp->free_result();
	    $this->closeDb();
	    return $result;
	}
	
}

?>