<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class ContentHelper
{
    /**
     * Parse text content that may contain pipe-delimited tables and figure references.
     * Regular text is escaped. Table syntax is converted to HTML tables.
     * Figure syntax is converted to image elements.
     *
     * Table format (first row = header):
     * | Header 1 | Header 2 | Header 3 |
     * | Data 1   | Data 2   | Data 3   |
     *
     * Figure format:
     * [figure: filename.jpg | Figure 1. Caption text]
     *
     * @param string $text
     * @param string $tableClass CSS class for tables
     * @param string $context 'web' or 'pdf' - controls image path rendering
     */
    public static function renderContent(string $text, string $tableClass = '', string $context = 'web'): string
    {
        $lines = preg_split('/\r?\n/', $text);
        $html = '';
        $inTable = false;
        $tableRows = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Check for figure syntax: [figure: filename | caption]
            // Can appear as the entire line or inline within text
            if (preg_match('/\[figure:\s*(.+?)\s*\|\s*(.+?)\s*\]/', $trimmed, $matches)) {
                // Flush any accumulated table first
                if ($inTable && !empty($tableRows)) {
                    $html .= self::buildTable($tableRows, $tableClass);
                    $tableRows = [];
                    $inTable = false;
                }

                $filename = trim($matches[1]);
                $caption = trim($matches[2]);

                // Get text before and after the figure tag
                $parts = preg_split('/\[figure:\s*.+?\s*\|\s*.+?\s*\]/', $trimmed, 2);
                $before = isset($parts[0]) ? trim($parts[0]) : '';
                $after = isset($parts[1]) ? trim($parts[1]) : '';

                if ($before !== '') {
                    $html .= '<p class="section-content">' . self::formatInline($before) . '</p>';
                }
                $html .= self::buildFigure($filename, $caption, $context);
                if ($after !== '') {
                    $html .= '<p class="section-content">' . self::formatInline($after) . '</p>';
                }
                continue;
            }

            // Check if line is a table row (starts and ends with |)
            if (preg_match('/^\|.*\|$/', $trimmed)) {
                $cells = array_map('trim', explode('|', $trimmed));
                array_shift($cells);
                array_pop($cells);

                // Skip separator rows like |---|---|---|
                if (!empty($cells) && preg_match('/^[-:\s]+$/', $cells[0])) {
                    continue;
                }

                $tableRows[] = $cells;
                $inTable = true;
            } else {
                // Flush any accumulated table
                if ($inTable && !empty($tableRows)) {
                    $html .= self::buildTable($tableRows, $tableClass);
                    $tableRows = [];
                    $inTable = false;
                }

                if ($trimmed === '') {
                    continue;
                } else {
                    $html .= '<p class="section-content">' . self::formatInline($trimmed) . '</p>';
                }
            }
        }

        // Flush remaining table
        if (!empty($tableRows)) {
            $html .= self::buildTable($tableRows, $tableClass);
        }

        return $html;
    }

    /**
     * Apply inline formatting: **bold**, *italic*, __underline__
     */
    private static function formatInline(string $text): string
    {
        $text = e($text);
        // Bold: **text**
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        // Italic: *text*
        $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
        // Underline: __text__
        $text = preg_replace('/__(.+?)__/', '<u>$1</u>', $text);
        return $text;
    }

    private static function buildFigure(string $filename, string $caption, string $context): string
    {
        // Sanitize filename
        $filename = basename($filename);

        if ($context === 'pdf') {
            // For DomPDF, use absolute file path
            $filePath = storage_path('app/public/research_images/' . $filename);
            if (!file_exists($filePath)) {
                return '<p class="section-content" style="text-align: center; font-style: italic; color: #666;">[Image not available: ' . e($caption) . ']</p>';
            }
            $src = $filePath;
        } else {
            // For web, use storage URL
            if (!Storage::disk('public')->exists('research_images/' . $filename)) {
                return '<div style="text-align: center; padding: 20px; margin: 15px 0; background: #f9fafb; border: 1px dashed #d1d5db; border-radius: 8px;"><p style="color: #9ca3af; font-style: italic;">[Image not available: ' . e($caption) . ']</p></div>';
            }
            $src = Storage::url('research_images/' . $filename);
        }

        $figureHtml = '<div class="figure-container">';
        $figureHtml .= '<img src="' . e($src) . '" alt="' . e($caption) . '" class="figure-image">';
        $figureHtml .= '<p class="figure-caption">' . e($caption) . '</p>';
        $figureHtml .= '</div>';

        return $figureHtml;
    }

    private static function buildTable(array $rows, string $tableClass): string
    {
        if (empty($rows)) return '';

        $class = $tableClass ? ' class="' . e($tableClass) . '"' : '';
        $html = '<table' . $class . '>';

        // First row = header
        $header = array_shift($rows);
        $html .= '<thead><tr>';
        foreach ($header as $cell) {
            $html .= '<th>' . self::formatInline($cell) . '</th>';
        }
        $html .= '</tr></thead>';

        if (!empty($rows)) {
            $html .= '<tbody>';
            foreach ($rows as $row) {
                $html .= '<tr>';
                foreach ($row as $cell) {
                    $html .= '<td>' . self::formatInline($cell) . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody>';
        }

        $html .= '</table>';
        return $html;
    }
}
