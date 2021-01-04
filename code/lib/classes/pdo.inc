<?php
/**
 *------------------------------------------------------------------------------
 * PDO class 2016/01/05
 *           2016/06/18  Rel 1.7 by Hide	modify TYPO & etc .....
 *------------------------------------------------------------------------------
 * HOW TO USE
 * ----------
 *   $con = new DBIO();
 *   $con->connect();
 *   $sql = "select * from table where id='?'";
 *   $prm = array('bar');
 *   $con->sqbind($sql,$prm);
 *   $result = $con->fetch();
 * 	 $con->close();
 * 
 *------------------------------------------------------------------------------
 * DBIO->DBIO($target = 'default')				//	constructor
 * DBIO->connnect()								//	connect db
 * DBIO->close()								//	close db
 * DBIO->sqlbind($sql, $params)					//	bind place holder
 * DBIO->query($sql , $params)					//	exec query (alias : sqlbind)
 * DBIO->fetch()								//	compat : fetch
 * DBIO->fetch_assoc()							//	compat : fetch_assoc
 * DBIO->numrows()								//	compat : numrows (mysql only)
 * DBIO->affected_rows()						//	compat : affected rows
 * DBIO->getAll($sql, $params = null)			//	exec + get all records
 * DBIO->getAllAssoc($sql, $params = null)		//	exec + get all records (assoc)
 * DBIO->getRow($sql, $params = null)			//	exec + get one record
 * DBIO->getRowAssoc($sql, $params = null)		//	exec + get one record  (assoc)
 * DBIO->getOne($sql, $params,$key)				//	exec + one record
 * DBIO->getColumn($sql, $params)				//	exec + get column name
 *----------------------------------------------------------------------------*/
class DBIO{
	//	db connect config ----------------------------------------------------------------
	//	TODO : external configuration
	private $config = array(
		'default' => array('dsn'  => 'mysql:host=127.0.0.1;dbname=remote;charset=utf8' , 
						   'user' => 'root'  , 
						   'pass' => ''         ) );
	//	private  -------------------------------------------------------------------------
    private $dsn ;				//	(D)ata(S)ource(N)ame
    private $db_user ;			//	user
    private $db_pass ;			//	password
    private $link = false;		//	link status
    private $dbh;				//	db handler
    private $sth;				//	db statement

	/**	constructor
	 * 
	 * @param  string   $target
	 * @return boolean  condition
	 */	
    function __construct($target = 'default') {
		$this->dsn     = $this->config[$target]["dsn"] ;
		$this->db_user = $this->config[$target]["user"] ;
		$this->db_pass = $this->config[$target]["pass"] ;
		//logsave("DBIO" , "INIT TARGET (".$target.")");
        return $this->connect();
    }
    
    /**	connect
	 * 
	 * @return boolean
	 */
	function connect(){
		//	already connected
        if($this->link){
        	return false;
		}
			
        try {
            $this->dbh  = new PDO($this->dsn , $this->db_user, $this->db_pass,
            array(
                PDO::ATTR_PERSISTENT => true
                )
            );
        	$this->sth  = null;
            $this->link = true;
        } catch (PDOException $e) {
            $this->link = false;
			logsave("DBIO" , "Connection failed (".$this->dsn.")");
			return false ;
        }
        return true ;
    }
    
	/**	close
	 * 
	 * @return boolean
	 */
	function close(){
		//	db not connect
		if(!$this->link){
		    return false ;
		}
		
        $this->dbh = null;
        $this->sth = null;
		$this->link = false ;
		return true;
	}

	/**	bind
	 * 
	 * @param type $sql
	 * @param type $params
	 * @return boolean
	 */
    function sqlbind($sql, $params = NULL){
		$st_time = microtime(true);								//	timer


		//	db not connect
		if(!$this->link){
		    return false ;
		}

        $this->sth = $this->dbh->prepare($sql);
		if($params != NULL){
	        for ($i=0; $i<count($params); ++$i){
    	    	$this->sth->bindValue(($i+1), $params[$i]);
			}
		}
				
        $result = $this->sth->execute();

		if($result === false){
			$e = $this->sth->errorInfo ();
			print_r($e);
			echo("PDO_ERROR\n\n");
		}

		$lap = substr((microtime(true) - $st_time),0,8);
		logsave("PDO" , "Query : $sql ");
		logsave("PDO" , "Query Time : $lap msec ");

        return $result;
    }

	/**	escape
	 * 
	 * @param type $str
	 * @return boolean
	 */
    function escape($str){
		//	db not connect
		if(!$this->link){
		    return false ;
		}
        return $this->dbh->quote($str);
    }

    
    //	query ( alias : sqlbind )
    function query($sql , $params = NULL){
		return $this->sqlbind($sql,$params);
    }
    
    /**	fetch
	 * 
	 * @return array  取得データ
	 */
    function fetch(){
        //	link check
		if(!$this->link){
			return false;
		}
        $row = $this->sth->fetch();
        
		return $row;
    }

    /**	fetch assoc
	 * 
	 * @return array	取得データ 
	 */
    function fetch_assoc(){
        //	link check
		if(!$this->link){
			return false;
		}
		$row = $this->sth->fetch(PDO::FETCH_ASSOC);

		return $row;
    }

	/**	numrows
	 * 
	 * @return boolean
	 */
	function numrows(){
        //	link check
		if(!$this->link){
			return false;
		}

		//	obsolete function
		return $this->sth->rowCount();
	
	}
	
	/**	affected rows
	 * 
	 * @return boolean
	 */
	function affected_rows(){
        //	link check
		if(!$this->link){
			return false;
		}

		return $this->sth->rowCount();
	}
    
	/**	get All result by array
	 * 
	 * @param type $sql
	 * @param type $params
	 * @return boolean
	 */
	function getAll($sql, $params = null) {
        //	link check
		if(!$this->link){
			return false;
		}
		$this->sqlbind($sql, $params);
		$row = $this->sth->fetchAll();
		$this->sth->closeCursor();
		
		return $row;
	}

	/**	get All result by array ( assoc )
	 * 
	 * @param type $sql
	 * @param type $params
	 * @return boolean
	 */
	function getAllAssoc($sql, $params = null) {
        //	link check
		if(!$this->link){
			return false;
		}
		$this->sqlbind($sql, $params);
		$row = $this->sth->fetchAll(PDO::FETCH_ASSOC );
		$this->sth->closeCursor();
		
		return $row;
	}
    
	/**	get One result by array
	 * 
	 * @param type $sql
	 * @param type $params
	 * @return boolean
	 */
	function getRow($sql, $params = null) {
        //	link check
		if(!$this->link){
			return false;
		}
        
		$this->sqlbind($sql, $params);
        $row = $this->sth->fetch();
        $this->sth->closeCursor();
        
		return $row;
	}
    
	/**	get One Result by array (assoc)
	 * 
	 * @param type $sql
	 * @param type $params
	 * @return boolean
	 */
	function getRowAssoc($sql, $params = null) {
        //	link check
		if(!$this->link){
			return false;
		}
        
		$this->sqlbind($sql, $params);
        $row = $this->sth->fetch(PDO::FETCH_ASSOC);
        $this->sth->closeCursor();
        
		return $row;
	}
    
	/**	get One result by Value
	 * 
	 * @param type $sql
	 * @param type $params
	 * @param type $key
	 * @return boolean
	 */
	function getOne($sql, $params = null, $key = 0) {
        //	link check
		if(!$this->link){
			return false;
		}
        
		$this->sqlbind($sql, $params);
        $row = $this->sth->fetch();
        $this->sth->closeCursor();
        
		return $row[$key];
	}
    
	/**	get Column Name by array
	 * 
	 * @param type $sql
	 * @param type $params
	 * @return type
	 */
	function getColumn($sql, $params) {
        $this->sqlbind($sql, $params);
        
        $columns = array();
        for ($i = 0; $i < $this->sth->columnCount(); $i++) {
            $meta = $this->sth->getColumnMeta($i);
            $columns[] = $meta['name'];
        }
        $this->sth->closeCursor();
        return $columns;
	}
	/** get fields
	 * 
	 * @param type $table_name
	 * @return type
	 */
	function getFields($table_name){
		$result = $this->getAllAssoc("DESC $table_name");
		$arr = array();
		if(!empty($result)){
			foreach($result as $val){
				$arr[$val["Field"]] = $val;
			}
		}
		return $arr;
	}
}
/* -- end of text ------------------------------------------------------------*/