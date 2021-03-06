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
 * $Revision: 3387 $
 * $Id: ImportBranch.class.php 3387 2010-03-04 17:42:17Z ipso $
 * $Date: 2010-03-04 09:42:17 -0800 (Thu, 04 Mar 2010) $
 */


/**
 * @package Modules\Import
 */
class ImportUserWage extends Import {

	public $class_name = 'APIUserWage';

	public $wage_group_options = FALSE;

	function _getFactoryOptions( $name, $parent = NULL ) {

		$retval = NULL;
		switch( $name ) {
			case 'columns':
				$uwf = TTNew('UserWageFactory');
				$retval = Misc::prependArray( $this->getUserIdentificationColumns(), Misc::arrayIntersectByKey( array('wage_group','type','wage','effective_date','hourly_rate','labor_burden_percent','weekly_time','note'), Misc::trimSortPrefix( $uwf->getOptions('columns') ) ) );

				break;
			case 'column_aliases':
				//Used for converting column names after they have been parsed.
				$retval = array(
								'type' => 'type_id',
								'wage_group' => 'wage_group_id',
								);
				break;
			case 'import_options':
				$retval = array(
								'-1010-fuzzy_match' => TTi18n::getText('Enable smart matching.'),
								);
				break;
			case 'parse_hint':
			case 'parse_hint':
				$upf = TTnew('UserPreferenceFactory');

				$retval = array(
								'effective_date' => $upf->getOptions('date_format'),
								'weekly_time' => $upf->getOptions('time_unit_format'),
								);
				break;
		}

		return $retval;
	}


	function _preParseRow( $row_number, $raw_row ) {
		$retval = $this->getObject()->getUserWageDefaultData();

		return $retval;
	}

	function _postParseRow( $row_number, $raw_row ) {
		$raw_row['user_id'] = $this->getUserIdByRowData( $raw_row );
		if ( $raw_row['user_id'] == FALSE ) {
			unset($raw_row['user_id']);
		}

		//If its a salary type, make sure average weekly time is always specified and hourly rate.
		return $raw_row;
	}

	function _import( $validate_only ) {
		return $this->getObject()->setUserWage( $this->getParsedData(), $validate_only );
	}

	//
	// Generic parser functions.
	//
	function getWageGroupOptions() {
		//Get job titles
		$wglf = TTNew('WageGroupListFactory');
		$wglf->getByCompanyId( $this->company_id );
		$this->wage_group_options = (array)$wglf->getArrayByListFactory( $wglf, FALSE, TRUE );
		unset($wglf);

		return TRUE;
	}
	function parse_wage_group( $input, $default_value = NULL, $parse_hint = NULL ) {
		if ( trim($input) == '' OR trim(strtolower($input)) == 'default' ) {
			return 0; //Default Wage Group
		}

		if ( !is_array( $this->wage_group_options ) ) {
			$this->getWageGroupOptions();
		}

		$retval = $this->findClosestMatch( $input, $this->wage_group_options );
		if ( $retval === FALSE ) {
			$retval = -1; //Make sure this fails.
		}

		return $retval;
	}


	function parse_effective_date( $input, $default_value = NULL, $parse_hint = NULL, $raw_row = NULL ) {
		if ( isset($parse_hint) AND $parse_hint != '' ) {
			TTDate::setDateFormat( $parse_hint );
			return TTDate::parseDateTime( $input );
		} else {
			return TTDate::strtotime( $input );
		}
	}

	function parse_type( $input, $default_value = NULL, $parse_hint = NULL, $raw_row = NULL ) {
		$uwf = TTnew('UserWageFactory');
		$options = Misc::trimSortPrefix( $uwf->getOptions( 'type' ) );

		if ( isset($options[$input]) ) {
			$retval = $input;
		} else {
			if ( $this->getImportOptions('fuzzy_match') == TRUE ) {
				$retval = $this->findClosestMatch( $input, $options, 50 );
			} else {
				$retval = array_search( strtolower($input), array_map('strtolower', $options) );
			}
		}
		
		if ( $retval === FALSE ) {
			if ( strtolower( $input ) == 'salary' OR strtolower( $input ) == 'salaried' OR strtolower( $input ) == 's' OR strtolower( $input ) == 'annual' ) {
				$retval = 20;
			} elseif ( strtolower( $input ) == 'month' OR strtolower( $input ) == 'monthly') {
				$retval = 15;
			} elseif ( strtolower( $input ) == 'biweekly' OR strtolower( $input ) == 'bi-weekly') {
				$retval = 13;
			} elseif ( strtolower( $input ) == 'week' OR strtolower( $input ) == 'weekly') {
				$retval = 12;
			} else {
				$retval = 10;
			}
		}

		return $retval;
	}

	function parse_weekly_time( $input, $default_value = NULL, $parse_hint = NULL, $raw_row = NULL ) {
		if ( isset($parse_hint) AND $parse_hint != '' ) {
			TTDate::setTimeUnitFormat( $parse_hint );
		}

		$retval = TTDate::parseTimeUnit( $input );

		return $retval;
	}

	function parse_wage( $input, $default_value = NULL, $parse_hint = NULL, $raw_row = NULL ) {
		$val = new Validator();
		$retval = $val->stripNonFloat($input);

		return $retval;
	}
}
?>
