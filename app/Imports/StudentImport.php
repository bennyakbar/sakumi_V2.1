<?php

namespace App\Imports;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentImport implements ToCollection, WithHeadingRow
{
    use Importable;

    public array $errors = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $data = $row->toArray();
            $rowNumber = $index + 2;

            $validator = Validator::make($data, [
                'name' => ['required', 'string', 'max:255'],
                'nis' => ['required', 'string', 'max:20', 'unique:students,nis'],
                'nisn' => ['nullable', 'string', 'max:20', 'unique:students,nisn'],
                'class_name' => ['required', 'string'],
                'category_name' => ['required', 'string'],
                'gender' => ['required', 'in:L,P'],
                'birth_place' => ['nullable', 'string', 'max:100'],
                'birth_date' => ['nullable', 'date'],
                'parent_name' => ['nullable', 'string', 'max:255'],
                'parent_phone' => ['nullable', 'string', 'max:20'],
                'parent_whatsapp' => ['nullable', 'regex:/^628\d{7,15}$/'],
                'address' => ['nullable', 'string'],
                'enrollment_date' => ['nullable', 'date'],
                'status' => ['nullable', 'in:active,graduated,dropout,transferred'],
            ]);

            $classId = SchoolClass::query()
                ->whereRaw('LOWER(name) = ?', [strtolower(trim((string) ($data['class_name'] ?? '')))])
                ->value('id');
            $categoryId = StudentCategory::query()
                ->whereRaw('LOWER(name) = ?', [strtolower(trim((string) ($data['category_name'] ?? '')))])
                ->value('id');

            if (! $classId) {
                $validator->errors()->add('class_name', 'Class not found.');
            }
            if (! $categoryId) {
                $validator->errors()->add('category_name', 'Category not found.');
            }

            if ($validator->fails()) {
                $this->errors[] = 'Row '.$rowNumber.': '.implode(', ', $validator->errors()->all());

                continue;
            }

            Student::query()->create([
                'name' => $data['name'] ?? null,
                'nis' => $data['nis'] ?? null,
                'nisn' => ($data['nisn'] ?? null) ?: null,
                'class_id' => $classId,
                'category_id' => $categoryId,
                'gender' => $data['gender'] ?? null,
                'birth_place' => ($data['birth_place'] ?? null) ?: null,
                'birth_date' => ($data['birth_date'] ?? null) ?: null,
                'parent_name' => ($data['parent_name'] ?? null) ?: null,
                'parent_phone' => ($data['parent_phone'] ?? null) ?: null,
                'parent_whatsapp' => ($data['parent_whatsapp'] ?? null) ?: null,
                'address' => ($data['address'] ?? null) ?: null,
                'enrollment_date' => ($data['enrollment_date'] ?? null) ?: now()->toDateString(),
                'status' => ($data['status'] ?? null) ?: 'active',
            ]);
        }
    }
}
