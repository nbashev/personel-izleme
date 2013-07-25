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
class ImportDepartment extends Import {

	public $class_name = 'APIDepartment';

	function _getFactoryOptions( $name, $parent = NULL ) {

		$retval = NULL;
		switch( $name ) {
			case 'columns':
				$bf = TTNew('DepartmentFactory');
				$retval = $bf->getOptions('columns');
				break;
			case 'import_options':
				$retval = array(
								'-1010-fuzzy_match' => TTi18n::getText('Enable smart matching.'),
								);
				break;
			case 'parse_hint':
				$retval = array();
				break;
		}

		return $retval;
	}


	function _preParseRow( $row_number, $raw_row ) {
		$retval = $this->getObject()->getDepartmentDefaultData();
		$retval['manual_id'] += $row_number; //Auto increment manual_id automatically.

		return $retval;
	}

	function _import( $validate_only ) {
		return $this->getObject()->setDepartment( $this->getParsedData(), $validate_only );
	}

	//
	// Generic parser functions.
	//
	function parse_status_id( $input, $default_value = NULL, $parse_hint = NULL, $raw_row = NULL ) {
		if ( strtolower( $input ) == 'e'
				OR strtolower( $input ) == 'enabled' ) {
			$retval = 10;
		} elseif ( strtolower( $input ) == 'd'
				OR strtolower( $input ) == 'disabled' ) {
			$retval = 20;
		} else {
			$retval = (int)$input;
		}

		return $retval;
	}

}
?>
