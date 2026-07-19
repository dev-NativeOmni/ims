<?php

namespace App\Services;

use ZipArchive;

class SimpleXlsxReader
{
    public static function read(string $filePath): array
    {
        if (! class_exists('ZipArchive')) {
            throw new \Exception('Ekstensi PHP ZipArchive (php-zip) belum aktif di server. Silakan hubungi administrator.');
        }

        if (! function_exists('simplexml_load_string')) {
            throw new \Exception('Ekstensi PHP SimpleXML (php-xml) belum aktif di server. Silakan hubungi administrator.');
        }

        $zip = new ZipArchive;
        if ($zip->open($filePath) !== true) {
            throw new \Exception('Gagal membaca berkas Excel (.xlsx). Berkas mungkin rusak atau tidak valid.');
        }

        // 1. Read shared strings
        $sharedStrings = [];
        $sharedStringsEntry = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsEntry !== false) {
            $xml = simplexml_load_string($sharedStringsEntry);
            if ($xml) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $r) {
                            $text .= (string) $r->t;
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Read sheet1
        $rows = [];
        $sheetEntry = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetEntry === false) {
            $zip->close();
            throw new \Exception('Lembar kerja pertama (sheet1.xml) tidak ditemukan di dalam berkas.');
        }

        $xml = simplexml_load_string($sheetEntry);
        if ($xml && $xml->sheetData) {
            foreach ($xml->sheetData->row as $rowNode) {
                $row = [];
                foreach ($rowNode->c as $cell) {
                    $ref = (string) $cell['r'];
                    $colLetter = preg_replace('/[0-9]/', '', $ref);
                    $colIndex = self::colLetterToIndex($colLetter);

                    $type = (string) $cell['t'];
                    $value = '';

                    if ($type === 's') {
                        $idx = (int) $cell->v;
                        $value = $sharedStrings[$idx] ?? '';
                    } elseif ($type === 'inlineStr') {
                        $value = (string) $cell->is->t;
                    } else {
                        $value = isset($cell->v) ? (string) $cell->v : '';
                    }

                    $row[$colIndex] = $value;
                }

                if (! empty($row)) {
                    $maxIndex = max(array_keys($row));
                    for ($i = 0; $i <= $maxIndex; $i++) {
                        if (! isset($row[$i])) {
                            $row[$i] = '';
                        }
                    }
                    ksort($row);
                    $rows[] = $row;
                }
            }
        }

        $zip->close();

        return $rows;
    }

    private static function colLetterToIndex(string $col): int
    {
        $col = strtoupper($col);
        $len = strlen($col);
        $index = 0;
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($col[$i]) - ord('A') + 1);
        }

        return $index - 1;
    }
}
