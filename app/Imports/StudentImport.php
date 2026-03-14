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

    public int $successCount = 0;

    public int $skipCount = 0;

    /**
     * Column alias map: Indonesian header → internal key.
     * Allows the template to use Indonesian headers while the logic uses English keys.
     */
    private const COLUMN_ALIASES = [
        'nama' => 'name',
        'kelas' => 'class_name',
        'kategori' => 'category_name',
        'jenis_kelamin' => 'gender',
        'tanggal_masuk' => 'enrollment_date',
        'tempat_lahir' => 'birth_place',
        'tanggal_lahir' => 'birth_date',
        'nama_orang_tua' => 'parent_name',
        'no_telepon' => 'parent_phone',
        'no_whatsapp' => 'parent_whatsapp',
        'alamat' => 'address',
    ];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $data = $this->normalizeRow($row->toArray());
            $rowNumber = $index + 2;

            // Skip completely empty rows
            if ($this->isEmptyRow($data)) {
                continue;
            }

            $validator = Validator::make($data, [
                // === MANDATORY ===
                'name' => ['required', 'string', 'max:255'],
                'class_name' => ['required', 'string'],
                'category_name' => ['required', 'string'],
                'gender' => ['required', 'in:L,P,l,p'],
                'enrollment_date' => ['required', 'date'],
                'status' => ['required', 'string'],
                'nisn' => ['required', 'string', 'max:20'],
                // === OPTIONAL ===
                'nis' => ['nullable', 'string', 'max:20'],
                'birth_place' => ['nullable', 'string', 'max:100'],
                'birth_date' => ['nullable', 'date'],
                'parent_name' => ['nullable', 'string', 'max:255'],
                'parent_phone' => ['nullable', 'string', 'max:20'],
                'parent_whatsapp' => ['nullable', 'regex:/^628\d{7,15}$/'],
                'address' => ['nullable', 'string'],
            ], [
                'name.required' => 'Nama wajib diisi.',
                'class_name.required' => 'Kelas wajib diisi.',
                'category_name.required' => 'Kategori wajib diisi.',
                'gender.required' => 'Jenis kelamin wajib diisi (L/P).',
                'gender.in' => 'Jenis kelamin harus L atau P.',
                'enrollment_date.required' => 'Tanggal masuk wajib diisi.',
                'enrollment_date.date' => 'Format tanggal masuk tidak valid (gunakan YYYY-MM-DD).',
                'nisn.required' => 'NISN wajib diisi.',
                'status.required' => 'Status wajib diisi.',
                'birth_date.date' => 'Format tanggal lahir tidak valid (gunakan YYYY-MM-DD).',
                'parent_whatsapp.regex' => 'Format WhatsApp tidak valid (contoh: 6281234567890).',
            ]);

            // Validate NIS uniqueness only when provided
            $nis = $this->cleanValue($data['nis'] ?? null);
            if ($nis !== null && Student::query()->where('nis', $nis)->exists()) {
                $validator->errors()->add('nis', "NIS '{$nis}' sudah terdaftar.");
            }

            // Validate NISN uniqueness only when provided
            $nisn = $this->cleanValue($data['nisn'] ?? null);
            if ($nisn !== null && Student::query()->where('nisn', $nisn)->exists()) {
                $validator->errors()->add('nisn', "NISN '{$nisn}' sudah terdaftar.");
            }

            // Lookup class and category
            $className = trim((string) ($data['class_name'] ?? ''));
            $categoryName = trim((string) ($data['category_name'] ?? ''));

            $classId = $className !== ''
                ? SchoolClass::query()->whereRaw('LOWER(name) = ?', [strtolower($className)])->value('id')
                : null;
            $categoryId = $categoryName !== ''
                ? StudentCategory::query()->whereRaw('LOWER(name) = ?', [strtolower($categoryName)])->value('id')
                : null;

            if ($className !== '' && !$classId) {
                $validator->errors()->add('class_name', "Kelas '{$className}' tidak ditemukan di sistem.");
            }
            if ($categoryName !== '' && !$categoryId) {
                $validator->errors()->add('category_name', "Kategori '{$categoryName}' tidak ditemukan di sistem.");
            }

            // Normalize status
            $status = $this->normalizeStatus($data['status'] ?? '');
            if ($status === null && !empty($data['status'])) {
                $validator->errors()->add('status', "Status '{$data['status']}' tidak valid. Gunakan: Aktif, Nonaktif, Lulus, Pindah, Keluar.");
            }

            if ($validator->fails()) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'messages' => $validator->errors()->all(),
                ];
                $this->skipCount++;

                continue;
            }

            Student::query()->create([
                'name' => trim((string) $data['name']),
                'nis' => $nis,
                'nisn' => $nisn,
                'class_id' => $classId,
                'category_id' => $categoryId,
                'gender' => strtoupper((string) $data['gender']),
                'birth_place' => $this->cleanValue($data['birth_place'] ?? null),
                'birth_date' => $this->cleanValue($data['birth_date'] ?? null),
                'parent_name' => $this->cleanValue($data['parent_name'] ?? null),
                'parent_phone' => $this->cleanValue($data['parent_phone'] ?? null),
                'parent_whatsapp' => $this->cleanValue($data['parent_whatsapp'] ?? null),
                'address' => $this->cleanValue($data['address'] ?? null),
                'enrollment_date' => $data['enrollment_date'],
                'status' => $status ?? 'active',
            ]);

            $this->successCount++;
        }
    }

    /**
     * Normalize column headers: map Indonesian aliases to English keys.
     */
    private function normalizeRow(array $data): array
    {
        $normalized = [];
        foreach ($data as $key => $value) {
            $normalizedKey = self::COLUMN_ALIASES[$key] ?? $key;
            $normalized[$normalizedKey] = $value;
        }

        return $normalized;
    }

    /**
     * Map user-friendly status labels to internal values.
     */
    private function normalizeStatus(mixed $raw): ?string
    {
        $value = strtolower(trim((string) $raw));

        return match ($value) {
            'aktif', 'active' => 'active',
            'nonaktif', 'non-aktif', 'inactive' => 'inactive',
            'lulus', 'graduated' => 'graduated',
            'pindah', 'transferred' => 'transferred',
            'keluar', 'dropout' => 'dropout',
            default => null,
        };
    }

    private function cleanValue(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            // Keep '0' as-is only for truly numeric contexts; for string fields treat '' as null
            if ($value === '' || $value === null) {
                return null;
            }
        }

        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : null;
    }

    private function isEmptyRow(array $data): bool
    {
        foreach ($data as $value) {
            if ($value !== null && $value !== '' && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
