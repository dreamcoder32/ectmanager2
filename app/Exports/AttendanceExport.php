<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceExport implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Return data as array.
     */
    public function array(): array
    {
        $rows = [];

        foreach ($this->data as $item) {
            $user = $item['user'] ?? null;

            $rows[] = [
                'Employee Name' => $user ? $user->full_name : 'N/A',
                'Employee ID' => $user ? $user->device_user_id : 'N/A',
                'Month' => date('F Y', mktime(0, 0, 0, $item['month'], 1, $item['year'])),
                'Total Work Hours' => $item['total_work_hours'],
                'Overtime Hours' => $item['total_overtime_hours'],
                'Present Days' => $item['present_days'],
                'Absent Days' => $item['absent_days'],
                'Half Days' => $item['half_days'],
                'Late Days' => $item['late_days'],
            ];
        }

        return $rows;
    }

    /**
     * Return headings.
     */
    public function headings(): array
    {
        return [
            'Employee Name',
            'Employee ID',
            'Month',
            'Total Work Hours',
            'Overtime Hours',
            'Present Days',
            'Absent Days',
            'Half Days',
            'Late Days',
        ];
    }

    /**
     * Apply styles to worksheet.
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * Return sheet title.
     */
    public function title(): string
    {
        return 'Attendance Report';
    }
}
