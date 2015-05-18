<?php
$dbHost = 'localhost';
$dbUser = 'root';
$dbPwd = '123456';
$dbName = 'note';
const dirName = 'migrations/';
const schemaMigrationTable = "schema__migrations";
function checkDBParam($param, $paramName) {
	if (empty ( $param )) {
		die ( $paramName . ' cannot be empty,check it' );
	}
}
function main() {
	global $argc;
	global $argv;
	global $dbHost;
	global $dbUser;
	global $dbPwd;
	global $dbName;
	checkDBParam ( $dbHost, '$dbHost' );
	checkDBParam ( $dbUser, '$dbUser' );
	checkDBParam ( $dbPwd, '$dbPwd' );
	checkDBParam ( $dbName, '$dbName' );
	
	$command = $argv [1];
	$commandParam = sizeof ( $argv ) > 2 ? $argv [2] : '';
	
	switch ($command) {
		case 'g' :
		case 'generate' :
			if (0 == strlen ( $commandParam )) {
				die ( "should provide the migration name" );
			}
			@mkdir ( dirName );
			$fileName = dirName . time () . '_' . $commandParam . '.sql';
			$file = fopen ( $fileName, 'w+' );
			fclose ( $file );
			print ('generate  ' . $fileName . '  done') ;
			break;
		case 'migrate' :
			$dir = dir ( dirName );
			$migrations = array ();
			while ( $file = $dir->read () ) {
				// let . .. pass
				if (strlen ( $file ) <= 2) {
					continue;
				}
				array_push ( $migrations, $file );
			}
			sort ( $migrations );
			print_r ( $migrations );
			
			$conn = mysql_connect ( $dbHost, $dbUser, $dbPwd );
			if (! $conn) {
				die ( 'connect to db failed' );
			}
			$res = mysql_select_db ( $dbName );
			if (! $res) {
				die ( 'can not find ' . $dbName );
			}
			$res = mysql_query ( 'show tables' );
			$hasTable = false;
			while ( $row = mysql_fetch_row ( $res ) ) {
				// print_r ( $row );
				if ($row [0] == schemaMigrationTable) {
					$hasTable = true;
					break;
				}
			}
			if (! $hasTable) {
				$res = mysql_query ( 'create table ' . schemaMigrationTable . ' (version int not null,created_at datetime not null);' );
				if (! $res) {
					die ( mysql_error () );
				}
				print 'create ' . schemaMigrationTable . 'done';
			}
			$res = mysql_query ( 'select version from ' . schemaMigrationTable );
			while ( $row = mysql_fetch_row ( $res ) ) {
				$version = $row [0];
				kickout ( $migrations, $version );
			}
			foreach ( $migrations as $migrate ) {
				print 'apple migrate ' . $migrate . '\n';
				$sql = file_get_contents ( dirName . $migrate );
				if (sizeof ( $sql ) == 0) {
					print $migrate . ' is empty ,pass it ' . '\n';
					continue;
				}
				$queryRes = mysql_query ( $sql );
				if ($queryRes) {
					// add version record
					$sql = 'insert into ' . schemaMigrationTable . ' values (' . getTimeByFileName ( $migrate ) . ',now());';
					print $sql . '\n';
					mysql_query ( $sql );
				} else {
					die ( 'apply migrate ' . $migrate . 'failed,error info: ' . mysql_error () );
				}
			}
			print 'migrate finished successfully!';
			mysql_close ( $conn );
	}
}
function kickout(&$migrations, $version) {
	while ( list ( $key ) = each ( $migrations ) ) {
		if (getTimeByFileName ( $migrations [$key] ) == $version) {
			print 'unset ' . $migrations [$key] . '/n';
			unset ( $migrations [$key] );
			return;
		}
	}
}
function getTimeByFileName($fname) {
	$arr = str_split ( $fname, 10 );
	return $arr [0];
}
main ();