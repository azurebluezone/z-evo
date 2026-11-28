<?php

namespace App\Http\Controllers\Admin\Concerns;

/**
 * Minimal, dependency-free XLSX writer.
 *
 * The report export used to download a CSV named like a spreadsheet. This
 * trait builds a real Office Open XML workbook with ZipArchive (the same
 * extension PHP already ships): inline strings, a bold header row, frozen
 * header and numeric cells with 0 / 0.00 formats. No PhpSpreadsheet, no
 * network call, no temp file left behind.
 *
 * Strings are always written as inlineStr, never as formulas, so a product
 * name beginning with "=" stays text.
 */
trait WritesXlsxFiles
{
    /**
     * @param array<int,string> $headers
     * @param array<int,array<int,string|int|float|null>> $rows
     */
    protected function buildXlsx(array $headers, array $rows, string $sheetName = 'Report'): string
    {
        $sheetName = $this->xlsxSheetName($sheetName);
        $sheetXml = $this->xlsxSheetXml($headers, $rows);

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';

        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';

        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $this->xlsxText($sheetName) . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';

        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';

        // xf 0 = normal, xf 1 = bold header, xf 2 = 0.00, xf 3 = 0.
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><name val="Calibri"/><family val="2"/><color theme="1"/><sz val="11"/></font>'
            . '<font><name val="Calibri"/><family val="2"/><b/><color theme="1"/><sz val="11"/></font>'
            . '</fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="4">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="2" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="1" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';

        $path = tempnam(sys_get_temp_dir(), 'zc-xlsx-');
        if ($path === false) {
            abort(500, 'Could not create a temporary file for the export.');
        }

        try {
            $zip = new \ZipArchive();
            if ($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                abort(500, 'Could not open the XLSX archive.');
            }

            $zip->addFromString('[Content_Types].xml', $contentTypes);
            $zip->addFromString('_rels/.rels', $rootRels);
            $zip->addFromString('xl/workbook.xml', $workbook);
            $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
            $zip->addFromString('xl/styles.xml', $styles);
            $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
            $zip->close();

            $bytes = file_get_contents($path);
        } finally {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        if ($bytes === false) {
            abort(500, 'Could not read the generated XLSX file.');
        }

        return $bytes;
    }

    private function xlsxSheetXml(array $headers, array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0">'
            . '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>'
            . '</sheetView></sheetViews><cols>';

        foreach (array_keys($headers) as $index) {
            $width = $index === 0 ? 8 : 18;
            $xml .= '<col min="' . ($index + 1) . '" max="' . ($index + 1) . '" width="' . $width . '" customWidth="1"/>';
        }

        $xml .= '</cols><sheetData>';
        $xml .= $this->xlsxRow($headers, 1, true);

        $rowNumber = 2;
        foreach ($rows as $row) {
            $xml .= $this->xlsxRow($row, $rowNumber, false);
            $rowNumber++;
        }

        return $xml . '</sheetData></worksheet>';
    }

    private function xlsxRow(array $cells, int $rowNumber, bool $header): string
    {
        $xml = '<row r="' . $rowNumber . '">';
        $column = 1;

        foreach ($cells as $value) {
            $ref = $this->xlsxCellRef($column, $rowNumber);

            if ($header) {
                $xml .= '<c r="' . $ref . '" t="inlineStr" s="1"><is><t>'
                    . $this->xlsxText((string) $value) . '</t></is></c>';
            } elseif (is_int($value) || is_float($value)) {
                $style = is_int($value) ? 3 : 2;
                $number = is_int($value) ? (string) $value : number_format($value, 2, '.', '');
                $xml .= '<c r="' . $ref . '" s="' . $style . '"><v>' . $number . '</v></c>';
            } else {
                $text = (string) ($value ?? '');
                if ($text === '') {
                    $xml .= '<c r="' . $ref . '"/>';
                } else {
                    $xml .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">'
                        . $this->xlsxText($text) . '</t></is></c>';
                }
            }

            $column++;
        }

        return $xml . '</row>';
    }

    private function xlsxCellRef(int $column, int $row): string
    {
        $letters = '';
        while ($column > 0) {
            $mod = ($column - 1) % 26;
            $letters = chr(65 + $mod) . $letters;
            $column = intdiv($column - 1, 26);
        }

        return $letters . $row;
    }

    private function xlsxText(string $value): string
    {
        // XML 1.0 forbids most control characters; strip them instead of
        // producing a workbook that Excel refuses to open.
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value) ?? '';

        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function xlsxSheetName(string $name): string
    {
        $name = preg_replace('/[\[\]:*?\/\\\\]/', ' ', $name) ?? 'Report';
        $name = trim($name);

        return $name === '' ? 'Report' : mb_substr($name, 0, 31);
    }
}
