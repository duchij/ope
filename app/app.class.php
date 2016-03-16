<?php 
//require_once './smarty/Smarty.class.php';
require_once 'log.class.php';
require_once 'mysql.class.php';
require_once './smarty/Smarty.class.php';



/**
 * @author Boris Duchaj
 * 
 * @version 0.0.1
 *
 */
class app {
	
	var $includeDir = "./include";
	var $iniDir = "./local_settings";
	var $user_id;
	var $user_email;
	
	var $db;
	var $log;
	var $omega;
	
	var $odbcUser;
	var $odbcPass;
	
	var $odbcDsn;
	
	var $odbcUser2;
	var $odbcPass2;
	
	var $odbcDsn2;
	
	// Some examples show "Driver={FreeTDS};" but this will not work
	/**
	 * Object defincie odbc openEdge 10.2b Connection 
	 * @var object
	 */
	var $medeaCon;
	var $medeaCon2;
	
	var $smarty;
	
	//var $odbc;
	
	var $LABELS = array();
	
	//var $formdes;
	
	
	public function __construct()
	{
	
	    putenv("ODBCINI=/opt/datadirect71/odbc.ini");
	    putenv("ODBCINST=/opt/datadirect71/odbcinst.ini");
		//$this->user_id = $_SESSION['libs']['user_id'];
		//$this->EXml = new Excel_XML();
		//$this->mail = new PHPMailer();
		$this->smarty = new Smarty();
		
		//$this->forms = new FormDes();
	
		$this->smarty->template_dir = './templates';
		$this->smarty->compile_dir = './templates/template_c';
		$this->smarty->cache_dir = './templates/cache';
		$this->smarty->config_dir = './templates/configs';
		
		$iniData = parse_ini_file($this->iniDir."/settings.ini");
		
		//var_dump($iniData);
		//exit;
		
		$this->db = new db(new mysqli($iniData["server"],$iniData["user"],$iniData["password"],$iniData["db"]));
		
		//$this->omega = new db(new mysqli($iniData["omega_server"],$iniData["omega_user"],$iniData["omega_passwd"],$iniData["omega_db"]));
		
		$this->log = new log();
		
		$this->odbcDsn = $iniData["odbc_dns"];
	    $this->odbcUser = $iniData["odbc_user"];
	    $this->odbcPwd = $iniData["odbc_passw"];
	    
	    $this->odbcDsn2 = $iniData["odbc_dns2"];
	    $this->odbcUser2 = $iniData["odbc_user2"];
	    $this->odbcPwd2 = $iniData["odbc_passw2"];
	    
	    //$this->medeaCon = $this->openOdbc();
	
	//	$this->_labels = new Labels();
	//	$this->LABELS = $this->_labels->getLabels();
	
	
	}
	
	/***
	 * Otvori openOdbc connection a vrati resource ak chyba tak konci
	 * @return resource
	 */
	public function openOdbc()
	{
	    odbc_close_all();
	//var_dump()
	    $res = odbc_connect("OpenEdgeOmega","user_sql6","brana9262105");
	
	    if ($res === false)
	    {
	        echo odbc_errormsg();
	        $this->logData(odbc_errormsg(),"error to log to medea",true);
	        exit;
	    }
	    return $res;
	}
	

	/***
	 * Otvori openOdbc connection a vrati resource ak chyba tak konci
	 * @return resource
	 */
	public function openOdbc2()
	{
	    odbc_close_all();
	
	    $res = odbc_pconnect($this->odbcDsn2,$this->odbcUser2,$this->odbcPass2);
	
	    if ($res === false)
	    {
	        echo odbc_errormsg();
	        $this->logData(odbc_errormsg(),"error to log to medea2",true);
	        exit;
	    }
	    return $res;
	}
	
	public function win2ascii($text)
	{
	    return strtr($text,
	        "\xe1\xe4\xe8\xef\xe9\xec\xed\xbe\xe5\xf2\xf3\xf6\xf5\xf4\xf8\xe0\x9a\x9d\xfa\xf9\xfc\xfb\xfd\x9e\xc1\xc4\xc8\xcf\xc9\xcc\xcd\xbc\xc5\xd2\xd3\xd6\xd5\xd4\xd8\xc0\x8a\x8d\xda\xd9\xdc\xdb\xdd\x8e",
	        "aacdeeillnoooorrstuuuuyzAACDEEILLNOOOORRSTUUUUYZ"
	    );
	
	}
	
	public function debug($data)
	{
	    $this->smarty->assign("debug",$data);
	    $this->smarty->display("main.tpl");
	}
	
	
	
// 	function app_init()
// 	{
// 		$this->abstr = new stdClass();
		
// 		$this->abstr->smarty = $this->smarty;
// 		$this->abstr->db = $this->db;
// 		$this->abstr->app = $this;
// 		//$this->abstr->LABELS = $this->LABELS;
		
// 		return $this->abstr;
// 	}
	
// 	public function loginUser($id)
// 	{
// 		$sql = sprintf("
// 				SELECT * FROM [usersdata]
// 				LEFT JOIN [users] ON [usersdata].[user_id] = [users].[id]
// 				WHERE [users].[id] = %d"
// 				,intval($id));
// 		return $this->db->sql_row($sql);
	
// 	}
	
	


	public function logout_fnc()
	{
	    $logut_html = $this->smarty->fetch('logout.tpl');
	    	
	    $fp = fopen("logout.html","w+");
	    	
	    fwrite($fp,$logut_html);
	    fclose($fp);
	    	
	    unset($_SESSION["libs"]);
	    session_destroy();
	    	
	    header("location:logout.html");
	     
	}
	
	
		
	/**
	 * Zakladna funkcia ktora predava REQUEST z formulara a dla toho vola metodu v triede
	 * 
	 * @param array $request  Pole $_REUQEST
	 * @param object $caller Objekt ktory zavolal fnc aby sa mu vratila 
	 * 
	 * @return boolean ak bola funkcia vo forme najdena..... inak false a treba to osetrit uz v triede
	 */
	public function run_app($request,$caller)
	{
		//var_dump($request);
	
		$result = false;
		foreach ($request as $key=>$value)
		{
			if (strpos($key,"_fnc") !== false)
			{
				$fnc = str_replace(array("_fnc_x","_fnc_y"),"_fnc",$key);
				//var_dump($caller);
				$result = true;
				$caller->$fnc($value,$request);
				//break;
			}
		}
		return $result;
	}
	
	
	

	//return app;
	
}

?>