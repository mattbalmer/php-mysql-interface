<?php

class DB
{
    // ===================
    // === Variables
    // ===================
    
    // Connection Info
    public static $host;
    public static $database;
    public static $user;
    public static $password;
    public static $prefix;

    // Connection Object
	public static $connection = false;

    // Last known error
    public static $error = '';

    // Options
	public static $options = array(
        'log' => false,
        'log_linebreak' => ''
    );

    // ===================
    // === Primary DB Funcs
    // ===================
	public static function connect() {
        // Determine number of arguments passed
        $numargs = func_num_args();

        // Connect to the DB, method depending on the arguments passed
        if($numargs == 0) {
            self::$connection = self::p_connect();
        }
        if($numargs == 1) {
            $a = func_get_arg(0);
            if( array_key_exists('host', $a) ) {
                self::$host = $a['host'];
                self::$database = $a['database'];
                self::$user = $a['user'];
                self::$password = $a['password'];
                self::$prefix = $a['prefix'];
                self::$connection = self::p_connect();
            }
        }
        if($numargs > 1) {
            self::$host = func_get_arg(0);
            self::$database = func_get_arg(1);
            self::$user = func_get_arg(2);
            self::$password = func_get_arg(3);
            self::$prefix = func_get_arg(4);
            self::$connection = self::p_connect();
        }

        // If connection failed, return now
		if (!self::$connection) {
			return self::setError('Error connecting to the DB server.');
		}
		// If connection succeeded, select a database
		if( !mysql_select_db( self::$database, self::$connection ) ) {
		    return self::setError('Error selecting a DB on the server.');
        }
        return self::$connection;
    }
	
	public static function close() {
        if(!self::isConnected()) return false;
		mysql_close(self::$connection);
	}
	
	public static function insert($table, $data) {
        if(!self::isConnected()) return false;
		$table = self::$prefix . $table;

        $d = self::formatData($data);
        $query = "INSERT INTO ".$table." {$d['keystring']} VALUES {$d['valstring']}";

        $result = mysql_query( $query, self::$connection );
        self::log( $query, mysql_error() );

		if(!$result) return self::setError('Error during insert operation. '.mysql_error());
		return true;
	}
	
	public static function update($table, $data, $where) {
        if(!self::isConnected()) return false;
		$table = self::$prefix . $table;

        $d = self::formatData($data);
        $where = self::getWhereString($where);
		$query = "UPDATE ".$table." SET ".$d['setstring'] . $where;

        $result = mysql_query( $query, self::$connection );
		self::log( $query, mysql_error() );

		if(!$result) return self::setError('Error during update operation. '.mysql_error());
		return true;
	}
	
	public static function get($table, $select, $where = NULL, $order = NULL, $start = 0, $range = 0, $desc = false, $first = -1) {
        if(!self::isConnected()) return false;
		$table = self::$prefix . $table;
		
		$where = self::getWhereString($where, $first, $desc);

		$query = "SELECT ".$select." FROM ".$table. $where . ( $order == NULL ? "" : " ORDER BY ".$order.($desc ? " DESC" : " ASC") );

        // If range & limit is given, insert them into the querystring
		if($range > 0) {
            if($first > -1)
			    $query .= " LIMIT 0, $range";
            else
                $query .= " LIMIT $start, $range";
        }

		$sqlReturn = mysql_query( $query, self::$connection );
        self::log( $query, mysql_error() );
        if(!$sqlReturn) return self::setError('Error during get operation. '.mysql_error());

        // If operation is successful, get the information from the mysql operation
		$result = array();
		while($row = mysql_fetch_array($sqlReturn)) {
			$thisrow = array();
			foreach($row as $key => $val) {
				$thisrow[$key] = $val;
			}
			$result[] = $thisrow;
		}
		return $result;
	}
	
	public static function delete($table,  $where) {
        if(!self::isConnected()) return false;
		$table = self::$prefix . $table;
		
		$where = self::getWhereString($where);
		
		$query = "DELETE FROM ".$table.$where;

        $result = mysql_query( $query, self::$connection );
        self::log( $query, mysql_error() );

        if(!$result) return self::setError('Error during delete operation. '.mysql_error());
        return true;
	}

    /**
     * Run raw SQL commands
     * @param $query Raw SQL to be run on database
     * @return bool true if successful, false if not. If false, call DB::err() to get the error String
     */
    public static function rawSql($query) {
        $sqlReturn = mysql_query( $query, self::$connection );
        self::log( $query, mysql_error() );
        if(!$sqlReturn) return self::setError('Error running raw SQL commands. '.mysql_error());
		return true;
	}

    /**
     * Return the last logged error
     */
    public static function err() {
        return self::$error;
    }

    /**
     * Check to see if a connection is open.
     * @return bool true if open, false if not. If false, call DB::err() to get the error String
     */
    public static function isConnected() {
        if(self::$connection == false) {
            return self::setError('No connection is open.');
        }
        return true;
    }

    // ===================
    // === Option Funcs
    // ===================
    public static function setOption($key, $value) {
        self::$options[$key] = $value;
    }

    // ===================
    // === Private Funcs
    // ===================
    private static function p_connect() {
        if( !isset(self::$host) || !isset(self::$user) || !isset(self::$password) ) return self::setError('Cannot connect because connection information has not been supplied.');
        $connection = mysql_connect( self::$host, self::$user, self::$password );
        return $connection;
    }

    private static function setError($error) {
        self::$error = $error;
        return false;
    }

    private static function formatData($data) {
        $r = array(
            'keys' => array(),
            'vals' => array(),
            'keystring' => '',
            'valstring' => '',
            'setstring' => ''
        );

        foreach($data as $key => $val) {
            $r['keys'][] = $key;
            $r['vals'][] = ("'".$val."'");
        }

        $r['keystring'] = '('.implode(', ',$r['keys']).')';
        $r['valstring'] = '('.implode(', ',$r['vals']).')';

        for($i = 0; $i < count($r['keys']); $i++) {
            $r['setstring'] .= ($i == 0 ? '' : ', ') . $r['keys'][$i] . "=" . $r['vals'][$i];
        }

        return $r;
    }

	private static function log() {
        for($i = 0; $i < func_num_args(); $i++) {
            $line = func_get_arg($i);

            if(self::$options['log']) echo $line.self::$options['log_linebreak'];
        }
	}
	
	private static function getWhereString($array, $first = 0, $desc = false) {
		if($array == NULL)
			return '';
		if(is_string($array)) 
			return " WHERE ".$array;

		foreach($array as $key => $val) {
			$keys[] = $key;
			$vals[] = ("'".$val."'");
		}
		$string = " WHERE ";
		for($i = 0; $i < count($keys); $i++) {
            $operator = $keys[$i] == 'id' && $first > 0 ? ( $desc == true ? '<=' : '>=' ) :  '=';
			$string = $string . ($i == 0 ? "" : " AND ") . $keys[$i] . $operator . $vals[$i];
		}

		return $string;
	}
}
?>