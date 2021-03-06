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
 * $Revision: 4300 $
 * $Id: RateLimit.class.php 4300 2011-02-25 19:49:45Z ipso $
 * $Date: 2011-02-25 11:49:45 -0800 (Fri, 25 Feb 2011) $
 */

/**
 * @package Core
 */

class RateLimit {
	protected $sleep = FALSE; //When rate limit is reached, do we sleep or return FALSE?


	protected $id = 1;
	protected $group = 'rate_limit';

	protected $allowed_calls = 25;
	protected $time_frame = 60; //1 minute.

	protected $memory = NULL;

	function __construct() {
		$this->memory = new SharedMemory();

		return TRUE;
	}

	function getID() {
		return $this->id;
	}
	function setID($value) {
		if ( $value != '' ) {
			$this->id = $value;

			return TRUE;
		}

		return FALSE;
	}

	//Define the number of calls to check() allowed over a given time frame.
	function getAllowedCalls() {
		return $this->allowed_calls;
	}
	function setAllowedCalls($value) {
		if ( $value != '' ) {
			$this->allowed_calls = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getTimeFrame() {
		return $this->time_frame;
	}
	function setTimeFrame($value) {
		if ( $value != '' ) {
			$this->time_frame = $value;

			return TRUE;
		}

		return FALSE;
	}

	function setRateData( $data ) {
		return $this->memory->set( $this->group.$this->getID(), $data );
	}
	function getRateData() {
		return $this->memory->get( $this->group.$this->getID() );
	}

	function getAttempts() {
		$rate_data = $this->getRateData();
		if ( isset($rate_data['attempts']) ) {
			return $rate_data['attempts'];
		}

		return FALSE;
	}

	function check() {
		if ( $this->getID() != '' ) {
			$rate_data = $this->getRateData();
			//Debug::Arr($rate_data, 'Failed Attempt Data: ', __FILE__, __LINE__, __METHOD__,10);
			if ( !isset($rate_data['attempts']) ) {
				$rate_data = array(
											'attempts' => 0,
											'first_date' => microtime(TRUE),
											 );
			} elseif ( isset($rate_data['attempts']) ) {
				if ( $rate_data['attempts'] > $this->getAllowedCalls() AND $rate_data['first_date'] >= ( microtime(TRUE)-$this->getTimeFrame() ) ) {
					return FALSE;
				} elseif ( $rate_data['first_date'] < ( microtime(TRUE)-$this->getTimeFrame() ) ) {
					$rate_data['attempts'] = 0;
					$rate_data['first_date'] = microtime(TRUE);
				}
			}

			$rate_data['attempts']++;
			return $this->setRateData( $rate_data );
		}

		return FALSE;
	}

	function delete() {
		return $this->memory->delete( $this->group.$this->getID() );
	}
}
?>