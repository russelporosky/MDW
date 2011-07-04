# MDW - Minimum Database Wrapper for PHP 5+
v0.2.1 Build 20110704a

## Description:
MDW is a simple database class that provides a slightly easier method of using
PDO in your application. By encouraging the use of `insertRecords()`,
`updateRecords()` and `deleteRecords()` methods and providing a simple
`executeQuery()` method for SELECTs and other complex queries using prepared
statements, you should be able to more easily avoid most obvious forms of
database attack and penetrations.

MDW is not meant to be a complete ADO or DBAL package. It is simply provided as
the easiest method to secure your database against looting without forcing you
to substantially change your existing code.

In this initial release, only MySQL is supported. Adding further databases is
fairly trivial, and will be explained below.

## Requirements:
* PHP 5.0 or higher
* PDO

## Usage:
	// Load the factory class and create an instance.
	require('Database.php');
	$db = Database::create('mysql');
	
	// Create 2 connections - read-only and read-write, and store the
	// connection info in constants for later use.
	define('DBREAD', $db->newConnection('localhost', 'userRO', 'password', 'DBname'));
	define('DBWRITE', $db->newConnection('localhost', 'userRW', 'password', 'DBname'));
	
	// Read a table and echo the contents. The use of prepared statements
	// means not having to escape input.
	$db->setActiveConnection(DBREAD);
	$db->executeQuery('SELECT * FROM `settings` WHERE `settings`.`section` = ?', 'Page');
	if ($db->numRows() > 0) {
		while ($setting = $db->getRows()) {
			var_dump($setting);
		}
	}
	
	// Insert a new row into "settings" table and print the ID (if any).
	$db->setActiveConnection(DBWRITE);
	$newId = $db->insertRecords('settings', array(
		'name' => 'New Settign',
		'section' => 'Page'
	));
	$db->setActiveConnection(DBREAD); // Recommended practice after any write operation.
	var_dump($newId);
	
	// Update the inserted row to fix spelling error, and display the number
	// of rows affected. Both the "changes" and "conditions" arrays can
	// have more than one member.
	$db->setActiveConnection(DBWRITE);
	$rowsAffected = $db->updateRecords('settings', array(
		'name' => 'New Setting'
	), array(
		'id' => $newId,
		'section' => 'Page'
	));
	$db->setActiveConnection(DBREAD);
	var_dump($rowsAffected);
	
	// Delete that row for fun, but limit it to a single record (if the
	// limit parameter is left off, all records matching the condition
	// would be deleted. The "conditions" array can have more than one
	// member.
	$db->setActiveConnection(DBWRITE);
	$rowsDeleted = $db->deleteRecords('settings', array(
		'name' => 'New Setting',
		'section' => 'Page'
	), 1);
	$db->setActiveConnection(DBREAD);
	var_dump($rowsDeleted);

## License
MDW is licensed under the GPL3. Please see the "LICENSE.txt" file for details.

## Contact
The GitHub issues page and wiki will be considered the "go to" source for
technical information related to MDW.
