<?php
namespace App\Imports;

use App\Employeedetail;
use App\EmployeeExpense;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Validation\ValidationException;

class EmployeeTravelImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        $newEmployees = [];

        foreach ($rows as $row) {
            // Check if the employee exists
            $employee = Employeedetail::where('name', $row['employee_name'])->first();

            if (!$employee) {
                // Add the new employee name to the error list
                $newEmployees[] = $row['employee_name'];
            } else {
                // Proceed with existing employee's data
                $total_kms = 2 * $employee->distance_from_office * $row['days_present'];

                EmployeeExpense::create([
                    'employeedetail_id' => $employee->id,
                    'days_present' => $row['days_present'],
                    'distance_from_office' => $employee->distance_from_office,
                    'total_kms' => $total_kms,
                    'month' => \Carbon\Carbon::createFromFormat('M-Y', $row['month'])->format('Y-m'),
                ]);
            }
        }

        // If there are new employee names in the Excel file, throw a validation error
        if (!empty($newEmployees)) {
            $errorMessage = 'The following employees do not exist. Please add them first: ' . implode(', ', $newEmployees);
            throw ValidationException::withMessages(['employee_name' => $errorMessage]);
        }

    }
}
