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
 * $Revision: 2217 $
 * $Id: CAPayrollDeductionTest.php 2217 2009-10-31 22:48:21Z ipso $
 * $Date: 2009-10-31 15:48:21 -0700 (Fri, 31 Oct 2009) $
 */
require_once('PHPUnit/Framework/TestCase.php');

class USPayrollDeductionTest2011 extends PHPUnit_Framework_TestCase {

    public $company_id = NULL;

    public function __construct() {
        global $db, $cache;

		$this->tax_table_file = dirname(__FILE__).'/USPayrollDeductionTest2011.csv';

		require_once( Environment::getBasePath().'/classes/payroll_deduction/PayrollDeduction.class.php');

		$this->company_id = PRIMARY_COMPANY_ID;

		TTDate::setTimeZone('PST');
    }

    public function setUp() {
        Debug::text('Running setUp(): ', __FILE__, __LINE__, __METHOD__,10);
        return TRUE;
    }

    public function tearDown() {
        Debug::text('Running tearDown(): ', __FILE__, __LINE__, __METHOD__,10);
        return TRUE;
    }

	public function mf($amount) {
		return Misc::MoneyFormat($amount, FALSE);
	}

	public function MatchWithinMarginOfError( $source, $destination, $error = 0) {
		//Source: 125.01
		//Destination: 125.00
		//Source: 124.99
		$high_water_mark = bcadd($destination, $error);
		$low_water_mark = bcsub($destination, $error);

		if (  $source <= $high_water_mark AND $source >= $low_water_mark ) {
			return $destination;
		}

		return $source;
	}

	//
	// January 2011
	//
	function testCSVFile() {
		$this->assertEquals( file_exists($this->tax_table_file), TRUE);

		$test_rows = Misc::parseCSV( $this->tax_table_file, TRUE );

		$total_rows = count($test_rows)+1;
		$i = 2;
		foreach( $test_rows as $row ) {
			//Debug::text('Province: '. $row['province'] .' Income: '. $row['gross_income'], __FILE__, __LINE__, __METHOD__,10);
			if ( $row['gross_income'] == '' AND isset($row['low_income']) AND $row['low_income'] != '' AND isset($row['high_income']) AND $row['high_income'] != '' ) {
				$row['gross_income'] = $row['low_income'] + ( ($row['high_income'] - $row['low_income']) / 2 );
			}
			if ( $row['country'] != '' AND $row['gross_income'] != '' ) {
				//echo $i.'/'.$total_rows.'. Testing Province: '. $row['province'] .' Income: '. $row['gross_income'] ."\n";

				$pd_obj = new PayrollDeduction( $row['country'], $row['province'] );
				$pd_obj->setDate( strtotime( $row['date'] ) );
				$pd_obj->setAnnualPayPeriods( $row['pay_periods'] );

				$pd_obj->setFederalFilingStatus( $row['filing_status'] );
				$pd_obj->setFederalAllowance( $row['allowance'] );

				$pd_obj->setStateFilingStatus( $row['filing_status'] );
				$pd_obj->setStateAllowance( $row['allowance'] );

				//Some states use other values for allowance/deductions.
				switch ($row['province']) {
					case 'GA':
						Debug::text('Setting UserValue3: '. $row['allowance'], __FILE__, __LINE__, __METHOD__,10);
						$pd_obj->setUserValue3( $row['allowance'] );
						break;
					case 'IN':
					case 'VA':
						Debug::text('Setting UserValue1: '. $row['allowance'], __FILE__, __LINE__, __METHOD__,10);
						$pd_obj->setUserValue1( $row['allowance'] );
						break;
				}

				$pd_obj->setFederalTaxExempt( FALSE );
				$pd_obj->setProvincialTaxExempt( FALSE );

				$pd_obj->setGrossPayPeriodIncome( $this->mf( $row['gross_income'] ) );

				//var_dump($pd_obj->getArray());

				$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), $this->mf( $row['gross_income'] ) );
				if ( $row['federal_deduction'] != '' ) {
					$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), $this->MatchWithinMarginOfError( $this->mf( $row['federal_deduction'] ), $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), 0.01 ) );
				}
				if ( $row['provincial_deduction'] != '' ) {
					$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), $this->mf( $row['provincial_deduction'] ) );
				}
			}

			$i++;
		}

		//Make sure all rows are tested.
		$this->assertEquals( $total_rows, $i-1);
	}

	//
	// US Social Security
	//
	function testUS_2011a_SocialSecurity() {
		Debug::text('US - SemiMonthly - Beginning of 2011 01-Jan-2011: ', __FILE__, __LINE__, __METHOD__,10);

		$pd_obj = new PayrollDeduction('US','MO');
		$pd_obj->setDate(strtotime('01-Jan-2011'));
		$pd_obj->setAnnualPayPeriods( 24 ); //Semi-Monthly

		$pd_obj->setFederalFilingStatus( 10 ); //Single
		$pd_obj->setFederalAllowance( 0 );

		$pd_obj->setYearToDateSocialSecurityContribution( 0 );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setGrossPayPeriodIncome( 1000.00 );

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeSocialSecurity() ), '42.00' );
	}

	function testUS_2011a_SocialSecurity_Max() {
		Debug::text('US - SemiMonthly - Beginning of 2011 01-Jan-2011: ', __FILE__, __LINE__, __METHOD__,10);

		$pd_obj = new PayrollDeduction('US','MO');
		$pd_obj->setDate(strtotime('01-Jan-2011'));
		$pd_obj->setAnnualPayPeriods( 24 ); //Semi-Monthly

		$pd_obj->setFederalFilingStatus( 10 ); //Single
		$pd_obj->setFederalAllowance( 0 );

		$pd_obj->setYearToDateSocialSecurityContribution( 4484.60 ); //4485.60

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setGrossPayPeriodIncome( 1000.00 );

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeSocialSecurity() ), '1.00' );
	}

	function testUS_2011a_Medicare() {
		Debug::text('US - SemiMonthly - Beginning of 2011 01-Jan-2011: ', __FILE__, __LINE__, __METHOD__,10);

		$pd_obj = new PayrollDeduction('US','MO');
		$pd_obj->setDate(strtotime('01-Jan-2011'));
		$pd_obj->setAnnualPayPeriods( 24 ); //Semi-Monthly

		$pd_obj->setFederalFilingStatus( 10 ); //Single
		$pd_obj->setFederalAllowance( 0 );

		$pd_obj->setYearToDateSocialSecurityContribution( 0 );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setGrossPayPeriodIncome( 1000.00 );

		//var_dump($pd_obj->getArray());

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeMedicare() ), '14.50' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployerMedicare() ), '14.50' );
	}

	function testUS_2011a_FederalUI_NoState() {
		Debug::text('US - SemiMonthly - Beginning of 2011 01-Jan-2011: ', __FILE__, __LINE__, __METHOD__,10);

		$pd_obj = new PayrollDeduction('US','MO');
		$pd_obj->setDate(strtotime('01-Jan-2011'));
		$pd_obj->setAnnualPayPeriods( 24 ); //Semi-Monthly

		$pd_obj->setFederalFilingStatus( 10 ); //Single
		$pd_obj->setFederalAllowance( 0 );

		$pd_obj->setYearToDateSocialSecurityContribution( 0 );
		$pd_obj->setYearToDateFederalUIContribution( 0 );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setGrossPayPeriodIncome( 1000.00 );

		//var_dump($pd_obj->getArray());

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalEmployerUI() ), '62.00' );
	}

	function testUS_2011a_FederalUI_NoState_Max() {
		Debug::text('US - SemiMonthly - Beginning of 2011 01-Jan-2011: ', __FILE__, __LINE__, __METHOD__,10);

		$pd_obj = new PayrollDeduction('US','MO');
		$pd_obj->setDate(strtotime('01-Jan-2011'));
		$pd_obj->setAnnualPayPeriods( 24 ); //Semi-Monthly

		$pd_obj->setFederalFilingStatus( 10 ); //Single
		$pd_obj->setFederalAllowance( 0 );

		$pd_obj->setStateUIRate( 0 );
		$pd_obj->setStateUIWageBase( 0 );

		$pd_obj->setYearToDateSocialSecurityContribution( 0 );
		$pd_obj->setYearToDateFederalUIContribution( 433 ); //434
		$pd_obj->setYearToDateStateUIContribution( 0 );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setGrossPayPeriodIncome( 1000.00 );

		//var_dump($pd_obj->getArray());

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalEmployerUI() ), '1.00' );
	}

	function testUS_2011a_FederalUI_State_Max() {
		Debug::text('US - SemiMonthly - Beginning of 2011 01-Jan-2011: ', __FILE__, __LINE__, __METHOD__,10);

		$pd_obj = new PayrollDeduction('US','MO');
		$pd_obj->setDate(strtotime('01-Jan-2011'));
		$pd_obj->setAnnualPayPeriods( 24 ); //Semi-Monthly

		$pd_obj->setFederalFilingStatus( 10 ); //Single
		$pd_obj->setFederalAllowance( 0 );

		$pd_obj->setStateUIRate( 3.51 );
		$pd_obj->setStateUIWageBase( 11000 );

		$pd_obj->setYearToDateSocialSecurityContribution( 0 );
		$pd_obj->setYearToDateFederalUIContribution( 187.30 ); //188.30
		$pd_obj->setYearToDateStateUIContribution( 0 );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setGrossPayPeriodIncome( 1000.00 );

		//var_dump($pd_obj->getArray());

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalEmployerUI() ), '1.00' );
	}

	//
	// MD
	//
	function testMD_2011a_BiWeekly_Single_LowIncome() {
		Debug::text('US - BiWeekly - Beginning of 2011 01-Jan-2011: ', __FILE__, __LINE__, __METHOD__,10);

		$pd_obj = new PayrollDeduction('US','MD', 'ALL');
		$pd_obj->setDate(strtotime('01-Jan-2011'));
		$pd_obj->setAnnualPayPeriods( 26 ); //Bi-Weekly

		$pd_obj->setFederalFilingStatus( 10 ); //Single
		$pd_obj->setFederalAllowance( 0 );

		$pd_obj->setStateFilingStatus( 10 ); //Single
		$pd_obj->setStateAllowance( 0 );

		$pd_obj->setUserValue3( 1.25 ); //County Rate

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setGrossPayPeriodIncome( 1000.00 );

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '121.54' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '55.38' );
	}
	function testMD_2011a_BiWeekly_Married_LowIncome() {
		Debug::text('US - BiWeekly - Beginning of 2011 01-Jan-2011: ', __FILE__, __LINE__, __METHOD__,10);

		$pd_obj = new PayrollDeduction('US','MD', 'ALL');
		$pd_obj->setDate(strtotime('01-Jan-2011'));
		$pd_obj->setAnnualPayPeriods( 26 ); //Bi-Weekly

		$pd_obj->setFederalFilingStatus( 20 ); //Single
		$pd_obj->setFederalAllowance( 1 );

		$pd_obj->setStateFilingStatus( 20 ); //Single
		$pd_obj->setStateAllowance( 1 );

		$pd_obj->setUserValue3( 1.25 ); //County Rate

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setGrossPayPeriodIncome( 1000.00 );

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '55.38' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '48.00' );
	}
	function testMD_2011a_BiWeekly_Married_HighIncome() {
		Debug::text('US - BiWeekly - Beginning of 2011 01-Jan-2011: ', __FILE__, __LINE__, __METHOD__,10);

		$pd_obj = new PayrollDeduction('US','MD', 'ALL');
		$pd_obj->setDate(strtotime('01-Jan-2011'));
		$pd_obj->setAnnualPayPeriods( 26 ); //Bi-Weekly

		$pd_obj->setFederalFilingStatus( 20 ); //Single
		$pd_obj->setFederalAllowance( 2 );

		$pd_obj->setStateFilingStatus( 20 ); //Single
		$pd_obj->setStateAllowance( 2 );

		$pd_obj->setUserValue3( 1.25 ); //County Rate

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setGrossPayPeriodIncome( 5000.00 );

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '804.81' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '280.62' );
	}
	function testMD_2011b_BiWeekly_Married_HighIncome() {
		Debug::text('US - BiWeekly - Beginning of 2011 01-Jan-2011: ', __FILE__, __LINE__, __METHOD__,10);

		$pd_obj = new PayrollDeduction('US','MD', 'ALL');
		$pd_obj->setDate(strtotime('01-Jan-2011'));
		$pd_obj->setAnnualPayPeriods( 26 ); //Bi-Weekly

		$pd_obj->setFederalFilingStatus( 20 ); //Single
		$pd_obj->setFederalAllowance( 2 );

		$pd_obj->setStateFilingStatus( 20 ); //Single
		$pd_obj->setStateAllowance( 2 );

		$pd_obj->setUserValue3( 3.20 ); //County Rate

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setGrossPayPeriodIncome( 5000.00 );

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '804.81' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '371.82' );
	}
/*
	function testNY_2011_BiWeekly_MedIncome() {
		Debug::text('US - BiWeekly - Beginning of 2011 01-Jan-11: ', __FILE__, __LINE__, __METHOD__,10);

		$pd_obj = new PayrollDeduction('US','NY');
		$pd_obj->setDate(strtotime('01-Jan-11'));
		$pd_obj->setAnnualPayPeriods( 26 ); //Bi-Weekly

		$pd_obj->setFederalFilingStatus( 10 ); //Single
		$pd_obj->setFederalAllowance( 0 );

		$pd_obj->setStateAllowance( 0 );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setGrossPayPeriodIncome( 500.00 );

		//var_dump($pd_obj->getArray());

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '500.00' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '8.65' );
	}
*/
}
?>