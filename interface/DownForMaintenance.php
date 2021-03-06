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
 * $Revision: 1902 $
 * $Id: DownForMaintenance.php 1902 2008-06-06 17:27:56Z ipso $
 * $Date: 2008-06-06 10:27:56 -0700 (Fri, 06 Jun 2008) $
 */
$disable_database_connection = TRUE;
require_once('../includes/global.inc.php');

$authenticate=FALSE;
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

extract	(FormVariables::GetVariables(
										array	(
												'exception',
												) ) );

$smarty->assign('title', TTi18n::gettext('Down for Maintenance'));
$smarty->assign('exception', strtolower($exception) );

$smarty->display('DownForMaintenance.tpl');
?>