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
 * $Revision: 5519 $
 * $Id: PremiumPolicyList.php 5519 2011-11-15 19:28:49Z ipso $
 * $Date: 2011-11-15 11:28:49 -0800 (Tue, 15 Nov 2011) $
 */
require_once('../../includes/global.inc.php');
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

if ( !$permission->Check('premium_policy','enabled')
		OR !( $permission->Check('premium_policy','view') OR $permission->Check('premium_policy','view_own') ) ) {

	$permission->Redirect( FALSE ); //Redirect

}

$smarty->assign('title', TTi18n::gettext($title = 'Premium Policy List')); // See index.php

/*
 * Get FORM variables
 */
extract	(FormVariables::GetVariables(
										array	(
												'action',
												'page',
												'sort_column',
												'sort_order',
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

		Redirect::Page( URLBuilder::getURL( NULL, 'EditPremiumPolicy.php', FALSE) );

		break;
	case 'delete':
	case 'undelete':
		if ( strtolower($action) == 'delete' ) {
			$delete = TRUE;
		} else {
			$delete = FALSE;
		}

		$pplf = TTnew( 'PremiumPolicyListFactory' );

		foreach ($ids as $id) {
			$pplf->getByIdAndCompanyId($id, $current_company->getId() );

			foreach ($pplf as $pp_obj) {
				$pp_obj->setDeleted($delete);
				if ( $pp_obj->isValid() ) {
					$pp_obj->Save();
				}
			}
		}

		Redirect::Page( URLBuilder::getURL( NULL, 'PremiumPolicyList.php') );

		break;

	default:
		BreadCrumb::setCrumb($title);
		$pplf = TTnew( 'PremiumPolicyListFactory' );
		$pplf->getByCompanyId( $current_company->getId() );

		$pager = new Pager($pplf);

		$type_options = $pplf->getOptions('type');

 		$show_no_policy_group_notice = FALSE;
		foreach ($pplf as $pp_obj) {
			if ( (int)$pp_obj->getColumn('assigned_policy_groups') == 0 ) {
				$show_no_policy_group_notice = TRUE;
			}

			$policies[] = array(
								'id' => $pp_obj->getId(),
								'name' => $pp_obj->getName(),
								'type_id' => $pp_obj->getType(),
								'type' => $type_options[$pp_obj->getType()],
								//'trigger_time' => $pp_obj->getTriggerTime(),
								'assigned_policy_groups' => (int)$pp_obj->getColumn('assigned_policy_groups'),
								'deleted' => $pp_obj->getDeleted()
							);

		}
		$smarty->assign_by_ref('policies', $policies);

		$smarty->assign_by_ref('show_no_policy_group_notice', $show_no_policy_group_notice );

		$smarty->assign_by_ref('sort_column', $sort_column );
		$smarty->assign_by_ref('sort_order', $sort_order );

		$smarty->assign_by_ref('paging_data', $pager->getPageVariables() );

		break;
}
$smarty->display('policy/PremiumPolicyList.tpl');
?>