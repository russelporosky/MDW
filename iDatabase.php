<?php
/**
 * Interface required for all helper database classes.
 *
 * @package MDW
 * @subpackage Database
 * @version 0.2.1
 * @license http://www.gnu.org/licenses/gpl.html
 * @copyright 2011 IndyArmy Network, Inc.
 * @author Russ Porosky <russ@indyarmy.com>
 */

interface iDatabase {
	public function newConnection($host, $user, $pass, $db);
	public function setActiveConnection($new);
	public function executeQuery($query = null, $data = null, $args = null);
	public function numRows();
	public function getRows();
	public function deleteRecords($table, array $condition, $limit = null);
	public function updateRecords($table, array $changes, array $condition);
	public function insertRecords($table, array $data);
}
