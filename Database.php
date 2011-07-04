<?php
/**
 * Factory Database class.
 *
 * @package MDW
 * @subpackage Database
 * @version 0.2.1
 * @license http://www.gnu.org/licenses/gpl.html
 * @copyright 2011 IndyArmy Network, Inc.
 * @author Russ Porosky <russ@indyarmy.com>
 */

require('iDatabase.php');

class Database {
	/**
	 * If the file "DB".$dbtype.".php" exists, load it up and instantiate
	 * the class.
	 *
	 * @param string $dbtype The type of database class to request.
	 * @return Database
	 */
	public static function create($dbtype) {
		$return = null;
		if (isset($dbtype)) {
			$type = 'DB'.strtolower($dbtype);
			$file = $type.'.php';
			if (file_exists($file)) {
				require($file);
				$return = new $type();
				if (!($return instanceof iDatabase)) {
					$return = null;
					throw new Exception('Database class '.$file.' is not a proper database instance.');
				}
			} else {
				throw new Exception('Database class '.$file.' does not exist.');
			}
		} else {
			throw new Exception('Database type has not been defined.');
		}
		return $return;
	}
}
