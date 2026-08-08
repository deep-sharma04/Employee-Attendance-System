<?php

namespace App\Services\Payroll;

class SalaryCalculationService
{
    /**
     * Compute daily salary rate based on the standard 30-day salary divisor.
     * Daily Salary = Monthly Salary / 30
     */
    public function calculateDailySalary(float $monthlySalary, int $salaryDivisor = 30): float
    {
        if ($salaryDivisor <= 0) {
            $salaryDivisor = 30;
        }

        return round($monthlySalary / $salaryDivisor, 2);
    }

    /**
     * Calculate LOP salary deduction.
     * LOP Deduction = Daily Salary * Total LOP Days
     */
    public function calculateLopDeduction(float $dailySalary, float $lopDays): float
    {
        return round($dailySalary * $lopDays, 2);
    }

    /**
     * Calculate Net Salary.
     * Net Salary = Monthly Salary - LOP Deduction - Other Configured Deductions
     */
    public function calculateNetSalary(float $monthlySalary, float $lopDeduction, float $otherDeductions = 0.0): float
    {
        $net = $monthlySalary - $lopDeduction - $otherDeductions;
        return round(max(0.0, $net), 2);
    }
}
