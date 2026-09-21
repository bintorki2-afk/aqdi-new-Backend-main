<?php

namespace App\Services\Admin;

use App\Mail\MarketingReportMail;
use App\Models\Employee;
use App\Services\Marketing\Tracking\MarketingTabReportsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use ZipArchive;

class MarketingReportExportService
{
    public function __construct(
        protected MarketingTabReportsService $reports,
    ) {}

    /**
     * @param  array{range: array{0: \Illuminate\Support\Carbon, 1: \Illuminate\Support\Carbon}|null, date_from: string|null, date_to: string|null, key: string}  $filter
     * @return array{filename: string, bytes: string, mime: string}
     */
    public function build(array $filter, string $format, string $channel = 'all'): array
    {
        $format = strtolower($format);
        if (! in_array($format, ['csv', 'xlsx', 'pdf'], true)) {
            throw new InvalidArgumentException('صيغة التصدير غير مدعومة.');
        }

        $payload = $this->reports->exportPayload($filter, $channel);
        $stamp = ($filter['date_from'] ?? 'all').'_'.($filter['date_to'] ?? 'all');

        return match ($format) {
            'csv' => [
                'filename' => "marketing-report-{$stamp}.csv",
                'bytes' => $this->toCsv($payload),
                'mime' => 'text/csv; charset=UTF-8',
            ],
            'xlsx' => [
                'filename' => "marketing-report-{$stamp}.xlsx",
                'bytes' => $this->toXlsx($payload),
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
            default => [
                'filename' => "marketing-report-{$stamp}.pdf",
                'bytes' => $this->toPdf($payload),
                'mime' => 'application/pdf',
            ],
        };
    }

    /**
     * @param  array{range: array{0: \Illuminate\Support\Carbon, 1: \Illuminate\Support\Carbon}|null, date_from: string|null, date_to: string|null, key: string}  $filter
     */
    public function emailTo(Employee $employee, array $filter, string $channel = 'all'): void
    {
        $email = trim((string) $employee->email);
        if ($email === '') {
            throw new InvalidArgumentException('لا يوجد بريد إلكتروني على حسابك لإرسال التقرير.');
        }

        $file = $this->build($filter, 'csv', $channel);
        $label = ($filter['date_from'] ?? 'كل الفترات').' → '.($filter['date_to'] ?? '');

        Mail::to($email)->send(new MarketingReportMail($file, trim($label, " →")));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function toCsv(array $payload): string
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['source', 'label_ar', 'spend', 'revenue', 'roas', 'leads', 'conversions', 'cac', 'profit']);
        foreach ($payload['channels']['rows'] as $row) {
            fputcsv($handle, [
                $row['source'],
                $row['label_ar'],
                $row['spend'],
                $row['revenue'],
                $row['roas'],
                $row['leads'],
                $row['conversions'],
                $row['cac'],
                $row['profit'],
            ]);
        }
        $total = $payload['channels']['total'];
        fputcsv($handle, ['total', 'الإجمالي', $total['spend'], $total['revenue'], $total['roas'], $total['leads'], $total['conversions'], $total['cac'], $total['profit']]);
        rewind($handle);

        return stream_get_contents($handle) ?: '';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function toPdf(array $payload): string
    {
        $title = e((string) ($payload['channels']['range_label_ar'] ?? 'تقرير القنوات'));
        $rows = '';
        foreach ($payload['channels']['rows'] as $row) {
            $rows .= '<tr>'
                .'<td>'.e((string) $row['label_ar']).'</td>'
                .'<td>'.e((string) $row['spend']).'</td>'
                .'<td>'.e((string) $row['revenue']).'</td>'
                .'<td>'.e((string) ($row['roas'] ?? '—')).'</td>'
                .'<td>'.e((string) $row['leads']).'</td>'
                .'<td>'.e((string) $row['conversions']).'</td>'
                .'<td>'.e((string) ($row['cac'] ?? '—')).'</td>'
                .'<td>'.e((string) $row['profit']).'</td>'
                .'</tr>';
        }

        $total = $payload['channels']['total'];
        $html = '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><style>'
            .'body{font-family:DejaVu Sans,sans-serif;font-size:12px;direction:rtl}'
            .'h1{font-size:16px}table{width:100%;border-collapse:collapse;margin-top:12px}'
            .'th,td{border:1px solid #ccc;padding:6px;text-align:right}th{background:#f3f3f3}'
            .'</style></head><body><h1>'.$title.'</h1><table><thead><tr>'
            .'<th>القناة</th><th>الصرف</th><th>الإيراد</th><th>ROAS</th>'
            .'<th>عملاء محتملون</th><th>تحويلات</th><th>CAC</th><th>الربح</th>'
            .'</tr></thead><tbody>'.$rows.'<tr>'
            .'<td>الإجمالي</td>'
            .'<td>'.e((string) $total['spend']).'</td>'
            .'<td>'.e((string) $total['revenue']).'</td>'
            .'<td>'.e((string) ($total['roas'] ?? '—')).'</td>'
            .'<td>'.e((string) $total['leads']).'</td>'
            .'<td>'.e((string) $total['conversions']).'</td>'
            .'<td>'.e((string) ($total['cac'] ?? '—')).'</td>'
            .'<td>'.e((string) $total['profit']).'</td>'
            .'</tr></tbody></table></body></html>';

        return Pdf::loadHTML($html)->output();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function toXlsx(array $payload): string
    {
        $rows = [['source', 'label_ar', 'spend', 'revenue', 'roas', 'leads', 'conversions', 'cac', 'profit']];
        foreach ($payload['channels']['rows'] as $row) {
            $rows[] = [
                (string) $row['source'],
                (string) $row['label_ar'],
                (string) $row['spend'],
                (string) $row['revenue'],
                (string) ($row['roas'] ?? ''),
                (string) $row['leads'],
                (string) $row['conversions'],
                (string) ($row['cac'] ?? ''),
                (string) $row['profit'],
            ];
        }
        $total = $payload['channels']['total'];
        $rows[] = ['total', 'الإجمالي', (string) $total['spend'], (string) $total['revenue'], (string) ($total['roas'] ?? ''), (string) $total['leads'], (string) $total['conversions'], (string) ($total['cac'] ?? ''), (string) $total['profit']];

        $sheet = $this->sheetXml($rows);
        $files = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="channels" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>',
            'xl/worksheets/sheet1.xml' => $sheet,
        ];

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::OVERWRITE);
        foreach ($files as $name => $xml) {
            $zip->addFromString($name, $xml);
        }
        $zip->close();
        $bytes = (string) file_get_contents($tmp);
        @unlink($tmp);

        return $bytes;
    }

    /**
     * @param  list<list<string>>  $rows
     */
    protected function sheetXml(array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        foreach ($rows as $r => $cols) {
            $xml .= '<row r="'.($r + 1).'">';
            foreach ($cols as $c => $value) {
                $cell = $this->xlsxCell($c).($r + 1);
                $xml .= '<c r="'.$cell.'" t="inlineStr"><is><t>'.htmlspecialchars((string) $value, ENT_XML1).'</t></is></c>';
            }
            $xml .= '</row>';
        }

        return $xml.'</sheetData></worksheet>';
    }

    protected function xlsxCell(int $index): string
    {
        $name = '';
        $index++;
        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)).$name;
            $index = intdiv($index, 26);
        }

        return $name;
    }
}
