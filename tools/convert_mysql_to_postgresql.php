<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Dominic O'Brien (dominicnobrien@gmail.com)
 * Portions of this software are Copyright (C) 2003 - 2013 TimeTrex Software Inc.
 * because Fairness is a fork of "TimeTrex Workforce Management" Software.
 *
 * Fairness is free software; you can redistribute it and/or modify it under the
 * terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation, either version 3 of the License, or (at you option )
 * any later version.
 *
 * Fairness is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along
 * with this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
  ********************************************************************************/
/*
 * $Revision: 1246 $
 * $Id: set_admin_permissions.php 1246 2007-09-14 23:47:42Z ipso $
 * $Date: 2007-09-14 16:47:42 -0700 (Fri, 14 Sep 2007) $
 */
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'global.inc.php');
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'CLI.inc.php');

//
/*

*******************************************************************************
************** WARNING: THIS IS NOT FULLY TESTED OR SUPPORTED *****************
*******************************************************************************

 Proceedure to Convert MySQL to PostgreSQL:

 1. Upgrade to latest version of TimeTrex still using MySQL.

 2. Run: convert_mysql_to_postgresql.php sequence > update_sequences.sql
 3. Run: convert_mysql_to_postgresql.php truncate > delete_all_data.sql

 4. Dump MySQL database with the following command:
	mysqldump -t --skip-add-locks --compatible=postgresql --complete-insert <TimeTrex_Database_Name> > timetrex_mysql.sql

 5. Install a fresh copy of TimeTrex on PostgreSQL, make sure its the latest version of TimeTrex and it matches the version
	currently installed and running on MySQL.

 6. Run: psql <TimeTrex_Database_Name> < delete_all_data.sql
 7. Run: psql <TimeTrex_Database_Name> < timetrex_mysql.sql.
		- There will be a few errors because it will try to update non-existant *_seq tables.
			This is fine because the next step handles this.
 8. Run: psql <TimeTrex_Database_Name> < update_sequences.sql

 9. Done!

*/


if ( $argc < 2 OR in_array ($argv[1], array('--help', '-help', '-h', '-?') ) ) {
	$help_output = "Usage: convert_mysql_to_postgresql.php [data]\n";
	$help_output .= " [data] = 'sequence' or 'truncate'\n";
	echo $help_output;
} else {
	//Handle command line arguments
	$last_arg = count($argv)-1;

	if ( isset($db) AND is_object($db) AND strncmp($db->databaseType,'mysql',5) == 0) {
		echo "This script must be run on MySQL only!";
		exit;
	}

	if ( isset($argv[$last_arg]) AND $argv[$last_arg] != '' ) {
		$type = trim(strtolower($argv[$last_arg]));

		$dict = NewDataDictionary($db);
		$tables = $dict->MetaTables();

		$sequence_modifier = 1000;

		$out = NULL;
		foreach( $tables as $table ) {
			if ( strpos($table, '_seq') !== FALSE ) {
				if ( $type == 'sequence' ) {
					//echo "Found Sequence Table: ". $table ."<br>\n";
					$query = 'select id from '. $table;
					$last_sequence_value = $db->GetOne($query) + $sequence_modifier;
					echo 'ALTER SEQUENCE '. $table .' RESTART WITH '. $last_sequence_value .';'."\n";
				}
			} else {
				if ( $type == 'truncate' ) {
					echo 'TRUNCATE '. $table .';'."\n";
				}
			}
		}
	}
}

//echo "WARNING: Clear TimeTrex cache after running this.\n";

//Debug::Display();
?>
