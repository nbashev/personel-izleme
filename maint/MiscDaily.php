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
 * $Revision: 1396 $
 * $Id: CheckForUpdate.php 1396 2007-11-07 16:49:35Z ipso $
 * $Date: 2007-11-07 08:49:35 -0800 (Wed, 07 Nov 2007) $
 */
/*
 * Checks for any version updates...
 *
 */
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'global.inc.php');
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'CLI.inc.php');
//
// Backup database if script exists.
// Always backup the database first before doing anything else like purging tables.
//
if ( !isset($config_vars['other']['disable_backup'])
		OR isset($config_vars['other']['disable_backup']) AND $config_vars['other']['disable_backup'] != TRUE ) {
	if ( PHP_OS == 'WINNT' ) {
		$backup_script = dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'backup_database.bat';
	} else {
		$backup_script = dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'backup_database';
	}
	Debug::Text('Backup Database Command: '. $backup_script, __FILE__, __LINE__, __METHOD__,10);
	if ( file_exists( $backup_script ) ) {
		Debug::Text('Running Backup: '. TTDate::getDate('DATE+TIME', time() ), __FILE__, __LINE__, __METHOD__,10);
		exec( '"'. $backup_script .'"', $output, $retcode);
		Debug::Text('Backup Completed: '. TTDate::getDate('DATE+TIME', time() ) .' RetCode: '. $retcode, __FILE__, __LINE__, __METHOD__,10);

		$backup_history_files = array();

		$backup_dir = dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'..';
		if ( is_dir($backup_dir) AND is_readable( $backup_dir ) ) {
			$fh = opendir($backup_dir);
			while ( ($file = readdir($fh)) !== FALSE ) {
				# loop through the files, skipping . and .., and recursing if necessary
				if ( strcmp($file, '.') == 0 OR strcmp($file, '..' ) == 0 ) {
					continue;
				}

				$filepath = $backup_dir . DIRECTORY_SEPARATOR . $file;
				if ( !is_dir( $filepath ) ) {
					if ( preg_match( '/timetrex_database.*\.sql/i', $file) == 1 ) {
		                $backup_history_files[filemtime($filepath)] = $filepath;
					}
				}
			}
		}
		ksort($backup_history_files);

		if ( is_array( $backup_history_files ) AND count($backup_history_files) > 7 ) {
			reset($backup_history_files);
			$delete_backup_file = current($backup_history_files);
			Debug::Text('Deleting oldest backup: '. $delete_backup_file .' Of Total: '. count($backup_history_files), __FILE__, __LINE__, __METHOD__,10);
			unlink( $delete_backup_file );
			unset($delete_backup_file);
		}
	}
	unset($backup_script, $output, $retcode, $backup_dir, $fh, $file, $filepath, $backup_history_files);
}

//
// Rotate log files
//
if ( !isset($config_vars['other']['disable_log_rotate'])
		OR isset($config_vars['other']['disable_log_rotate']) AND $config_vars['other']['disable_log_rotate'] != TRUE ) {
	$log_rotate_config[] = array(
								'directory' => $config_vars['path']['log'],
								'recurse' => FALSE,
								'file' => 'timetrex.log',
								'frequency' => 'DAILY',
								'history' => 7 );

	$log_rotate_config[] = array(
								'directory' => $config_vars['path']['log'] . DIRECTORY_SEPARATOR . 'client',
								'recurse' => TRUE,
								'file' => '*',
								'frequency' => 'DAILY',
								'history' => 7 );

	$log_rotate_config[] = array(
								'directory' => $config_vars['path']['log'] . DIRECTORY_SEPARATOR . 'time_clock',
								'recurse' => TRUE,
								'file' => '*',
								'frequency' => 'DAILY',
								'history' => 7 );

	$lr = new LogRotate( $log_rotate_config );
	$lr->Rotate();
}

//
// Check cache file directories and permissions.
//
if ( !isset($config_vars['other']['disable_cache_permission_check'])
		OR isset($config_vars['other']['disable_cache_permission_check']) AND $config_vars['other']['disable_cache_permission_check'] != TRUE ) {
	if ( isset($config_vars['cache']['enable']) AND $config_vars['cache']['enable'] == TRUE AND isset($config_vars['cache']['dir']) AND $config_vars['cache']['dir'] != '' ) {
		Debug::Text('Validating Cache Files/Directory: '. $config_vars['cache']['dir'], __FILE__, __LINE__, __METHOD__,10);

		//Just as a precaution, confirm that cache directory exists, if not try to create it.
		if ( file_exists($config_vars['cache']['dir']) == FALSE ) {
			//Try to create cache directory
			Debug::Text( 'Cache directory does not exist, attempting to create it: '. $config_vars['cache']['dir'], __FILE__, __LINE__, __METHOD__,10);
			$mkdir_result = @mkdir( $config_vars['cache']['dir'], 0777, TRUE );
			if ( $mkdir_result == FALSE ) {
				Debug::Text( 'ERROR: Unable to create cache directory: '. $config_vars['cache']['dir'], __FILE__, __LINE__, __METHOD__,10);
				Misc::disableCaching();
			} else {
				Debug::Text( 'Cache directory created successfully: '. $config_vars['cache']['dir'], __FILE__, __LINE__, __METHOD__,10);
			}
			unset($mkdir_result);
		}

		//Check all cache files and make sure they are owned by the same users.
		$cache_files = Misc::getFileList( $config_vars['cache']['dir'], NULL, TRUE );
		if ( is_array($cache_files) AND count($cache_files) > 0 ) {
			foreach( $cache_files as $cache_file ) {
				$cache_file_owners[] = @fileowner($cache_file);
			}

			$cache_file_owners = array_unique($cache_file_owners);
			if ( count($cache_file_owners) > 1 ) {
				Debug::Text( 'ERROR: Cache directory contains files from several different owners. Its likely that their permission conflict.', __FILE__, __LINE__, __METHOD__,10);
				Debug::Arr( $cache_file_owners, 'Cache File Owner UIDs: ', __FILE__, __LINE__, __METHOD__,10);
				Misc::disableCaching();
			}
		}
	}
}

Debug::writeToLog();
Debug::Display();
?>