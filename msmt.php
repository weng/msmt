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
function help() {
	if (file_exists ( 'readme.md' )) {
		print file_get_contents ( 'readme.md' );
	} else {
		print 'read the manul at https://github.com/shapowang/msmt';
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
	if ($argc == 1) {
		help ();
		exit ();
	}
	$command = $argv [1];
	$commandParam = $argc > 2 ? $argv [2] : '';
	
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
			print ('generate  ' . $fileName . '  done , add your sql statement please\n') ;
			break;
		case 'migrate' :
			$dir = dir ( dirName );
			$migrations = array ();
			while ( $file = $dir->read () ) {
				// let . and .. pass
				if (strlen ( $file ) <= 2) {
					continue;
				}
				array_push ( $migrations, $file );
			}
			sort ( $migrations );
			$conn = mysql_connect ( $dbHost, $dbUser, $dbPwd );
			if (! $conn) {
				die ( 'connect to db failed,check your db configuration' );
			}
			$res = mysql_select_db ( $dbName );
			if (! $res) {
				die ( 'can not find ' . $dbName . ' ,you need create the database by hand first' );
			}
			$res = mysql_query ( 'show tables like "' . schemaMigrationTable . '"' );
			$hasTable = false;
			while ( $row = mysql_fetch_row ( $res ) ) {
				if ($row [0] == schemaMigrationTable) {
					$hasTable = true;
					break;
				}
			}
			if (! $hasTable) {
				$res = mysql_query ( 'create table ' . schemaMigrationTable . ' (version int not null,created_at datetime not null);' );
				if (! $res) {
					die ( ' fail to create the ' . schemaMigrationTable . ' table,' . mysql_error () );
				}
				print 'create ' . schemaMigrationTable . ' done\n';
			}
			$res = mysql_query ( 'select version from ' . schemaMigrationTable );
			while ( $row = mysql_fetch_row ( $res ) ) {
				$version = $row [0];
				kickout ( $migrations, $version );
			}
			foreach ( $migrations as $migrate ) {
				print 'apply migrate ' . $migrate . '\n';
				$sql_arr = file ( dirName . $migrate );
				if (count ( $sql_arr ) == 0) {
					print $migrate . ' is empty ,pass it ' . '\n';
					continue;
				}
				foreach ( $sql_arr as $sql ) {
					$queryRes = mysql_query ( $sql );
					if (! $queryRes) {
						die ( 'apply migrate ' . $migrate . ' failed, error sql statement .' . $sql . ',error info: ' . mysql_error () );
					}
				}
				if ($queryRes) {
					print 'apply migrate ' . $migrate . ' success \n';
					// add version record
					$sql_version = 'insert into ' . schemaMigrationTable . ' values (' . getTimeByFileName ( $migrate ) . ',now());';
					print $sql_version . '\n';
					$queryRes = mysql_query ( $sql_version );
					if (! $queryRes) {
						die ( 'fail to insert the migration version,error:' . mysql_error () );
					}
				}
			}
			print 'migrate finished successfully!';
			mysql_close ( $conn );
			break;
		default :
			print 'unsupport command \n';
			help ();
			exit ();
	}
}
function kickout(&$migrations, $version) {
	while ( list ( $key ) = each ( $migrations ) ) {
		if (getTimeByFileName ( $migrations [$key] ) == $version) {
			print 'unset ' . $migrations [$key] . '\n';
			unset ( $migrations [$key] );
			return;
		}
	}
}
function getTimeByFileName($fname) {
	$arr = str_split ( $fname, strlen ( time () . '' ) );
	return $arr [0];
}
main ();