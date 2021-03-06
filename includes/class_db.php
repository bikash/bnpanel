<?php
/* For licensing terms, see /license.txt */

//Create the class
class db {
	private $sql = array(), $con, $prefix, $db; #Variables, only accesible in class
		
	public function __construct() { # Connect SQL as class is called
	
		include INCLUDES.'conf.inc.php'; # Get the config
		$this->sql = $sql; # Assign the settings to DB Class
		$this->con = @mysql_connect($this->sql['host'], $this->sql['user'], $this->sql['pass']); #Connect to SQL		
		if (!$this->con) { # If SQL didn't connect
			die("Fatal: Coudn't connect to mySQL, please check your details!");
		} else {
			$this->db = @mysql_select_db($this->sql['db'], $this->con); # Select the mySQL DB			
			if (!$this->db) {
				//@todo show a nicer msg
				die("Fatal: Couldn't select the database, check your db setting in includes/conf.inc.php  !");
			} else {
				$this->prefix = $this->sql['pre'];
			}
		}
	}
	
	private function error($name, $mysqlerror, $func) { #Shows a SQL error from main class
		$error['Error'] = $name;
		$error['Function'] = $func;
		$error['mySQL Error'] = $mysqlerror;
		
		global $main;
		$main->error($error);
	}
	
	/**
	 * Returns the error number from the last operation done on the database server.
	 * @param resource $connection (optional)	The database server connection, for detailed description see the method query().
	 * @return int								Returns the error number from the last database (operation, or 0 (zero) if no error occurred.
	 */
	public static function errno($connection = null) {
		return self::use_default_connection($connection) ? mysql_errno() : mysql_errno($connection);
	}
	
	private static function use_default_connection($connection) {
		return !is_resource($connection) && $connection !== false;
	}
	
	/**
	 * Returns the error text from the last operation done on the database server.
	 * @param resource $connection (optional)	The database server connection, for detailed description see the method query().
	 * @return string							Returns the error text from the last database operation, or '' (empty string) if no error occurred.
	 */
	public static function error_mysql($connection = null) {
		return self::use_default_connection($connection) ? mysql_error() : mysql_error($connection);
	}	
	
	/** 
	 * Runs any query and return the results 
	 * @param string 	sql query
	 * @return resource the mysql_query return 
	 * @author Julio Montoya <gugli100@gmail.com> Added some nice error reporting
	 */
	public function query($sql) { 
		$sql = preg_replace("/<PRE>/si", $this->prefix, $sql); #Replace prefix variable with right value
				
		$result = mysql_query($sql, $this->con);
			
		if(!$result) {
			//$this->error("mySQL Query Failed", mysql_error(), __FUNCTION__); # Call Error
								
			$backtrace = debug_backtrace(); // Retrieving information about the caller statement.
			if (isset($backtrace[0])) {
				$caller = & $backtrace[0];
			} else {
				$caller = array();
			}
			if (isset($backtrace[1])) {
				$owner = & $backtrace[1];
			} else {
				$owner = array();
			}
			if (empty($file)) {
				$file = $caller['file'];
			}
			if (empty($line) && $line !== false) {
				$line = $caller['line'];
			}
			$type		= $owner['type'];
			$function 	= $owner['function'];
			$class 		= $owner['class'];
			
			//$server_type = api_get_setting('server_type');
			if (!empty($line)) {			
				//echo $info;
				$error['Database error number'] 	= self::errno($this->con);
				$error['Database error message']	= self::error_mysql($this->con).'<br />';
				
				$error['Query'] = $sql;
				$error['File'] 	= $file;
				$error['Line'] 	= $line;
				if (empty($type)) {
					if (!empty($function)) {
						$error['Function'] = '<br />' . $function;
					}
				} else {
					if (!empty($class) && !empty($function)) {
						$error['Class']  = $class;
						$error['Method'] = $function;
					}
				}
				global $main;
				if (!empty($main)) {
					$main->error($error);	
				} else {
					echo 'Main class is not loaded';
				}		
				
			}	
		}
		return $result; # Return mySQL result
	}
	
	/**
	 * Gets the number of rows from the last query result - help achieving database independence
	 * @param resource		The result
	 * @return integer		The number of rows contained in this result
	 **/
	public function num_rows($result) {		
		return is_resource($result) ? mysql_num_rows($result) : false;
	}
	
	public function fetch_array($result, $option = 'BOTH') { # Gets a query and returns the rows/columns as array
		//$sql = @mysql_fetch_array($result); # Fetch the SQL Array, all the data
		return $option == 'ASSOC' ? mysql_fetch_array($result, MYSQL_ASSOC) : ($option == 'NUM' ? mysql_fetch_array($result, MYSQL_NUM) : mysql_fetch_array($result));		
	}
	
	public function fetch_row($result) {
		return mysql_fetch_row($result);
	}
	
	
	
	public function strip($value) { # Gets a string and returns a value without SQL Injection
		if(is_array($value)) {
			$array = array();
			foreach($value as $k => $v) {
				if(is_array($v)) {
					$array[$k] = $this->strip($v);
				} else {
					if (get_magic_quotes_gpc()) { # Check if Magic Quotes are on
						  $v = stripslashes($v); 
					}
					if(function_exists("mysql_real_escape_string")) { # Does mysql real escape string exist?
						  $v = mysql_real_escape_string($v);
					} 
					else { # If all else fails..
						  $v = addslashes($v);
					}
					$array[$k] = $v;
				}
			}
			return $array;
		} else {
			if (get_magic_quotes_gpc()) { # Check if Magic Quotes are on
				  $value = stripslashes($value); 
			}
			if(function_exists("mysql_real_escape_string")) { # Does mysql real escape string exist?
				  $value = mysql_real_escape_string($value);
			} 
			else { # If all else fails..
				  $value = addslashes($value);
			}
			return $value;
		}

	}
	
	/**
	 * Gest the current system configuration
	 */
	function getSystemConfigList($force = false) {	
		if (empty($force)) {
			$force = false;
		}	
		if (!isset($_SESSION['config']) || $force == true) {
			$query = $this->query("SELECT * FROM <PRE>config");				
			if ($this->num_rows($query) > 0) {
				$list = $this->store_result($query);
				$new_list =  array();
				foreach($list as $item ) {
					$new_list[$item['name']] = $item['value'];
				}				
				$_SESSION['config'] = $new_list;
			}				
		} else {			
			return $_SESSION['config'];
		}
	}
	
	
	public function config($name) { # Returns a value of a config variable		
		$config_list = $this->getSystemConfigList();			
		if (isset($config_list[$name]) && !empty($config_list[$name])) {
			return $config_list[$name];			
		}
		return false;
	}
	
	public function resources($name) { # Returns a value of a resource variable
		$name = $this->strip($name);
		$query = $this->query("SELECT * FROM `<PRE>resources` WHERE `resource_name` = '{$name}'");
		if($this->num_rows($query) == 0) {
			$error['Error'] = "Couldn't Retrieve resource value!";
			$error['Resource Name'] = $name;
			global $main;
			$main->error($error);
		}
		else {
			$value = $this->fetch_array($query);
			return $value['resource_value'];
		}
	}
	
	public function staff($id) { # Returns values of a id
		$id = intval($id);
		$query = $this->query("SELECT * FROM `<PRE>staff` WHERE `id` = '{$id}'");
		if($this->num_rows($query) == 0) {
			$error['Error'] = "Couldn't retrieve staff data!";
			$error['Username'] = $id;
			global $main;
			$main->error($error);
		}
		else {
			$value = $this->fetch_array($query);
			return $value;
		}
	}
	
	public function client($id) { # Returns values of a id
		$id = intval($id);
		$query = $this->query("SELECT * FROM `<PRE>users` WHERE `id` = '{$id}'");
		if($this->num_rows($query) == 0) {
			$error['Error'] = "Couldn't retrieve client data!";
			$error['UserId'] = $id;
			global $main;
			$main->error($error);
		}
		else {
			$value = $this->fetch_array($query);
			$query = $this->query("SELECT * FROM `<PRE>orders` WHERE `userid` = '{$value['id']}'");
			$data = $this->fetch_array($query);
			$value['domain'] = $data['domain'];
			$value['status'] = $data['status'];
			return $value;
		}
	}
	
	public function updateConfig($name, $value) { # Updates a config value
		$name = $this->strip($name);
		$sql = "UPDATE `<PRE>config` SET `value` = '{$value}' WHERE `name` = '{$name}'";		
		$query = $this->query($sql);
	}
	
	public function updateResource($name, $value) { # Updates a config value
		$name = $this->strip($name);
		$value= $this->strip($value);
		$query = $this->query("UPDATE `<PRE>resources` SET `resource_value` = '{$value}' WHERE `resource_name` = '{$name}'");
	}
	
	public function emailTemplate($name = 0, $id = 0) { # Retrieves a email template with name or id
		global $main, $db;
		if($name) {
			$query = $db->query("SELECT * FROM `<PRE>templates` WHERE `name` = '{$this->strip($name)}'");	
		}
		elseif($id) {
			$query = $db->query("SELECT * FROM `<PRE>templates` WHERE `id` = '{$this->strip($id)}'");		
		}
		else {
			$array['Error'] = "No name/id was sent onto the reciever!";
			$main->error($array);
			return;
		}
		if($db->num_rows($query) == 0) {
			$array['Error'] = "That template doesn't exist!";
			$array['Template Name/ID'] = $name . $id;
			$main->error($array);
		}
		else {
			return $db->fetch_array($query);	
		}
	}
	
	/**
	 * Gets the ID of the last item inserted into the database
	 * @param resource $connection (optional)	The database server connection, for detailed description see the method query().
	 * @return int								The last ID as returned by the DB function
	 */
	public static function insert_id($connection = null) {
		return self::use_default_connection($connection) ? mysql_insert_id() : mysql_insert_id($connection);
	}	
	
	public static function store_result($result, $option = 'ASSOC') {
		$array = array();
		if ($result !== false) { // For isolation from database engine's behaviour.
			while ($row = self::fetch_array($result, $option)) {				
				$array[] = $row;
			}
		}
		return $array;
	}	
	
	public function addLimitAndOffset(&$sql,$options) {
        if (isset($options['limit']) && $limit = $options['limit']){
            $sql .= " LIMIT $limit";
            if (isset($options['offset']) && $offset = $options['offset']){
                $sql .= " OFFSET $offset";
            }
        }
        return $sql;
    }
    
    
    /* DATABASE STATEMENTS - CRUD */

    public function execute($sql, $message = 'SQL') {
        if (is_array($sql)) {
            $sql_string = array_shift($sql);
            $bindings = $sql;
        } else $sql_string = $sql;

        //$this->_log($message.': '.$sql_string);
        //$result = isset($bindings) ? $this->connection->Execute($sql_string, $bindings) : $this->connection->Execute($sql_string);
        $result = $this->query($sql_string);

        if (!$result){
            $error_message = '['.$this->connection->ErrorNo().'] '.$this->connection->ErrorMsg();
           // $this->_log('SQL Error: '.$error_message);
            if ($this->debug || AK_DEBUG) trigger_error("Tried '$sql_string'. Got: $error_message", E_USER_NOTICE);
        }
        return $result;
    }

    public function incrementsPrimaryKeyAutomatically()
    {
        return true;
    }

    public function getLastInsertedId($table,$pk)
    {
        return $this->connection->Insert_ID($table,$pk);
    }

    public function getAffectedRows()
    {
        return $this->connection->Affected_Rows();
    }

    public function insert($sql,$id=null,$pk=null,$table=null,$message = '')
    {
        $result = $this->execute($sql,$message);
        if (!$result){
            return false;
        }
        return is_null($id) ? $this->getLastInsertedId($table,$pk) : $id;
    }

    public function update($sql,$message = '')
    {
        $result = $this->execute($sql,$message);
        return ($result) ? $this->getAffectedRows() : false;
    }

    public function delete($sql,$message = '')
    {
        $result = $this->execute($sql,$message);
        return ($result) ? $this->getAffectedRows() : false;
    }

    /**
    * Returns a single value, the first column from the first row, from a record
    */
    public function selectValue($sql)
    {
        $result = $this->selectOne($sql);
        return !is_null($result) ? array_shift($result) : null;
    }

    /**
     * Returns an array of the values of the first column in a select:
     *   sqlSelectValues("SELECT id FROM companies LIMIT 3") => array(1,2,3)
     */
    public function selectValues($sql)
    {
        $values = array();
        if($results = $this->select($sql)){
            foreach ($results as $result){
                $values[] = array_shift($result);
            }
        }
        return $values;
    }

    /**
     * Returns a record array of the first row with the column names as keys and column values
     * as values.
     */
    public function selectOne($sql)
    {
        $result = $this->select($sql);
        return  !is_null($result) ? array_shift($result) : null;
    }

    /**
     * alias for select
     */
    public function selectAll($sql)
    {
        return $this->select($sql);
    }

    /**
    * Returns an array of record hashes with the column names as keys and
    * column values as values.
    */
    public function select($sql, $message = '')
    {
        $result = $this->execute($sql, $message);
        if (!$result){
            return array();
        }

        $records = array();
        while ($record = $this->fetch_row($result)) {
            $records[] = $record;
        }
        //$result->Close();
        return $records;
    }
    
    
    
}