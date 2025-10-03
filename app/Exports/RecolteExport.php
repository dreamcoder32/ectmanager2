<?php

namespace App\Exports;

use App\Models\Recolte;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class RecolteExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents, WithCustomStartCell
{
    protected Recolte $recolte;
    protected int $dataCount;
    protected int $headerRow = 3;
    protected int $dataStartRow;

    public function __construct(Recolte $recolte)
    {
        $this->recolte = $recolte->load(['collections.parcel', 'createdBy']);
        $this->dataCount = $this->recolte->collections->count();
        $this->dataStartRow = $this->headerRow + 1;
    }

    public function startCell(): string
    {
        return 'A3';
    }

    public function collection()
    {
        return $this->recolte->collections;
    }

    public function headings(): array
    {
        return [
            'Tracking',
            'Montant',
            'telephone',
            'Recolté Par',
            'Recolté le',
            'Type',
        ];
    }

    public function map($collection): array
    {
        $tracking = $collection->parcel->tracking_number ?? 'N/A';
        $amount = (int) round($collection->parcel->cod_amount ?? 0);
        $phone = $collection->parcel->recipient_phone ?? 'N/A';
        $by = $collection->createdBy ? ($collection->createdBy->display_name ?? ($collection->createdBy->name ?? 'N/A')) : 'N/A';
        $date = $collection->collected_at ? $collection->collected_at->format('Y-m-d H:i') : '';
        
        // Determine parcel type (stopdesk or home)
        $rawType = $collection->parcel_type ?? ($collection->parcel->delivery_type ?? null);
        if ($rawType) {
            if (in_array($rawType, ['home_delivery', 'home', 'homeDelivery'])) {
                $type = 'a domicile';
            } elseif (in_array($rawType, ['stopdesk', 'stop_desk'])) {
                $type = 'stopdesk';
            } else {
                $type = $rawType;
            }
        } else {
            $type = $collection->isHomeDelivery() ? 'home' : 'stopdesk';
        }

        return [
            $tracking,
            $amount,
            $phone,
            $by,
            $date,
            $type,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header info above the table
        $sheet->setCellValue('A1', 'Recolte Code:');
        $sheet->setCellValue('B1', 'RCT-'.$this->recolte->code . ' Crée par : ' . $this->recolte->createdBy->name);

        $totalCod = $this->recolte->collections->sum(function ($c) {
            return $c->parcel ? ($c->parcel->cod_amount ?? 0) : 0;
        });
        $sheet->setCellValue('A2', 'Total COD:');
        $sheet->setCellValue('B2', (int) round($totalCod).' Da');

        // Bold labels and remove borders in the header area
        $sheet->getStyle('A1:A2')->getFont()->setBold(true);
        $sheet->getStyle('A1:B2')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_NONE);

        // Style the column headings row (row 3)
        return [
            $this->headerRow => [
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['argb' => 'FFF0F0F0'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                // Hide gridlines for the header area
                $sheet->getDelegate()->setShowGridlines(false);

                // Determine data range
                $dataEndRow = $this->dataStartRow + $this->dataCount - 1;

                if ($dataEndRow >= $this->dataStartRow) {
                    $sheet->getDelegate()->getStyle('A' . $this->headerRow . ':F' . $dataEndRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['argb' => 'FFBFBFBF'],
                            ],
                            'outline' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['argb' => 'FFBFBFBF'],
                            ],
                        ],
                    ]);
                }

                // Column widths
                $sheet->getDelegate()->getColumnDimension('A')->setWidth(42); // Extra spacing for long tracking codes
                $sheet->getDelegate()->getColumnDimension('B')->setWidth(8); // Montant
                $sheet->getDelegate()->getColumnDimension('C')->setWidth(12); // Phone
                $sheet->getDelegate()->getColumnDimension('D')->setWidth(18);
                $sheet->getDelegate()->getColumnDimension('E')->setWidth(20);
                $sheet->getDelegate()->getColumnDimension('F')->setWidth(16); // Type

                // Alignments for data rows
                $sheet->getDelegate()->getStyle('A' . $this->dataStartRow . ':A' . $dataEndRow)->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => false,
                        'shrinkToFit' => false,
                    ],
                    'font' => ['size' => 11],
                ]);
                // Add a small left indent on Tracking header and data
                $sheet->getDelegate()->getStyle('A' . $this->headerRow)->getAlignment()->setIndent(1);
                $sheet->getDelegate()->getStyle('A' . $this->dataStartRow . ':A' . $dataEndRow)->getAlignment()->setIndent(1);
                $sheet->getDelegate()->getStyle('B' . $this->dataStartRow . ':B' . $dataEndRow)->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'font' => ['size' => 11],
                ]);
                // Increase left indent on Phone column header and data
                $sheet->getDelegate()->getStyle('C' . $this->headerRow)->getAlignment()->setIndent(0);
                $sheet->getDelegate()->getStyle('C' . $this->dataStartRow . ':C' . $dataEndRow)->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'font' => ['size' => 11],
                ]);
                $sheet->getDelegate()->getStyle('D' . $this->dataStartRow . ':D' . $dataEndRow)->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'font' => ['size' => 11],
                ]);
                $sheet->getDelegate()->getStyle('E' . $this->dataStartRow . ':E' . $dataEndRow)->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'font' => ['size' => 11],
                ]);
                $sheet->getDelegate()->getStyle('F' . $this->dataStartRow . ':F' . $dataEndRow)->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'font' => ['size' => 11],
                ]);

                // Number formatting for amounts: integers with trailing spaces for right padding
                if ($dataEndRow >= $this->dataStartRow) {
                    $sheet->getDelegate()->getStyle('B' . $this->dataStartRow . ':B' . $dataEndRow)
                        ->getNumberFormat()->setFormatCode('#,##0" Da"');
                }

                // Zebra striping for readability (even rows only)
                for ($r = $this->dataStartRow; $r <= $dataEndRow; $r++) {
                    if (($r - $this->dataStartRow) % 2 === 0) {
                        $sheet->getDelegate()->getStyle('A' . $r . ':F' . $r)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'color' => ['argb' => 'FFF7F7F7'],
                            ],
                        ]);
                    }
                    $sheet->getDelegate()->getRowDimension($r)->setRowHeight(18);
                }
            },
        ];
    }
}