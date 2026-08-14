<?php

namespace Tests\Unit;

use App\Services\Payroll\SalaryCalculationService;
use PHPUnit\Framework\TestCase;

class PayrollCalculationTest extends TestCase
{
    protected SalaryCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SalaryCalculationService();
    }

    public function test_daily_salary_calculation_with_standard_30_divisor(): void
    {
        // Monthly Salary: 30,000 -> Daily: 1,000.00
        $this->assertEquals(1000.00, $this->service->calculateDailySalary(30000.00, 30));

        // Monthly Salary: 60,000 -> Daily: 2,000.00
        $this->assertEquals(2000.00, $this->service->calculateDailySalary(60000.00, 30));

        // Monthly Salary: 45,500 -> Daily: 1,516.67
        $this->assertEquals(1516.67, $this->service->calculateDailySalary(45500.00, 30));

        // Non-positive divisor falls back to 30
        $this->assertEquals(1000.00, $this->service->calculateDailySalary(30000.00, 0));
    }

    public function test_lop_deduction_calculation(): void
    {
        $daily = 1000.00;

        // 0 LOP days -> 0 deduction
        $this->assertEquals(0.00, $this->service->calculateLopDeduction($daily, 0.0));

        // 2 LOP days -> 2,000 deduction
        $this->assertEquals(2000.00, $this->service->calculateLopDeduction($daily, 2.0));

        // 0.5 half-day LOP -> 500 deduction
        $this->assertEquals(500.00, $this->service->calculateLopDeduction($daily, 0.5));

        // 3.5 days -> 3,500 deduction
        $this->assertEquals(3500.00, $this->service->calculateLopDeduction($daily, 3.5));
    }

    public function test_net_salary_calculation(): void
    {
        // 30,000 salary - 2,000 LOP = 28,000
        $this->assertEquals(28000.00, $this->service->calculateNetSalary(30000.00, 2000.00, 0.00));

        // 30,000 salary - 2,000 LOP - 200 Prof Tax = 27,800
        $this->assertEquals(27800.00, $this->service->calculateNetSalary(30000.00, 2000.00, 200.00));

        // Excess deductions do not produce negative salary (floor is 0)
        $this->assertEquals(0.00, $this->service->calculateNetSalary(30000.00, 35000.00, 0.00));
    }
}
