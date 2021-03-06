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
 * $Revision: 417 $
 * $Id: AddRecurringHoliday.php 417 2006-12-06 22:58:53Z ipso $
 * $Date: 2006-12-06 14:58:53 -0800 (Wed, 06 Dec 2006) $
 */
/*
 * Adds time to employee accruals based on calendar milestones
 * This file should run once a day.
 *
 */
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'global.inc.php');
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'CLI.inc.php');

//Debug::setVerbosity(11);

$current_epoch = TTDate::getTime();
//$current_epoch = strtotime('28-Dec-07 1:00 AM');

$offset = 86400-(3600*2); //22hrs of variance. Must be less than 24hrs which is how often this script runs.

$clf = new CompanyListFactory();
$clf->getAll();
if ( $clf->getRecordCount() > 0 ) {
	foreach ( $clf as $c_obj ) {
		if ( $c_obj->getStatus() != 30 ) {
			$aplf = new AccrualPolicyListFactory();
			$aplf->getByCompanyIdAndTypeId( $c_obj->getId(), array(20, 30) ); //Include hour based accruals so rollover adjustments can be calculated.
			if ( $aplf->getRecordCount() > 0 ) {
				foreach( $aplf as $ap_obj ) {
					$ap_obj->addAccrualPolicyTime( $current_epoch, $offset );
				}
			}
		}
	}
}
Debug::writeToLog();
Debug::Display();
?>
