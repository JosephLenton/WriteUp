<?
	/**
	 * DB
	 *
	 * A database interface to make it simpler to perform queries. It's methods for describing
	 * SQL queries return this instance to allow the methods to be chained.
	 *
	 * First all methods and properties return the same database instance. This is to allow you
	 * to chain query methods.
	 *
	 * For example the SQL statement:
	 *     'SELECT name, artist FROM albums WHERE release_date > 2009 LIMIT 10'
	 * can be auto-generated as:
	 *     $db->albums->select('name', 'artist')->where('release_date > 2009')->limit(10)->query();
	 *
	 * You can access tables directly as properties of the DB. These allow you to do several things.
	 *
	 * ## Setting ##
	 * First you can insert values directly by just setting an array of properties to the table.
	 *
	 * For example:
	 *     $db->albums = array( 'name' => 'OK Computer', 'artist' => 'Radiohead' );
	 * In this example the query is run directly without the user having to call query().
	 *
	 * You can also set multiple sets of values in one to the table. You can do this if the
	 * array you are setting is 2D.
	 * For example:
	 *     $db->albums = array(
	 *             array( 'name' => 'OK Computer', 'artist' => 'Radiohead' ),
	 *             array( 'name' => 'You Forget it in People', 'artist' => 'Broken Social Scene' )
	 *     );
	 * In this example two rows of data are entered into the albume table.
	 *
	 * ## Getting ##
	 * You can also retrieve properties to state which table you are using. The first property
	 * will state which table you are accessing and any more state what values you are selecting.
	 *
	 * For example:
	 *     $db->albums->name->artist;
	 * This states that the user is selecting name and artist from the albums table.
	 * The query is not performed automatically when getting properties (only when setting).
	 * Note also that the above is the same as:
	 *     $db->albums;
	 *     $db->name;
	 *     $db->artist;
	 *
	 * ## Examples ##
	 *
	 * All of the following inserts do the same thing:
	 *
	 *     $db->artists->insert( array('name' => 'Rancid'), array('name' => 'Caribou') )->query();
	 *     $db->insert( array('name' => 'Rancid'), array('name' => 'Caribou') )->artists->query();
	 *     $db->insert( 'artists', array('name' => 'Rancid'), array('name' => 'Caribou') )->query();
	 *     $db->insert( 'artists', array(array('name' => 'Rancid'), array('name' => 'Caribou')) )->query();
	 *     $db->artists->insert( array(array('name' => 'Rancid'), array('name' => 'Caribou')) )->query();
	 *     $db->insert( array(array('name' => 'Rancid'), array('name' => 'Caribou')) )->artists->query();
	 *     $db->artists = array( array('name' => 'Rancid'), array('name' => 'Caribou') );
	 *
	 */
	class DB extends \flexi\Obj
	{
        const ORDER_DEFAULT = 'DESC';

        private static $lastQuery = null;

        /**
         * This is intended for debugging purposes, and not for real world use.
         * 
         * That is why it is stored globally, and shared across all Database
         * objects, so the last query can be retrieved without having to worry
         * about which Database object to check.
         * 
         * @return the last SQL query performed, by any database connection, or null, if no query has occurred.
         */
        public static function getLastQuery() {
            return DB::$lastQuery;
        }

		/**
		 * Tests if the given variable is set, and if it's not then an exception is thrown.
		 *
		 * <p>The given var is taken and tested if it is set. If it is then it is returned.
		 * If it is not set then an exception is thrown that uses the errorTxt given as
		 * it's description.</p>
		 *
		 * @param var The variable to test.
		 * @param errorTxt The text for the exception being thrown if the variable is not set.
		 * @return Returns the var parameter, but this only occurs if it's set.
		 */
		private static function issetOrError( $arr, $key, $errorTxt )
		{
			if ( isset($arr[$key]) ) {
				return $arr[$key];
			} else {
				throw new Exception( $errorTxt );
			}
		}

		/**
		 * Tests if the given array is at least two-dimensional.
		 *
		 * <p>If the given array contians another array as it's first element
		 * then it is considered to be two-dimensional and true will be returned.
		 * Otherwise false is returned.</p>
		 *
		 * @param The array to check if it is multidimensional.
    	 * @return True if the array is at least 2D, otherwise false in all cases.
		 */
		private static function isArray2D( &$array )
		{
			return (
                    isset($array) && is_array($array) &&
                    isset($array[0]) && is_array($array[0])
            );
		}

        private static function hasWhere( &$ar )
        {
            return  isset($ar['where'])                 ||
					isset($ar['where_or'])              ||

					isset($ar['equal'])                 ||
                    isset($ar['not_equal'])             ||

					isset($ar['less_than'])             ||
					isset($ar['less_than_equal'])       ||
					isset($ar['greater_than'])          ||
					isset($ar['greater_than_equal'])    ||

					isset($ar['match']);
        }

		private static function issetConcat( $sql, $prefix, $postfix, $arr, $key )
		{
			if ( isset($arr[$key]) ) {
                $val = $arr[$key];

                if ( $val !== null ) {
                    return $sql . $prefix . $val . $postfix;
                } else {
                    return $sql;
                }
			} else {
				return $sql;
			}
		}

        private static function checkTables( $stored, $newTables ) {
            if ( $stored === false ) {
                throw new Exception( "no database tables found" );
            } else {
                for ( $i = 0; $i < count($newTables); $i++ ) {
                    if ( ! in_array($newTables[$i], $stored) ) {
                        throw new Exception( "Unknown table given, " . $newTables[$i] );
                    }
                }
            }
        }

		/**
		 *
		 */
		private static function dbSafe( $val )
		{
            if ( is_bool($val) ) {
                return ( $val ? 1 : 0 );
            } else if ( is_array($val) ) {
                $safeArr = array();

                foreach ( $val as $i => $v ) {
                    $safeArr[$i] = DB::dbSafe( $v );
                }

                return $safeArr;
            } else {
                return mysql_real_escape_string( $val );
            }
		}

        private static function generateEqualitySQLClauses( $arr, $equalOp ) {
            $equalOp = ' ' . $equalOp . ' ';
            $sql = '';
            $firstIteration = true;

            foreach ( $arr as $field => $var ) {
                if ( $firstIteration ) {
                    $firstIteration = false;
                } else {
                    $sql .= ' AND ';
                }

                // this is for: equal( 'id', [1, 2, 3] ),
                // it becomes: '((id == 1) || (id == 2) || (id == 3))
                if ( is_array($var) && count($var) > 0 ) {
                    $sql .=
                            '( ' . $field . ' = "' .
                                implode( '" || ' . $field . ' = "', DB::dbSafe($var) ) .
                            '" )'
                    ;
                } else {
                    $sql .= $field . ' = "' . DB::dbSafe($var) . '" ';
                }
            }

            return $sql;
        }

        /**
         * Executes the callbacks given, map and filter, on the object.
         * They are executed if they exist, and the new value is then returned.
         */
        private static function executeQueryCallbacks( &$obj, &$map, &$filter ) {
            if ( $map !== null ) {
                $r = call_user_func( $map, $obj );

                if ( isset($r) ) {
                    $obj = $r;
                }
            }

            if ( $filter !== null ) {
                $r = call_user_func( $filter, $obj );

                if ( ! isset($r) || !$r ) {
                    return;
                }
            }
            return $obj;
        }

        private $username;
        private $password;
        private $database;
        private $host;

        private $activerecord;

        private $events;

        // boolean flags, that do various things during the query execution
        private $skipEvents;
        private $justField;
        private $grabOne;

        // gets set to true, if execute query fails
        private $hasError;

        // event handlers
        private $beforeQuery;
        private $afterQuery;

        private $validateTables;

		/**
		 * Takes an array containing settings stored under 'username', 'password', 'database' and 'host' for
		 * the matching database settings for setting up a connection.
         * 
         * @param configs An array of database configurations.
		 */
		public function __construct( $configs )
		{
            $this->validateTables = null;

            if ( ! is_array($configs) ) {
                $message = '';

                // check common types of failure items, then generic message
                if ( $configs === null ) {
                    $message = 'null Database configuration given';
                } else if ( $configs === false ) {
                    $message = 'false Database configuration given';
                } else if ( $configs === 0 ) {
                    $message = 'you gave "0" for the Database configuration, why???';
                } else {
                    $message = 'invalid Database configuration given, it must be an array';
                }
                
                throw new Exception( $message );
            }

			$this->username = DB::issetOrError( $configs, 'username', 'Username missing from database config map' );
			$this->password = DB::issetOrError( $configs, 'password', 'Password missing from database config map' );
			$this->database = DB::issetOrError( $configs, 'database', 'Database to use is missing from database config map' );
			$this->host     = DB::issetOrError( $configs, 'hostname', 'Hostname missing from database config map' );

			$this->activerecord = array();

            $this->events = null;

            $this->skipEvents = false;
            $this->justField  = false;
            $this->grabOne    = false;

            $this->beforeQuery = null;
            $this->afterQuery  = null;
            $this->hasError    = false;
		}

		public function __set( $field, $value )
		{
            /*
             * Inserting zero rows of data is valid.
             *
             * However to optimize this, we just skip
             * the whole query and jump straight to
             * the clear.
             */

            if ( count($value) > 0 ) {
                $this->insert( $field, $value );
                $this->query();
            } else {
				$this->clear();
            }
		}

		public function __get( $field )
		{
			if ( ! isset($this->activerecord['table']) ) {
				return $this->from( $field );
			} else {
				return $this->select( $field );
			}
		}

        /**
         * Adds an event to be called when the stated tables are touched. This
         * is run on every row of the results of the select.
         *
         * The event can add or change the properties of the returned value,
         * this is mainly what it's intended for. If the event returns null
         * then the object is not added to the results, allowing filtering,
         * however it's highly advised to do this via the database where
         * possible.
         *
         * If the event fails to return a value then an exception will be
         * thrown, as it is presumed that the developer has forgotten to bear
         * in mind that this alters the result.
         *
         * If no table is stated then the event is applied to all results of
         * all select queries. If multiple tables are stated then the results
         * must be from a select that touched all of those tables.
         *
         * If the select touches more tables then the event needs, but does
         * touch all of the events tables, then the event is still run.
         *
         * The event will need to take at least 1 parameter for the row being
         * passed in.
         * 
         * # Examples
         * 
         *      // run when the users table is queried
         *      $db->onSelect( 'users', function($user) {
         *          $user->user_url = '/users/view/' . $user->username;
         *      } );
         *
         *      // run when *both* users and posts tables are queried
         *      $db->onSelect( 'users', 'posts', function($posts) {
         *          $posts->post_url = '/posts/' . $posts->username . '/' . $posts->title;
         *      } );
         * 
         *      // this is the same as the two above ...
         *      $db->onSelect( array(
         *              'users' => function($user) {
         *                  $user->user_url = '/users/view/' . $user->username;
         *              },
         * 
         *              'users, posts' => function($posts) {
         *                  $posts->post_url = '/posts/' . $posts->username . '/' . $posts->title;
         *              }
         *      ) );
         * 
         * # Return false
         * 
         * You can also filter out results, by returning false. In that case,
         * a null value is returned in the case of 'get()', or is skipped
         * entirely in the case of 'gets()'.
         */
        public function onSelect( $first )
        {
            $numArgs = func_num_args();

            if ( $numArgs === 0 ) {
                throw new Exception( "not enough parameters" );
            }

            if ( is_array($first) ) {
                if ( $numArgs === 1 ) {
                    foreach ( $first as $props => $fun ) {
                        $this->onSelect( $props, $fun );
                    }
                } else if ( $numArgs === 2 ) {
                    $fun = func_get_arg( 1 );

                    if ( ! is_callable($fun) ) {
                        throw new Exception( "Given event is not callable (it's not a function), received: " . $fun );
                    }
                } else {
                    throw new Exception( "too many parameters" );
                }
            } else if ( $numArgs === 1 ) {
                throw new Exception( "not enough parameters" );
            } else {
                $tables = array();
                for ( $i = 0; $i < $numArgs-1; $i++ ) {
                    $table = strtolower( func_get_arg($i) );

                    if ( strpos($table, ',') !== false ) {
                        $tableParts = explode( ',', $table );

                        for ( $j = 0; $j < count($tableParts); $j++ ) {
                            $tables[]= trim( $tableParts[$j] );
                        }
                    } else {
                        $tables[] = $table;
                    }
                }

                $event = func_get_arg( $numArgs-1 );
                if ( ! is_callable( $event ) ) {
                    throw new Exception( "Given event is not callable (it's not a function), received: " . $event );
                }

                if ( $this->validateTables !== null ) {
                    DB::checkTables( $this->validateTables, $tables );
                }

                $tablesEvent = array(
                        'tables' => $tables,
                        'event'  => $event
                );

                if ( $this->events === null ) {
                    $this->events = array( $tablesEvent );
                } else {
                    $this->events[]= $tablesEvent;
                }
            }
        }

        /**
         * Sets an event which will be alled before a query.
         * 
         * The sql will be passed in as it's only parameter.
         */
        public function beforeQuery( $callback ) {
            if ( $callback !== null && ! is_callable($callback) ) {
                throw new Exception( "callback must be a callback, or null" );
            }

            $this->beforeQuery = $callback;

            return $this;
        }

        /**
         * Sets an event to be called after a query.
         * This is called regardless of if it is successful or not.
         * 
         * The sql, and if it succeeded or not, is passed in.
         * Success is 'true' for success, or 'false' if it failed.
         *
         * The mysql error is passed in as a third parameter,
         * or null on success.
         */
        public function afterQuery( $callback ) {
            if ( $callback !== null && ! is_callable($callback) ) {
                throw new Exception( "callback must be a callback, or null" );
            }

            $this->afterQuery = $callback;

            return $this;
        }

		/**
		 * Creates and returns a new database connection.
		 *
		 * <p>Based on the configuration values given when this database object was made,
		 * this creates and then returns a database connection object for you to use in
		 * order to communicate with the database.</p>
		 *
		 * @return A mySQL database connection reference.
		 */
		public function newConnection()
		{
			$conn = mysql_connect( $this->host, $this->username, $this->password );

            if ( $conn === false ) {
                throw new Exception("failed to connect to MySQL server");
            }

			if ( mysql_select_db($this->database) === false ) {
                throw new \Exception("Select failed with database '" . $this->database . "'" );
            }

			return $conn;
		}

        /**
         * Calling this disabled events for the next query.
         * Once the query is executed, events are re-enabled.
         */
        public function skipEvents()
        {
            $this->skipEvents = true;
            return $this;
        }

        /**
         * The best way to describe this method,
         * is to describe the use case. Lets say I want a user object,
         * and I have their id. One way of doing that is:
         *
         *     $user = $db->users->equal( 'id', $id )->
         *             limit(1)->query()->row();
         *
         * This allows you to do the above as:
         *     $user = $db->users->get( 'id', $id );
         *
         * It rolls the 'equal', 'limit(1)', 'query' and 'row' together.
         *
         * However this can also be used as a single parameter function,
         * where it will presume you are matching on the 'id' field.
         * So the above could be written as:
         *
         *     $user->$db->users->get( $id );
         *
         * You can also call this with no parameters, in which case it is
         * the same as calling 'queryOne'.
         *
         * @param id
         * @param idVal optional
         * @return Null if nothing is found, otherwise the first object found.
         */
        public function get()
        {
            $numArgs = func_num_args();

            if ( $numArgs === 1 ) {
                $this->equal( func_get_arg(0) );
            } else if ( $numArgs === 2 ) {
                $this->equal( func_get_arg(0), func_get_arg(1) );
            }

            return $this->queryOne();
        }

        /**
         * Same as 'query', only this adds extra parameters.
         */
        public function gets()
        {
            if ( func_num_args() === 2 ) {
                return $this->equal( func_get_arg(0), func_get_arg(1) )->query();
            } else {
                return $this->query();
            }
        }

        /**
         * ## WARNING! ##
         * This does not screen the column, it allows SQL injection!
         *
         * Returns just the field values of the column specified.
         *
         * This is similar too:
         *
         *  $rows = $this->db->my_table->select( $column )->gets()
         *  for ( $i = 0; $i < count($rows); $i++ ) {
         *      $rows[$i] = $rows[$i]->$column;
         *  }
         *
         * The above could be replaced with:
         *
         * $rows = $this->db->my_table->getFields( $column );
         *
         * However this is a lot more direct, because it avoids
         * returning an object for each result row in the first
         * place.
		 *
		 * That does _not_ mean that it's ok to call this lots
		 * of times, because each call still requires the cost
		 * of a round trip to the DB and back, and so using less
		 * DB calls is best!
		 *
		 * Only use this if you are replacing a single call to
		 * get out a whole object(s), with a single call to get
		 * out field(s).
		 *
		 * @param column The column to select, and return the value of.
		 * @return An array containing all of the values found, which is empty if none found.
         */
        public function getFields( $column )
        {
            $this->justField = true;

            return $this->select( $column )->query();
        }

        /**
         * ## WARNING! ##
         * This does not screen the column, it allows SQL injection!
         *
         * Just like getFields, only this returns the first value found.
		 *
		 * @param column The column to select.
		 * @return The field found, or null if not found.
         */
        public function getField( $column )
        {
            $this->grabOne = true;

            return $this->limit(1)->getFields( $column );
        }

        /**
         * This executes the query, and then runs each result through the callback given, in order.
         * 
         * If map callback is not executed if there
         * are no results.
         * 
         * If map returns a value, then it replaces the one given.
         * 
         * @param callback The function to apply to each element.
         * @return null If there is no result, otherwise the element found.
         */
        public function map( $callback )
        {
            if ( ! $callback ) {
                throw new Error("No callback function given");
            }

            return $this->query( null, $callback );
        }

        /**
         * The same as the other version of map,
         * only this will only retrieve one value,
         * and return it directly. Just like
         * calling '.get()'.
         * 
         * If map callback is not executed if there
         * are no results.
         * 
         * If map returns a value, then it replaces the one given.
         * 
         * @param callback The function to apply to the result found.
         * @return null If there is no result, otherwise the element found.
         */
        public function mapOne( $map )
        {
            if ( ! $map ) {
                throw new Error("No map callback function given");
            }

            return $this->queryOne( $map );
        }

        /*
         * Executes the query, and then filters the result.
         * This will go through all results, until one of the
         * results is allowed by the filter.
         * 
         * This means you can easily go through every result in the DB.
         */
        public function filterOne( $filter )
        {
            if ( ! $filter ) {
                throw new Error("Invalid filter callback given");
            }

            $this->grabOne = true;
            return $this->query( null, null, $filter );            
        }

        /*
         * Executes the query, and then filters the results through this function.
         * 
         * Return a false-y value, such as false, null, 0 or nothing at all to filter
         * out the element. Return a true-like value to keep it.
         * 
         * WARNING! where possible, it is ALWAYS faster to filter using SQL conditions,
         * instead of this function. This is because the DB will return less data.
         */
        public function filter( $filter )
        {
            if ( ! $filter ) {
                throw new Error("Invalid filter callback given");
            }

            return $this->query( null, null, $filter );
        }

        /**
         * It is very common that you are only after a single
         * result from a DB query, such as a user from a user id.
         *
         * This essentially shorthand for '$db->limit(1)->query()->row()'.
         *
         * @return Null if no value is found, otherwise the first result found.
         */
        public function queryOne( $map=null )
        {
            $this->grabOne = true;

            return $this->limit( 1 )->query( null, $map );
        }

		/**
		 * Performs the SQL query given, or the one stored based on this object.
		 *
		 * <p>If sql is passed in then this will perform the SQL value given.
		 * If there is no SQL value then this will generate an SQL statement
		 * based on the values stored within this database object. The result
		 * is returned.</p>
		 *
		 * @return An array of objects containing the results (if any) from the query performed.
		 */
		public function query( $sql=null, $map=null, $filter=null )
		{
			$conn = $this->newConnection();
            $events = null;
            $justField = $this->justField;
            $grabOne = $this->grabOne;
global $str2;

			if ( $sql === null ) {
                $isGenerated = true;

				$sqlResult = $this->generateSQL();

                if ( $this->validateTables !== null ) {
                    DB::checkTables( $this->validateTables, $this->activerecord['table'] );
                }

                $sql = $sqlResult->sql;

                $allEvents = $this->events;

                if (
                        !$this->skipEvents &&
                        !$justField  &&
                        $allEvents != null &&
                        $sqlResult->isSelect &&
                        $sqlResult->tables
                ) {
                    $sqlTables = $sqlResult->tables;
                    $sqlTablesCount = count( $sqlTables );
                    $allEventsCount = count( $allEvents );

                    for ( $i = 0; $i < $allEventsCount; $i++ ) {
                        $tablesEvent = $allEvents[$i];
                        $tables = $tablesEvent['tables'];

                        $count = count( $tables );
                        if ( $count > 0 ) {
                            for ( $j = 0; $j < $sqlTablesCount; $j++ ) {
                                $table = $sqlTables[$j];

                                if ( in_array(strtolower($table), $tables) ) {
                                    $count--;

                                    if ( $count === 0 ) {
                                        break;
                                    }
                                }
                            }
                        }

                        if ( $count <= 0 ) {
                            $event = $tablesEvent['event'];

                            if ( $events === null ) {
                                $events = array( $event );
                            } else {
                                $events[]= $event;
                            }
                        }
                    }
                }

				$this->clear();
			} else {
                $isGenerated = false;
            }

            DB::$lastQuery = $sql;

            if ( $this->beforeQuery !== null ) {
                $fun = $this->beforeQuery;
                $fun( $sql );
            }

            $result = $this->executeQuery( $sql, $conn, $events, $justField, $grabOne, $map, $filter );

            if ( $this->hasError ) {
                $error = mysql_error();

                $this->hasError = false;
                if ( $this->afterQuery !== null ) {
                    $fun = $this->afterQuery;
                    $fun( $sql, false, $error );
                }

                throw new Exception( "mySQL error: $error" );
            } else {
                if ( $this->afterQuery !== null ) {
                    $fun = $this->afterQuery;
                    $fun( $sql, true, '' );
                }

                return $result;
            }
		}

        public function validateTables() {
            if ( $this->validateTables === null ) {
                try {
                    $conn = $this->newConnection();
                    $nullVal = null;

                    $this->validateTables = $this->executeQuery(
                            "SHOW TABLES FROM " . $this->database,
                            $conn,
                            $nullVal,
                            true,
                            false,
                            $nullVal,
                            $nullVal
                    );
                } catch ( Exception $ex ) {
                    $this->validateTables = false;
                }
            }
        }

		private function executeQuery( $sql, $conn, &$events, $justField, $grabOne, &$map, &$filter )
		{
			$mysqlResults = mysql_query( $sql, $conn );

            /*
             * Some SQL queries don't return a value, such as 'update'.
             * This tests for that, and returns null.
             */
            if ( $mysqlResults === true ) {
                $result = null;
            /*
             * SQL query has failed in some way.
             */
            } else if ( $mysqlResults === false ) {
                $this->hasError = true;
                $result = null;
            } else {
                if ( $grabOne ) {
                    $result = null;
                } else {
                    $result = array();
                }

                // objects, and run all events on the object,
                // this is the common case
                if ( $justField === false && $events !== null ) {
                    if ( $grabOne ) {
                        while ( $resultObj = mysql_fetch_object($mysqlResults) ) {
                            foreach ( $events as $event ) {
                                $newResultObj = call_user_func( $event, $resultObj );

                                if ( $newResultObj === false ) {
                                    $resultObj = false;
                                    break;
                                } else if ( $newResultObj !== null ) {
                                    $resultObj = $newResultObj;
                                }

                                $results[]= $resultObj;
                            }

                            if ( $resultObj !== false ) {
                                $resultObj = DB::executeQueryCallbacks( $resultObj, $map, $filter );

                                $result = $resultObj;
                                break;
                            }
                        }
                    } else {
                        while ( $resultObj = mysql_fetch_object($mysqlResults) ) {
                            foreach ( $events as $event ) {
                                $newResultObj = call_user_func( $event, $resultObj );

                                if ( $newResultObj === false ) {
                                    $resultObj = false;
                                    break;
                                } else if ( $newResultObj !== null ) {
                                    $resultObj = $newResultObj;
                                }
                            }

                            if ( $resultObj !== false ) {
                                $resultObj = DB::executeQueryCallbacks( $resultObj, $map, $filter );

                                if ( isset($resultObj) ) {
                                    $result[]= $resultObj;
                                }
                            }
                        }
                    }
                // return either one value, or an array of values
                } else if ( $justField ) {
                    if ( $grabOne ) {
                        while ( $resultRow = mysql_fetch_row($mysqlResults) ) {
                            $resultObj = DB::executeQueryCallbacks( $resultRow[0], $map, $filter );

                            if ( isset($resultObj) ) {
                                $result = $resultObj;
                                break;
                            }
                        }
                    } else {
                        while ( $resultRow = mysql_fetch_row($mysqlResults) ) {
                            $resultObj = DB::executeQueryCallbacks( $resultRow[0], $map, $filter );

                            if ( isset($resultObj) ) {
                                $result[]= $resultObj;
                            }
                        }
                    }
                // return one object
                } else if ( $grabOne ) {
                    while ( $resultObj = mysql_fetch_object($mysqlResults) ) {
                        $resultObj = DB::executeQueryCallbacks( $resultObj, $map, $filter );

                        if ( isset($resultObj) ) {
                            $result = $resultObj;
                        }
                    }
                // return an array of objects
                } else {
                    while ( $resultObj = mysql_fetch_object($mysqlResults) ) {
                        $resultObj = DB::executeQueryCallbacks( $resultObj, $map, $filter );

                        if ( isset($resultObj) ) {
                            $result[]= $resultObj;
                        }
                    }
                }
			}

			mysql_close( $conn );

			return $result;
		}

		/**
		 * Performs a delete query based on the table and
         * fields currently selected.
         *
         * This will raise an error if no key is provided,
         * and there is no where, or limit on the delete.
         *
         * This is to avoid accidental deletion of everything.
         *
         * If you want to delete all entires, then use
         * 'deleteAll'.
         * 
         * If an array is given, but it is empty, then this
         * will just silently return. This is so you can pipe
         * one request straight into another.
		 */
		public function delete( $key = null, $val = null )
		{
            if (
                    ( $val === null && is_array($key) && count($key) === 0 ) ||
                    ( is_array($val) && count($val) === 0 )
            ) {
                return null;
            } else {
                return $this->deleteAll( $key, $val, true );
            }
		}

        /**
         * The normal 'delete' will raise an error if no where
         * clause is provided. This doesn't!
         *
         * That way you can delete all items in a table, if
         * that is what you want to happen.
         */
		public function deleteAll( $key = null, $val = null, $deleteAllCheck = false )
        {
            if ( $key !== null ) {
                if ( $val === null ) {
                    $this->equal( 'id', $key );
                } else {
                    $this->equal( $key, $val );
                }
            }

			$this->activerecord['delete'] = true;

            $ar = $this->activerecord;

            if ( $deleteAllCheck && ! DB::hasWhere($ar) ) {
                throw new Exception("Calling 'delete' with no where, limit, equal or other checks (it's a delete all).");
            } else {
                return $this->query();
            }
        }

		/**
		 * Generates the SQL query from the settings placed onto this database object
		 * and returns it. No query is performed and the database is left unaltered.
		 *
		 * This is mainly for debugging purposes and so you can get copies of the SQL
		 * that your queries will make.
		 *
		 * @return The SQL code to perform the query currently setup on this database object.
		 */
		private function generateSQL()
		{
			$sql = '';
			$ar =& $this->activerecord;
			$isSelect = false;

			if ( ! isset($ar['table']) ) {
				throw new Exception("No table selected in query.");
			}

			// INSERT values
			if ( isset($ar['update_on']) ) {
                $this->ensureNotJoin( 'Update' );
				$sql = $this->generateSQLUpdate();
			} else if ( isset($ar['insert']) ) {
				if ( count($ar['table']) > 1 ) {
					throw new Exception("Multiple tables selected on insert (can only select one).");
				}
                $this->ensureNotJoin( 'Insert' );

				$sql = $this->generateSQLInsert();
			} else if ( isset($ar['delete']) ) {
                $this->ensureNotJoin( 'Delete' );
				$sql = $this->generateSQLDelete();
			} else {
                $isSelect = true;

				$sql = $this->generateSQLSelect();
			}

			return new DBSQLQuery( $sql, $ar['event_tables'], $isSelect );
		}

        private function ensureNotJoin( $operation )
        {
            $this->ensureNotSet( 'join', "Cannot use a join whilst performing a " . $operation );
        }

        private function ensureNotSet( $key, $err )
        {
            if ( isset($this->activerecord[$key]) ) {
                throw new Exception( $err );
            }
        }

		private function generateSQLUpdate()
		{
			$ar =& $this->activerecord;
			$sql = 'UPDATE ';
			$sql .= $this->generateSQLFromList( $ar );

			$sql .= ' SET ';

			$isFirstIteration = true;

			// error checking
			if ( isset($ar['insert']) && count($ar['insert']) > 0 ) {
				$updateRow = $ar['insert'][0];
			} else {
				throw new Exception( "No rows given in update." );
			}

			foreach ( $updateRow as $field => $value ) {
				if ( $isFirstIteration ) {
					$isFirstIteration = false;
				} else {
					$sql .= ', ';
				}

				$sql .= $field . ' = "' . DB::dbSafe($value) . '"';
			}

			$sql .= $this->generateSQLWheres( $ar );

			if ( count($ar['table']) == 1 ) {
				$sql .= $this->generateSQLOrder( $ar );
				$sql .= $this->generateSQLLimitUpdate( $ar );
			}

			return $sql;
		}

		private function generateSQLInsert()
		{
			$sql = '';
			$ar =& $this->activerecord;
			$fields = array();
			$firstIterationOuter = true;

			foreach ( $ar['insert'] as $iArray ) {
				if ( $firstIterationOuter ) {
					$sql .= ' ("';
				} else {
					$sql .= ', ("';
				}

				$firstIterationInner = true;
				foreach ( $iArray as $field => $value ) {
					if ( ! $firstIterationOuter ) {
						if ( ! isset($fields[$field]) ) {
							throw new Exception( "Inconsistent field found '" . $field . "' (this field was found in one set of values but not in earlier insert values)." );
						}
					} else {
						if ( isset($fields[$field]) ) {
							throw new Exception( "Double field found in values, field: " . $field );
						} else {
							$fields[$field] = $field;
						}
					}

					if ( $firstIterationInner ) {
						$firstIterationInner = false;
					} else {
						$sql .= '", "';
					}

					$sql .= DB::dbSafe( $value );
				}

				$sql .= '")';
				$firstIterationOuter = false;
			}

			if ( isset($ar['override']) ) {
				$preSQL = 'REPLACE';
			} else {
				$preSQL = 'INSERT';
			}

            // checked in outer generateSQL method to ensure this exists
			$preSQL .= ' INTO ' . $ar['table'][0];
			$preSQL .= ' (' . implode( ', ', $fields );

			return $preSQL . ') VALUES ' . $sql;
		}

		private function generateSQLDelete()
		{
			$sql = 'DELETE ';
			$ar =& $this->activerecord;

			$sql .= $this->generateSQLFrom( $ar );
			$sql .= $this->generateSQLWheres( $ar );
			$sql .= $this->generateSQLOrder( $ar );
			$sql .= $this->generateSQLLimit( $ar );

			return $sql;
		}

		private function generateSQLSelect()
		{
			$sql = 'SELECT ';
			$ar =& $this->activerecord;

			$sql .= $this->generateSQLFields( $ar );
			$sql .= $this->generateSQLFrom( $ar );
            $sql .= $this->generateSQLJoins( $ar );
			$sql .= $this->generateSQLWheres( $ar );
			$sql .= $this->generateSQLGroup( $ar );
			$sql .= $this->generateSQLOrder( $ar );
			$sql .= $this->generateSQLLimit( $ar );

			return $sql;
		}

		private function generateSQLOrder( &$ar )
		{
            if ( isset($ar['order']) ) {
                $orders =& $ar['order'];
                $orderSqls = array();

                foreach ( $orders as $order ) {
                    $orderSqls[]= $order['field'] . ' ' . $order['direction'];
                }

                return ' ORDER BY ' . implode( ',', $orderSqls );
            } else {
                return '';
            }
		}

		private function generateSQLJoins( &$ar )
		{
			$sql = '';

            if ( isset($ar['join'] ) ) {
                $joins = $ar['join'];
                $len = count( $joins );

                for ( $i = 0; $i < $len; $i++ ) {
                    $join = $joins[$i];

                    $type = $join[0];
                    if ( $type === 'left' ) {
                        $type = ' LEFT JOIN ';
                    } else if ( $type === 'right' ) {
                        $type = ' RIGHT JOIN ';
                    } else if ( $type === '' ) {
                        $type = ' JOIN ';
                    }

                    $sql .= $type . $join[1] . ' ON ' . $join[2];
                }
            }

			return $sql;
		}

		private function generateSQLGroup( &$ar )
		{
			$sql = '';

            $sql = DB::issetConcat( $sql, ' GROUP BY ', '', $ar, 'group_field'     );
            $sql = DB::issetConcat( $sql, ' '         , '', $ar, 'group_direction' );

			return $sql;
		}

        private function generateSQLLimitUpdate( &$ar )
        {
            if ( isset($ar['limit_count']) ) {
                return 'LIMIT ' . $ar['limit_count'];
            } else {
                return '';
            }
        }

		private function generateSQLLimit( &$ar )
		{
			$sql = '';

			if ( isset($ar['limit_count']) ) {
				$sql .= ' LIMIT ' . $ar['limit_count'];

				if ( isset($ar['limit_offset']) && !isset($ar['update']) && !isset($ar['delete']) ) {
					$sql .= ' OFFSET ' . $ar['limit_offset'];
				}
			}

			return $sql;
		}

		private function generateSQLFields( &$ar )
		{
			$sql = '';

			if ( isset($ar['field']) ) {
				$sql .= implode( ', ', $ar['field'] );
			} else {
				$sql .= ' * ';
			}

			return $sql;
		}

		private function generateSQLFrom( &$ar )
		{
			$sql = '';

			// FROM
			$sql .= ' FROM ';
			$sql .= $this->generateSQLFromList( $ar );

			return $sql;
		}

		private function generateSQLFromList( &$ar )
		{
			return implode( ',', $ar['table'] );
		}

		private function generateSQLWheres( &$ar )
		{
			$sql = '';

            // This is for ensuring the join is seperate to the where's.
            // The non-join where's are bracketed to ensure they do not affect the join.
            $closeJoin = false;

			// WHERE
			if ( DB::hasWhere($ar) ) {
				$sql .= ' WHERE ';

				// First where is to state that there are no where clauses printed until this is false.
				$firstWhere = true;
			}

			if ( isset($ar['equal']) ) {
				if ( ! $firstWhere ) {
					$sql .= ' AND ';
				} else {
					$firstWhere = false;
				}

                $sql .= DB::generateEqualitySQLClauses( $ar['equal'], '=' );
			}

			if ( isset($ar['not_equal']) ) {
				if ( ! $firstWhere ) {
					$sql .= ' AND ';
				} else {
					$firstWhere = false;
				}

                $sql .= DB::generateEqualitySQLClauses( $ar['not_equal'], '!=' );
			}

			if ( isset($ar['less_than']) ) {
				if ( ! $firstWhere ) {
					$sql .= ' AND ';
				} else {
					$firstWhere = false;
				}

                $sql .= DB::generateEqualitySQLClauses( $ar['less_than'], '<' );
			}

			if ( isset($ar['less_than_equal']) ) {
				if ( ! $firstWhere ) {
					$sql .= ' AND ';
				} else {
					$firstWhere = false;
				}

                $sql .= DB::generateEqualitySQLClauses( $ar['less_than_equal'], '<=' );
			}

			if ( isset($ar['greater_than']) ) {
				if ( ! $firstWhere ) {
					$sql .= ' AND ';
				} else {
					$firstWhere = false;
				}

                $sql .= DB::generateEqualitySQLClauses( $ar['greater_than'], '>' );
			}

			if ( isset($ar['greater_than_equal']) ) {
				if ( ! $firstWhere ) {
					$sql .= ' AND ';
				} else {
					$firstWhere = false;
				}

                $sql .= DB::generateEqualitySQLClauses( $ar['greater_than_equal'], '>=' );
			}

			if ( isset($ar['match']) ) {
				if ( ! $firstWhere ) {
					$sql .= ' AND ';
				} else {
					$firstWhere = false;
				}

				$firstIteration = true;
				foreach ( $ar['match'] as $clause ) {
					if ( $firstIteration ) {
						$firstIteration = false;
					} else {
						$sql .= ' AND ';
					}

					$fields = implode( ',', $clause['fields'] );
					$text = DB::dbSafe( $clause['text'] );

					$sql .= ' MATCH( ' . $fields . ' ) AGAINST("' . $text . '") ';
				}
			}

			if ( isset($ar['where']) ) {
				if ( ! $firstWhere ) {
					$sql .= ' AND ';
				} else {
					$firstWhere = false;
				}

				$sql .= implode( ' AND ', $ar['where'] );
			}

			if ( isset($ar['where_or']) ) {
				if ( ! $firstWhere ) {
					$sql .= ' OR ';
				} else {
					$firstWhere = false;
				}

				$sql .= implode( ' OR ', $ar['where_or'] );
			}

			if ( $closeJoin ) {
				$sql .= ' ) ';
			}

			return $sql;
		}

		/**
		 * Clears all of the various settings set on this database object.
		 *
		 * <p>Clears all of the various settings set on this database object.
		 * You can then create a new query using this object.
		 * Bear in mind this will be cleared automatically whenever you call
		 * the query method in order to perform a query built using this
		 * databases methods.</p>
		 */
		public function clear()
		{
            // reset the SQL generation
			$this->activerecord = array();

            // clear all flags
            $this->skipEvents   = false;
            $this->justField    = false;
            $this->grabOne      = false;

			return $this;
		}

		/**
		 * This is a quick way to select some fields from a single table.
		 *
		 * <p>A quick query builder. If fields is ommitted then all fields will be selected.</p>
		 *
		 * <p>The condition can be used in one of two ways. If an array is given
		 * with the form of (array['field'] = 'value') then those mappings are
		 * used as the basis for the conditions in the SQL. The SQL will use
		 * the value's stored to match the fields that they are stored under.</p>
		 *
		 * <p>The limitMax is for stating the maximum number of results to return,
		 * whilst limitOffset is for stating the offset to start the query from.</p>
		 *
		 * <p>Finally ordered must be 'ASC' or 'DESC'.</p>
		 *
		 * @param table The table you will be querying from.
		 * @param field An array containing the fields to retrieve.
		 * @param condition An array containing the matches to check for, or a custom SQL boolean condition which will be injected (i.e. 'name = "john").
		 * @param limitMax The maximum number of rows to return.
		 * @param limitOffset The row to start returning rows from, counts from 0.
		 * @return An array of objects from the query.
		 */
		public function query_select( $table, $fields=null, $condition=null, $limitMax=null, $limitOffset=null )
		{
			$sql = 'SELECT ';
			if ( $fields == null ) {
				$sql .= '*';
			} else if ( is_array($fields) ) {
				$addComma = false;
				foreach ( $fields as $field ) {
					if ( $addComma ) {
						$sql .= ', ';
					} else {
						$addComma = true;
					}

					$sql .= $field;
				}
			} else if ( is_string($fields) ) {
				$sql .= $fields;
			}

			$sql .= ' FROM ' . $table;
			if ( $condition != null ) {
				$sql .= ' WHERE ';

				if ( is_array($condition) ) {
					$addAnd = false;
					foreach ( $condition as $field => $val ) {
						if ( $addAnd ) {
							$sql .= ' AND ';
						} else {
							$addAnd = true;
						}

						$sql .= ' ( ' . mysql_real_escape_string($field) . ' = "' . mysql_real_escape_string($val) . '" ) ';
					}
				} else {
					$sql .= $condition;
				}
			}

			// add the limit
			if ( $limitMax != null ) {
				if ( $limitOffset != null ) {
					$sql .= ' LIMIT '.$limitOffset.', '.$limitMax;
				} else {
					$sql .= ' LIMIT '.$limitMax;
				}
			} else {
				if ( $limitOffset != null ) {
					$sql .= ' LIMIT '.$limitOffset.', 18446744073709551615';
				}
			}

			return $this->query( $sql );
		}

		public function from()
		{
			if ( func_num_args() == 0 ) {
				throw new Exception( "No tables given." );
			}

			$args = func_get_args();
			$this->activerecord['table'] = $this->arrayMerge(
					$this->activerecord['table'],
					$args
			);
            $this->activerecord['event_tables'] = $this->arrayMerge(
					$this->activerecord['event_tables'],
					$args
			);

			return $this;
		}

		public function limit( $offset, $count=null )
		{
			if ( $count === null ) {
                $this->activerecord['limit_count']  = $offset; // offset if actually count on single parameter
			} else {
				if ( $offset < 0 ) {
					throw new Exception( "Offset cannot be less then 0, was given: " . $offset );
				} else if ( $count < 0 ) {
					throw new Exception( "Count cannot be less then or equal to 0, was given: " . $count );
				}

				$this->activerecord['limit_offset'] = $offset;
				$this->activerecord['limit_count']  = $count;
			}

			return $this;
		}

		/**
		 * A multi-purpose insert method for inserting rows into a table.
		 *
		 * <p>Can be used in one of two ways. First you can use this for stating a table and
		 * then pass in 1 or more arrays of values to be inserted into that table.
		 * For example: $db->insert( 'artists', array('name' => 'Radiohead'), array('name' => 'The Sea and Cake') );</p>
		 *
		 * <p>Secondly if you have already stated the table your inserting too then you can just
		 * pass in multiple arrays.<br />
		 * For example $db->artists->insert( array('name' => 'Radiohead'), array('name' => 'The Sea and Cake') );</p>
		 *
		 * <p>In both cases each array passed in is considered to be a seperate set of values
		 * to insert. The array should contain mappings of 'field' to 'value' where field
		 * refers to fields in the table.</p>
		 *
		 * <p>If any of the arrays given are 2D then they will be presumed to be an array of rows to enter.
		 * This allows you to insert multiple rows like:
		 *     $db->artists->insert( array(
		 *             array('name' => 'Radiohead'),
		 *             array('name' => 'The Sea and Cake')
		 *     ) );</p>
		 */
		public function insert()
		{
			if ( func_num_args() == 0 ) {
				throw new Exception( "No insert values given." );
			}

			$table = func_get_arg( 0 );
			if ( ! is_array($table) ) {
				if ( func_num_args() == 1 ) {
					throw new Exception( "No insert values given." );
				}

				$this->from( $table );
				$startIndex = 1;
			} else {
				$startIndex = 0;
			}

			$paramArray = func_get_args();
			$numArgs = func_num_args();
			for ( $i = $startIndex; $i < $numArgs; $i++ ) {
				$this->insertArray( $paramArray[$i] );
			}

			return $this;
		}

		/**
		 *
		 */
		public function update( $arr=null )
		{
			$this->activerecord['update_on'] = true;

			if ( $arr !== null ) {
				$this->insert( $arr );
			}

			return $this;
		}

		/**
		 * Inner helper function for inserting an associative array into
		 * the inserts bit of the database query building.
		 * This automatically expands any inner arrays it finds as elements
		 * to be re-entered.
		 *
		 *
		 */
		private function insertArray( &$array )
		{
			if ( DB::isArray2D($array) ) {
				foreach ( $array as $arr ) {
					$this->insertArray( $arr );
				}
			} else {
				$newValues = array();
				foreach ( $array as $key => $value ) {
					$newValues[ $key ] = $value;
				}

                $this->appendValue( 'insert', $newValues );
			}
		}

		private function insertValue( $field, $value )
		{
			if ( isset($this->activerecord['insert']) ) {
                $this->activerecord['insert'][ $field ] = $value;
			} else {
				$this->insert( array($field => $value) );
			}

			return $this;
		}

        /*
         * This presumes that the given key is an index in the internal SQL
         * data structure, that can hold multiple values.
         *
         * If there is already an array set under that key, then this will
         * append the given value to the array. Otherwise it will set an array
         * that also includes the given value.
         */
        private function appendValue( $key, $value )
        {
            if ( isset($this->activerecord[$key]) ) {
				$this->activerecord[$key][] = $value;
			} else {
				$this->activerecord[$key] = array( $value );
			}
        }

		/**
		 * This can be called multiple times to allow you to build up a
         * selection of orderings.
         *
         * This can be called in one of two ways, first:
         *  $db->order( 'artist' ) - orders by artist, descending
         *  $db->order( 'artist', 'asc' ) - orders by artist, ascending
         *  $db->order( 'artist' )->order( 'album' ) - orders by artist, then album, both descending
         *  $db->order( array( 'artist', 'album' ) ) - orders by artist, then album
         *  $db->order( array( 'artist' => 'asc', 'album' ) - orders by artist ascending, then by album descending
         *  $db->order( array( 'artist' => 'asc', 'album' => 'desc' ) - orders by artist ascending, then by album descending
         *
         * With the array version, the $direction parameter overrides any other direction given.
         *
         *  $db->order( array('artist' => 'asc', 'album' => 'asc'), 'desc' ) - orders by artist and then album, both _descending_
         *
         * Note there is a known bug in this method.
         * In databases you can have an int for a column name; i.e. you can call
         * a column '52'. If you use an int column name and you use the array
         * version of this method, then the column name _must_ be specified as
         * a string and not an int.
         *
         * For example, if you have a column called '52', and you use the array version,
         * then you must pass in the '52' as a string and _not_ as an int.
         *
		 * @param field The field to order the results by.
		 * @param direction 'DESC' or 'ASC' for descending or ascending ordering, defaults to 'DESC'.
		 */
		public function order( $field, $direction=null )
		{
            if ( is_array($field) ) {
                $default = $direction ?
                        $direction :
                        DB::ORDER_DEFAULT ;
                $orders = array();

                foreach ( $field as $k => $v ) {
                    // this is for: $db->order( array('artist', 'album') )
                    if ( is_int($k) ) {
                        $orders[]= array(
                                'field'     => $v,
                                'direction' => $default
                        );
                    // this is for: $db->order( array('artist' => 'asc', 'album' => 'desc') )
                    } else {
                        if ( $direction === null ) {
                            $orders[]= array(
                                    'field'     => $k,
                                    'direction' => $v
                            );
                        } else {
                            $orders[]= array(
                                    'field'     => $k,
                                    'direction' => $direction
                            );
                        }
                    }
                }

                $this->activerecord['order'] = $this->arrayMerge(
                        $this->activerecord['order'],
                        $orders
                );
            } else {
                if ( $direction == null ) {
                    $direction = DB::ORDER_DEFAULT;
                }

                $order = array(
                    'field'     => $field,
                    'direction' => $direction
                );

                if (
                        isset($this->activerecord['order']) &&
                        is_array($this->activerecord['order'])
                ) {
                    $this->activerecord['order'][] = $order;
                } else {
                    $this->activerecord['order'] = array( $order );
                }
            }

			return $this;
		}

		/**
		 *
		 * @param field The field to order the results by.
		 * @param direction 'DESC' or 'ASC' for descending or ascending ordering.
		 */
		public function group( $field, $direction=null )
		{
			$this->activerecord['group_field']     = $field;
			$this->activerecord['group_direction'] = $direction;

			return $this;
		}

        /**
         * ## WARNING! ##
         * This does not screen the column, it allows SQL injection!
         *
         * Sets the values to select.
         */
		public function select()
		{
			if ( func_num_args() == 0 ) {
				throw new Exception( "No select fields given." );
			}

			$args = func_get_args();
			$this->activerecord['field'] = $this->arrayMerge(
					$this->activerecord['field'],
					$args
			);

			return $this;
		}

		/**
		 * Adds a where clause for 'field = value'. The value is presumed to be a literal
		 * and so will be wrapped in quotes. The field will be presumed to be a column name.
		 *
         * If the value is an array, then equal or is performed on every match.
         * For example:
         *
         *      $this->db->users->
         *              equal( 'id', $userID )->
         *              equal( 'type', array( $adminType, $userType )->
         *              get();
         *
         * This will find a user:
         *  = with the id $userID
         *  = with the type $adminType or $userType
         *
         * But one item in the array must match!
         *
		 * @param field The column for the match.
		 * @param The value to match the column against.
		 * @return This database object.
		 */
		public function equal($field, $value=null)
		{
            if ( $value !== null || is_array($field) ) {
                return $this->setEquality( 'equal', $field, $value );
            // presume 'equal( $foo )' is actually 'equal( 'id', $foo )'
            } else {
                return $this->setEqual( 'equal', 'id', $field );
            }
		}

        /**
         * Adds a not equal clause to match values which aren't the one given.
         */
        public function notEqual( $field, $value )
        {
            return $this->setEquality( 'not_equal', $field, $value );
        }

        public function lessThan( $field, $value=null )
        {
            return $this->setEquality( 'less_than', $field, $value );
        }

        public function lessThanEqual( $field, $value=null )
        {
            return $this->setEquality( 'less_than_equal', $field, $value );
        }

        public function greaterThan( $field, $value=null )
        {
            return $this->setEquality( 'greater_than', $field, $value );
        }

        public function greaterThanEqual( $field, $value=null )
        {
            return $this->setEquality( 'greater_than_equal', $field, $value );
        }

        private function setEquality($type, $field, $value)
        {
            if ( $value !== null ) {
                $this->setEqual( $type, $field, $value );
            } else if ( is_array($field) ) {
                if ( count($field) === 0 ) {
                    throw new Exception( "no fields given in array, for setting equality condition" );
                }

                foreach ( $field as $key => $val ) {
                    $this->setEqual( $type, $key, $val );
                }
            } else {
                throw new Exception( "no field and value given for setting equality condition: '" . $type . "', field: '" . $field . "'" );
            }

            return $this;
        }

        private function setEqual( $arField, $field, $value )
        {
            if ( isset($this->activerecord[$arField]) ) {
				$this->activerecord[$arField][$field] = $value;
			} else {
				$this->activerecord[$arField] = array( $field => $value );
			}

			return $this;
        }

		/**
		 * This should only be used on fields set to use fulltext!
		 *
		 * Variable length parameters but with a minimum of two.
		 * All parameters except the last one are seen as fields to
		 * match and search within. The last parameter is the search
		 * text to search for within those fields.
		 */
		public function match()
		{
			$numArgs = func_num_args();

			// last parameter is text
			$text = func_get_arg( $numArgs-1 );

			// all parameters are fields except the last one
			$args = func_get_args();
			$fields = array_slice( $args, 0, $numArgs-1 );
			
			$clause = array(
                    'fields' => $fields,
                    'text'   => $text
			);

            $this->appendValue( 'match', $clause );

			return $this;
		}

		public function where()
		{
			if ( func_num_args() == 0 ) {
				throw new Exception( "No where conditions were given." );
			}

			$args = func_get_args();
			$this->activerecord['where'] = $this->arrayMerge(
					$this->activerecord['where'],
					$args
			);

			return $this;
		}

		/**
		 * This is used for inserting. When called this tells the database to override any rows that
	     * clash with the inserted data. This clash only occurs if there is a primary or unique key
	     * on tables (as duplicates are allowed if they do not exist).
		 */
		public function override()
		{
			$this->activerecord['override'] = true;
			return $this;
		}

		public function whereOr()
		{
			if ( func_num_args() == 0 ) {
				throw new Exception( "No where conditions were given." );
			}

			$args = func_get_args();
			$this->activerecord['where_or'] = $this->arrayMerge(
					$this->activerecord['where_or'],
					$args
			);

			return $this;
		}

		/**
		 * This method can work in one of two ways depending on if you give it 2 or 3 parameters.
		 * The first parameter always refers to the table you are joining with.
		 *
		 * The first way to use this is to pass in two fields in the first and second parameter.
		 * These will then generate a where clause for them to be equal. For example
		 * join( 'artist', 'albums.id', 'artists.album_id' ) will generate the clause
		 * 'albums.id = artists.album_id'.
		 *
		 * The second way is to give your own where clause in the second paramter, i.e.
		 * joine( 'artists', 'albums.id = artists.album_id' ).
		 *
		 * @param table The table you are joining to for this query.
		 * @param selectField The first field to select values from, or your own clause for matching.
		 * @param tableField The second field you are joining against.
		 */
		public function join( $table, $selectField, $tableField=null )
		{
            return $this->setJoin( '', $table, $selectField, $tableField );
		}

        public function leftJoin( $table, $selectField, $tableField=null )
        {
            return $this->setJoin( 'left', $table, $selectField, $tableField );
        }

        public function rightJoin( $table, $selectField, $tableField=null )
        {
            return $this->setJoin( 'right', $table, $selectField, $tableField );
        }

        private function setJoin( $type, $table, $selectField, $tableField=null )
        {
            $clause = ( $tableField == null ) ?
                    $selectField :
                    $selectField . ' = ' . $tableField
            ;

            $this->appendValue( 'join', array( 'left', $table, $clause ) );
            $this->appendValue( 'event_tables', $table );

            return $this;
        }

		/**
		 * Returns the number of elements in the currently selected table.
		 * i.e.
		 *     $db->table->count()
		 *     $db->count( 'table' )
		 *
		 * Note this is will execute the query and clear the content of the
		 * database object.
         *
         * Note that DB events are automatically skipped when this is executed.
		 *
		 * This is returned as an actual value rather then as a result object.
		 */
		public function count( $table=null )
		{
			if ( $table != null ) {
				$this->from( $table );
			}

            return (int) ( $this->getField('COUNT(*) AS `count`') );
		}

		/**
		 * This is a query method, it will execute the query data so far.
		 *
		 * It is for checking if a table has at least one row for the query described.
		 *
		 * @return True if the query returns at least one row, otherwise false.
		 */
		public function exists()
		{
            $numArgs = func_num_args();

            if ( $numArgs === 1 ) {
                $this->equal( func_get_arg(0) );
            } else if ( $numArgs === 2 ) {
                $this->equal( func_get_arg(0), func_get_arg(1) );
            }

			$this->limit(1);
			return $this->count() > 0;
		}

		/**
		 * Given two values this will merge the right one to the left one if they are both arrays.
		 * This is just like array_merge.
		 *
		 * How this differs is that it expects one of left or right to possibly by a non-array
		 * (either null or undefined).
		 */
		private function arrayMerge( &$left, &$right )
		{
			if ( is_array($left) ) {
				if ( is_array($right) ) {
					return array_merge( $left, $right );
				} else {
					return $left;
				}
			} else {
				if ( is_array($right) ) {
					return $right;
				} else {
					throw new Exception( 'Neither left or right are valid arrays.' );
				}
			}
		}
	}

    class DBSQLQuery
    {
        public $sql;
        public $tables;
        public $isSelect;

        public function __construct( $sql, $tables, $isSelect )
        {
            $this->sql = $sql;
            $this->tables = $tables;
            $this->isSelect = $isSelect;
        }
    }
