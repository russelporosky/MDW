<?php
/**
 * MySQL Database class.
 *
 * @package MDW
 * @subpackage Database
 * @version 0.2.1
 * @license http://www.gnu.org/licenses/gpl.html
 * @copyright 2011 IndyArmy Network, Inc.
 * @author Russ Porosky <russ@indyarmy.com>
 */

class DBmysql implements iDatabase {
	/**
	 * Tracks IDs of all active database connections.
	 */
	private $connections = array();

	/**
	 * The unique ID of the currently active database connection.
	 */
	private $activeConnection;

	/**
	 * Cached queries from all connections.
	 */
	private $queryCache = array();

	/**
	 * Cached query data from all connections.
	 */
	private $dataCache = array();

	/**
	 * Cached resultsets from all connections.
	 */
	private $resultCache = array();

	/**
	 * The most recent resultset as an associative array.
	 */
	private $last;

	/**
	 * The number of queries on all connections.
	 */
	public $queryCounter = 0;

	/**
	 * Holds a list of destructor methods that need to execute before the
	 * database class is destroyed.
	 */
	private $destructors = array();

	/**
	 * The constructor isn't currently used by this class.
	 */
	public function __construct() {
	}

	/**
	 * Removes all connections to the database(s).
	 */
	public function __destruct() {
		for ($i = 0, $c = count($this->connections); $i < $c; $i++) {
			$this->connections[$i] = null;
		}
	}

	/**
	 * Creates a new connection to the database and returns a connection ID.
	 *
	 * @param string $host The hostname of the database server.
	 * @param string $user The username of the database.
	 * @param string $pass The password for the user.
	 * @param string $db The database to connect to.
	 * @return int A unique connection ID.
	 */
	public function newConnection($host, $user, $pass, $db) {
		try {
			$this->connections[] = new PDO('mysql:host='.$host.';dbname='.$db, $user, $pass, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
		} catch (PDOException $e) {
			switch ($e->getCode()) {
				case 1045:
					throw new Exception('Cannot connect to database.', 400);
					break;
			}
			exit;
		}
		$connectionId = count($this->connections) - 1;
		return $connectionId;
	}

	/**
	 * Sets the currently active database connection.
	 *
	 * @param int $new The connection ID to use for future queries.
	 */
	public function setActiveConnection($new) {
		$this->activeConnection = (int)$new;
	}

	/**
	 * Prepares and executes a query against the currently active connection.
	 * This function accepts prepared statements or regular queries. In the case
	 * of a prepared statement, the number of items in $data must be the same as
	 * the number of "?" characters in $query. If there is only a single $data
	 * item, it does not need to be an array. $args is an array of option
	 * arguments - int start, int rows
	 *
	 * @param string $query The query to be executed.
	 * @param mixed $data 
	 * @param array $args
	 */
	public function executeQuery($query = null, $data = null, $args = null) {
		/**
		 * The query object.
		 */
		$db = null;

		/**
		 * Array of errors, if any.
		 */
		$dberror = null;

		if (isset($query)) {
			if (!is_array($data)) {
				$data = array($data);
			}
			if (!is_array($args)) {
				$args = array($args);
			}
			if (isset($args['start']) && isset($args['rows'])) {
				if ((int)$args['start'] >= 0 && (int)$args['rows'] >= 1) {
					$query .= ' limit '.(int)$args['start'].', '.(int)$args['rows'];
				}
			}
			$this->queryCache[] = $query;
			$this->dataCache[] = $data;
			$db = $this->connections[$this->activeConnection]->prepare($query.'; ');
			$db->execute(array_values($data));
			$dberror = $db->errorInfo();
			if ($dberror[0] == '42S02') {
				throw new exception('Table could not be found.', 401);
			}
			$this->resultCache[] = $this->last = $db;
			$this->queryCounter++;
		}
	}

	/**
	 * Returns the number of rows affected by the most recent query.
	 *
	 * @return int Rows affected.
	 */
	public function numRows() {
		return $this->last->rowCount();
	}

	/**
	 * Returns a row from the last query as long as rows exist. Return FALSE
	 * if there are no more rows in the resultset.
	 *
	 * @return mixed Associative array of row values, or FALSE if empty.
	 */
	public function getRows() {
		return $this->last->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * Deletes records from a table.
	 *
	 * @param string $table The table to remove records from.
	 * @param array $condition An associative array of conditions.
	 * @param int $limit (Optional) The maximum number of deleted rows.
	 * @return int Number of records deleted by the query.
	 */
	public function deleteRecords($table, array $condition, $limit = null) {
		$query = 'delete from '.$table.' where';
		$data = null;
		foreach ($condition as $field => $value) {
			$query .= ' `'.$field.'` = ? and';
			$data[] = $value;
		}
		$query = substr($query, 0, strlen($query) - 4);
		$this->executeQuery($query, $data, array(0, (int)$limit));
		return $this->last->rowCount();
	}

	/**
	 * Updates records in a table.
	 *
	 * @param string $table The table to update records in.
	 * @param array $changes An associative array of changes to be made.
	 * @param array $condition An associative array of AND conditions.
	 * @return int Number of records updated by the query.
	 */
	public function updateRecords($table, array $changes, array $condition) {
		$query = 'update '.$table.' set';
		$data = null;
		foreach ($changes as $field => $value) {
			$query .= ' `'.$field.'` = ?,';
			$data[] = $value;
		}
		$query = substr($query, 0, strlen($query) - 1);
		$query .= ' where';
		foreach ($condition as $field => $value) {
			$query .= ' `'.$field.'` = ? and';
			$data[] = $value;
		}
		$query = substr($query, 0, strlen($query) - 4);
		$this->executeQuery($query, $data);
		return $this->last->rowCount();
	}

	/**
	 * Adds new records to a table.
	 *
	 * @param string $table The table that records will be added to.
	 * @param array $data Associative array of new records.
	 * @return int The ID of this record, if the database engine supports it.
	 */
	public function insertRecords($table, array $data) {
		$query = 'insert into '.$table.' (';
		$vals = '(';
		$newdata = null;
		foreach ($data as $field => $value) {
			$query .= '`'.$field.'`,';
			$vals .= '?,';
			$newdata[] = $value;
		}
		$query = substr($query, 0, -1);
		$vals = substr($vals, 0, -1);
		$query .= ') values ';
		$vals .= ')';
		$this->executeQuery($query.$vals, $newdata);
		return $this->connections[$this->activeConnection]->lastInsertId();
	}
}
