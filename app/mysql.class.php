<?php 

class db {
	
	private $mysqli;
	var $server = '';
	var $user = '';
	var $passwd = '';
	var $dbase ='';
	//var $conn = new stdClass();
	
	function __construct($mysqli)
	{
		
		$this->mysqli = $mysqli;
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
	    /*$pattern = @"\[([a-zA-Z_0-9-]+)\.([a-zA-Z_0-9]+)\]";
	    Regex reg = new Regex(pattern);
	    
	    query = reg.Replace(query, @"`$1`.`$2`");
	    
	    pattern = @"\[([a-zA-Z_0-9-]+)\]";
	    reg = new Regex(pattern);
	    query = reg.Replace(query, @"`$1`");*/
	    
	    $pattern = "/\[([a-zA-Z_0-9-]+)\.([a-zA-Z_0-9]+)\]/";
	    $replacement = "`$1`.`$2`";
	    
	    $sql = preg_replace($pattern, $replacement, $sql);
	    
	    
	    $pattern = "/\[([a-zA-Z_0-9-]+)\]/";
	    $replacement = "`$1`";
	    $sql = preg_replace($pattern, $replacement, $sql);
	    
	    return $sql;
	    /*if (strpos($sql,"].[") !== false)
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
	    }*/
	    
	    
		
	}
	private function closeDb()
	{
	    //$this->mysqli->close();
	}
	
	
	function logData($what,$debug="LOG",$error=false)
	{
		if ($error == false)
		{
			$datum  = date("dmY");
			$fp = fopen("./log/{$datum}.log","a+");
			
			$str = date("d.m.Y H.i.s")."..........>{$debug}";
			$str .= "==========================================================================".PHP_EOL;
			$whatTmp = substr(print_r($what,true),0,500);
			
			$str .= $whatTmp.PHP_EOL."....truncated...".PHP_EOL;
			
			$str = str_replace(array("\r", "\n"), array('', "\r\n"), $str);
		
			fwrite($fp,$str);
			fclose($fp);
		}
		else
		{
			$datum  = date("dmY");
			$fp = fopen("./log/{$datum}_error.log","a+");
			
			$str = date("d.m.Y H.i.s")."..........>{$debug}";
			$str .= "==========================================================================".PHP_EOL;
			$str .= print_r($what,true).PHP_EOL;
			
			$str = str_replace(array("\r", "\n"), array('', "\r\n"), $str);
			
			fwrite($fp,$str);
			fclose($fp);
		}
		
	
	}
	
	
	/**
	 * Vykona formatovany sql prikaz nie charakteru SELECT ale napr. UPDATE, DELETE a pod
	 * 
	 * @param string $sql formatovany string
	 * @return boolean vrati ci sa vykonal spravne
	 */
	public function sql_execute($sql)
	{
		$res = array();
		$sql = $this->modifStr($sql);
		//var_dump($sql);
		
		$tmp = $this->mysqli->real_query($sql);
		$this->logData($sql,'');
		
		if (!$tmp)
		{
			//trigger_error('Chyba SQL: ' . $sql . ' Error: ' . $this->mysqli->error, E_USER_ERROR);
			$this->logData('Chyba SQL: ' . $sql .'  Error: ' . $this->mysqli->error,000,true);
			
			$res["status"] = false;
			
		}
		else{
		    $res["status"] = true;
		    $row = mysqli_affected_rows($this->mysqli);
		  //  var_dump($row);
		    $res["affected"] =$row;
		    //$res["row_id"]
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
			$this->logData($sql);
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
			
			$this->logData('Chyba SQL: ' . $sql . ' Error: ' . $this->mysqli->error,000,true);
			
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
			$this->logData($sql);
			$result['rows'] = $tmp->num_rows;
			
		}
		else
		{
			trigger_error('Chyba SQL: ' . $sql . ' Error: ' . $this->mysqli->error, E_USER_ERROR);
			$result['error'] = "Error SQL: {$sql}, ".$this->mysqli->error;
			$this->logData('Chyba SQL: ' . $sql . ' Error: ' . $this->mysqli->error,000,true);
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
			$this->logData($sql);
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
			$this->logData('Chyba SQL: ' . $sql . ' Error: ' . $this->mysqli->error,000,true);
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
			$this->logData('Chyba SQL: ' . $sql . ' Error: ' . $this->mysqli->error,000,true);
			$this->closeDb();
			return FALSE;
		}
		else
		{
			$this->logData($sql);
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
		        $this->logData('Chyba SQL: ' . $sql . ' Error: ' . $this->mysqli->error,000,true);
		        $result['status'] = FALSE;
		        //$this->closeDb();
		    }
	    else
		        {
	
		            $this->logData($sql);
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
	        $this->logData('Chyba SQL: ' . $sql . ' Error: ' . $this->mysqli->error,000,true);
	        $result['status'] = FALSE;
	        //$this->closeDb();
	    }
	    else
	    {
	         
	        $this->logData($sql);
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
	function insert_row_withoutDupKey($table,$data,$param="")
	{
	
	    $result = array();
	    $colLen = count($data);
	
	    $colArr = array();
	    $colValArr = array();
	   // $colUpArr = array();
	
	    $col_str = "";
	    $col_val = "";
	    //$col_update = "";
	    $i=0;
	
	    foreach ($data as $key=>$value)
	    {
	        	
	        $colArr[$i]      ="`{$key}`";
	        $colValArr[$i]   = "'{$this->mysqli->real_escape_string($value)}'";
	        //$colUpArr[$i]    = sprintf(" `%s` = VALUES(`%s`)",$key,$key);
	        	
	        $i++;
	    }
	
	    $col_str = implode(",",$colArr);
	    $col_val = implode(",",$colValArr);
	   // $col_update = implode(",",$colUpArr);
	
	    $sql = sprintf("INSERT %s INTO `%s` (%s) VALUES (%s)",$param,$table,$col_str,$col_val);
	
	    //$sql = $this->mysqli->real_escape_string($sql);
	
	    //echo $sql;
	    //return;
	    //$trans = $this->mysqli->autocommit(false);
	
	
	    if (!$tmp = $this->mysqli->query($sql))
	    {
	        // $this->mysqli->rollback();
	        $result['error'] = trigger_error('Chyba SQL: ' . $sql . ' Error: ' . $this->mysqli->error, E_USER_ERROR);
	        $this->logData('Chyba SQL: ' . $sql . ' Error: ' . $this->mysqli->error,000,true);
	        $result['status'] = FALSE;
	        //$this->closeDb();
	    }
	    else
	    {
	         
	        $this->logData($sql);
	        //$this->mysqli->commit();
	        $result['status'] = TRUE;
	        $result['last_id'] = mysqli_insert_id($this->mysqli);
	        	
	        	
	        //$this->closeDb();
	    }
	
	    //$tmp->free_result();
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
		//$trans = $this->mysqli->autocommit(false);
		
		
		if (!$tmp = $this->mysqli->query($sql))
		{
		   // $this->mysqli->rollback();
			$result['error'] = trigger_error('Chyba SQL: ' . $sql . ' Error: ' . $this->mysqli->error, E_USER_ERROR);
			$this->logData('Chyba SQL: ' . $sql . ' Error: ' . $this->mysqli->error,000,true);
			$result['status'] = FALSE;
			//$this->closeDb();
		}
		else
		{
		   
			$this->logData($sql);
			//$this->mysqli->commit();
			$result['status'] = TRUE;
			$result['last_id'] = mysqli_insert_id($this->mysqli);
			
			
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
	        $result['error'] = trigger_error('Chyba SQL: ' . $sql . ' Error: ' . $this->mysqli->error, E_USER_ERROR);
	        $this->logData('Chyba SQL: ' . $sql . ' Error: ' . $this->mysqli->error,000,true);
	        $result['status'] = FALSE;
	        //$this->closeDb();
	    }
	    else
	    {
	         
	        $this->logData($sql);
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