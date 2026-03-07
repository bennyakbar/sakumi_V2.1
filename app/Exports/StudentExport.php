<?php

namespace App\Exports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Student::query()
            ->with(['schoolClass:id,name', 'category:id,name'])
            ->orderBy('name')
            ->get()
            ->map(function (Student $student) {
                return [
                    'name' => $student->name,
                    'nis' => $student->nis,
                    'nisn' => $student->nisn,
                    'class_name' => $student->schoolClass?->name,
                    'category_name' => $student->category?->name,
                    'gender' => $student->gender,
                    'birth_place' => $student->birth_place,
                    'birth_date' => $student->birth_date?->format('Y-m-d'),
                    'parent_name' => $student->parent_name,
                    'parent_phone' => $student->parent_phone,
                    'parent_whatsapp' => $student->parent_whatsapp,
                    'address' => $student->address,
                    'enrollment_date' => $student->enrollment_date?->format('Y-m-d'),
                    'status' => $student->status,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'name',
            'nis',
            'nisn',
            'class_name',
            'category_name',
            'gender',
            'birth_place',
            'birth_date',
            'parent_name',
            'parent_phone',
            'parent_whatsapp',
            'address',
            'enrollment_date',
            'status',
        ];
    }
}
