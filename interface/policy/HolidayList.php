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
 * $Revision: 4104 $
 * $Id: HolidayList.php 4104 2011-01-04 19:04:05Z ipso $
 * $Date: 2011-01-04 11:04:05 -0800 (Tue, 04 Jan 2011) $
 */
require_once('../../includes/global.inc.php');
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

if ( !$permission->Check('holiday_policy','enabled')
		OR !( $permission->Check('holiday_policy','view') OR $permission->Check('holiday_policy','view_own') ) ) {
	$permission->Redirect( FALSE ); //Redirect
}

$smarty->assign('title', TTi18n::gettext($title = 'Holiday List')); // See index.php

/*
 * Get FORM variables
 */
extract	(FormVariables::GetVariables(
										array	(
												'action',
												'page',
												'sort_column',
												'sort_order',
												'holiday_policy_id',
												'id',
												'ids',
												) ) );

URLBuilder::setURL($_SERVER['SCRIPT_NAME'],
											array(
													'sort_column' => $sort_column,
													'sort_order' => $sort_order,
													'page' => $page
												) );

$sort_array = NULL;
if ( $sort_column != '' ) {
	$sort_array = array($sort_column => $sort_order);
}

Debug::Arr($ids,'Selected Objects', __FILE__, __LINE__, __METHOD__,10);

$action = Misc::findSubmitButton();
switch ($action) {
	case 'add':

		Redirect::Page( URLBuilder::getURL( array('holiday_policy_id' => $holiday_policy_id ), 'EditHoliday.php', FALSE) );

		break;
	case 'delete' OR 'undelete':
		if ( strtolower($action) == 'delete' ) {
			$delete = TRUE;
		} else {
			$delete = FALSE;
		}

		$hlf = TTnew( 'HolidayListFactory' );

		foreach ($ids as $id) {
			$hlf->getById($id );
			foreach ($hlf as $h_obj) {
				$h_obj->setDeleted($delete);
				if ( $h_obj->isValid() ) {
					$h_obj->Save();
				}
			}
		}

		Redirect::Page( URLBuilder::getURL( array('id' => $holiday_policy_id ), 'HolidayList.php') );

		break;

	default:
		BreadCrumb::setCrumb($title);

		$hlf = TTnew( 'HolidayListFactory' );
		$hlf->getByCompanyIdAndHolidayPolicyId( $current_company->getId(), $id );

		$pager = new Pager($hlf);
		
		if ( $hlf->getRecordCount() > 0 ) {
			foreach ($hlf as $h_obj) {

				$rows[] = array(
									'id' => $h_obj->getId(),
									'date_stamp' => $h_obj->getDateStamp(),
									'name' => $h_obj->getName(),
									'deleted' => $h_obj->getDeleted()
								);

			}
		}

		$smarty->assign_by_ref('holiday_policy_id', $id);
		$smarty->assign_by_ref('rows', $rows);

		$smarty->assign_by_ref('sort_column', $sort_column );
		$smarty->assign_by_ref('sort_order', $sort_order );

		$smarty->assign_by_ref('paging_data', $pager->getPageVariables() );

		break;
}
$smarty->display('policy/HolidayList.tpl');
?>