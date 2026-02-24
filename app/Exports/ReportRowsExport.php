<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportRowsExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithStyles, WithEvents
{
    /**
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<string, string>  $columnFormats  Example: ['L' => '#,##0;[Red]-#,##0']
     * @param  array<int, int>  $highlightRows  1-based row numbers to highlight
     */
    public function __construct(
        private readonly array $rows,
        private readonly array $columnFormats = [],
        private readonly array $highlightRows = []
    ) {}

    public function collection(): Collection
    {
        return collect($this->rows);
    }

    public function columnFormats(): array
    {
        return $this->columnFormats;
    }

    public function styles(Worksheet $sheet): array
    {
        // First row is always heading row in this export helper.
        $styles = [
            1 => ['font' => ['bold' => true]],
        ];

        foreach ($this->highlightRows as $row) {
            $styles[(int) $row] = [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => 'F3F4F6'],
                ],
            ];
        }

        return $styles;
    }

    public static function accountingNumberFormat(): string
    {
        return '#,##0;[Red]-#,##0';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                if (empty($this->rows)) {
                    return;
                }

                $maxColumns = max(array_map('count', $this->rows));
                if ($maxColumns <= 0) {
                    return;
                }

                $lastColumn = Coordinate::stringFromColumnIndex($maxColumns);
                $event->sheet->freezePane('A2');
                $event->sheet->setAutoFilter("A1:{$lastColumn}1");
            },
        ];
    }
}
