<?php

namespace App\Services;

use ZipArchive;

class SimpleXlsxWriter
{
    public static function write(string $filePath, array $headers, array $data): void
    {
        $zip = new ZipArchive;
        if ($zip->open($filePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Gagal membuat berkas Excel (.xlsx).');
        }

        // 1. [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // 2. _rels/.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';
        $zip->addFromString('_rels/.rels', $rels);

        // 3. xl/workbook.xml
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<sheets>
<sheet name="Daftar Santri" sheetId="1" r:id="rId1"/>
</sheets>
</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbook);

        // 4. xl/_rels/workbook.xml.rels
        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);

        // 5. xl/worksheets/sheet1.xml
        $sheetData = '';
        $rowNum = 1;

        // Write headers
        $sheetData .= '<row r="1">';
        $colIndex = 0;
        foreach ($headers as $header) {
            $colLetter = self::indexToColLetter($colIndex);
            $ref = $colLetter.$rowNum;
            $val = htmlspecialchars($header, ENT_QUOTES, 'UTF-8');
            $sheetData .= '<c r="'.$ref.'" t="inlineStr"><is><t>'.$val.'</t></is></c>';
            $colIndex++;
        }
        $sheetData .= '</row>';

        // Write rows
        foreach ($data as $rowData) {
            $rowNum++;
            $sheetData .= '<row r="'.$rowNum.'">';
            $colIndex = 0;
            foreach ($rowData as $val) {
                $colLetter = self::indexToColLetter($colIndex);
                $ref = $colLetter.$rowNum;
                $valStr = htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8');
                $sheetData .= '<c r="'.$ref.'" t="inlineStr"><is><t>'.$valStr.'</t></is></c>';
                $colIndex++;
            }
            $sheetData .= '</row>';
        }

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<sheetData>'.$sheetData.'</sheetData>
</worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);

        $zip->close();
    }

    private static function indexToColLetter(int $index): string
    {
        $letter = '';
        while ($index >= 0) {
            $letter = chr($index % 26 + ord('A')).$letter;
            $index = intval($index / 26) - 1;
        }

        return $letter;
    }
}
