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
 * $Revision: 8371 $
 * $Id: PremiumPolicyDepartmentFactory.class.php 8371 2012-11-22 21:18:57Z ipso $
 * $Date: 2012-11-22 13:18:57 -0800 (Thu, 22 Nov 2012) $
 */

/**
 * @package Modules\Policy
 */
class PremiumPolicyDepartmentFactory extends Factory {
	protected $table = 'premium_policy_department';
	protected $pk_sequence_name = 'premium_policy_department_id_seq'; //PK Sequence name

	protected $department_obj = NULL;

	function getDepartmentObject() {
		if ( is_object($this->department_obj) ) {
			return $this->department_obj;
		} else {
			$lf = TTnew( 'DepartmentListFactory' );
			$lf->getById( $this->getDepartment() );
			if ( $lf->getRecordCount() == 1 ) {
				$this->department_obj = $lf->getCurrent();
				return $this->department_obj;
			}

			return FALSE;
		}
	}

	function getPremiumPolicy() {
		if ( isset($this->data['premium_policy_id']) ) {
			return $this->data['premium_policy_id'];
		}
	}
	function setPremiumPolicy($id) {
		$id = trim($id);

		$pplf = TTnew( 'PremiumPolicyListFactory' );

		if (	$id == 0
				OR
				$this->Validator->isNumeric(	'premium_policy',
													$id,
													TTi18n::gettext('Selected Premium Policy is invalid')
/*
				$this->Validator->isResultSetWithRows(	'premium_policy',
													$pplf->getByID($id),
													TTi18n::gettext('Selected Premium Policy is invalid')
*/
															)
			) {

			$this->data['premium_policy_id'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getDepartment() {
		if ( isset($this->data['department_id']) ) {
			return $this->data['department_id'];
		}

		return FALSE;
	}
	function setDepartment($id) {
		$id = trim($id);

		$dlf = TTnew( 'DepartmentListFactory' );

		if ( $this->Validator->isResultSetWithRows(	'department',
													$dlf->getByID($id),
													TTi18n::gettext('Selected Department is invalid')
													) ) {
			$this->data['department_id'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	//This table doesn't have any of these columns, so overload the functions.
	function getDeleted() {
		return FALSE;
	}
	function setDeleted($bool) {
		return FALSE;
	}

	function getCreatedDate() {
		return FALSE;
	}
	function setCreatedDate($epoch = NULL) {
		return FALSE;
	}
	function getCreatedBy() {
		return FALSE;
	}
	function setCreatedBy($id = NULL) {
		return FALSE;
	}

	function getUpdatedDate() {
		return FALSE;
	}
	function setUpdatedDate($epoch = NULL) {
		return FALSE;
	}
	function getUpdatedBy() {
		return FALSE;
	}
	function setUpdatedBy($id = NULL) {
		return FALSE;
	}

	function getDeletedDate() {
		return FALSE;
	}
	function setDeletedDate($epoch = NULL) {
		return FALSE;
	}
	function getDeletedBy() {
		return FALSE;
	}
	function setDeletedBy($id = NULL) {
		return FALSE;
	}

	function addLog( $log_action ) {
		$obj = $this->getDepartmentObject();
		if ( is_object($obj) ) {
			return TTLog::addEntry( $this->getPremiumPolicy(), $log_action,  TTi18n::getText('Department').': '. $obj->getName(), NULL, $this->getTable() );
		}
	}
}
?>
