<?php
// Generates a print-ready HTML from the ERD entity descriptions
$content = file_get_contents('C:/Users/HARVIE BERNESTO/.gemini/antigravity/brain/0b1ca0b1-eb35-4b02-8ffa-e17003dac442/erd_entity_descriptions.md');

// Simple markdown → HTML conversion
function md2html($md) {
    // H1
    $md = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $md);
    // H2
    $md = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $md);
    // H3
    $md = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $md);
    // Bold
    $md = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $md);
    // Inline code
    $md = preg_replace('/`([^`]+)`/', '<code>$1</code>', $md);
    // HR
    $md = preg_replace('/^---$/m', '<hr>', $md);
    // Tables
    $lines = explode("\n", $md);
    $out = []; $inTable = false;
    foreach ($lines as $line) {
        if (preg_match('/^\|/', $line)) {
            if (!$inTable) { $out[] = '<table>'; $inTable = true; $isHeader = true; }
            else { $isHeader = false; }
            if (preg_match('/^\|[-| ]+\|$/', $line)) continue; // separator row
            $cells = array_slice(explode('|', $line), 1, -1);
            $tag = $isHeader ? 'th' : 'td';
            $row = '<tr>';
            foreach ($cells as $c) $row .= "<$tag>" . trim($c) . "</$tag>";
            $row .= '</tr>';
            $out[] = $row;
        } else {
            if ($inTable) { $out[] = '</table>'; $inTable = false; }
            $out[] = $line;
        }
    }
    if ($inTable) $out[] = '</table>';
    $md = implode("\n", $out);
    // Paragraphs (blank-line separated non-tag lines)
    $md = preg_replace('/\n{2,}(?!<)/', "</p>\n<p>", $md);
    return $md;
}

$body = md2html($content);

$html = <<<H
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>GSpot Gaming Hub — ERD Entity Descriptions</title>
<style>
  @page { size: A4; margin: 18mm 15mm; }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 11px;
         color: #222; background: #fff; padding: 20px 24px; }
  h1 { font-size: 18px; color: #1a3a6a; border-bottom: 2px solid #4A8FC4;
       padding-bottom: 6px; margin: 20px 0 12px; page-break-before: avoid; }
  h2 { font-size: 14px; color: #fff; background: #4A8FC4;
       padding: 6px 10px; margin: 22px 0 8px; border-radius: 4px;
       page-break-after: avoid; }
  h3 { font-size: 12px; color: #333; margin: 12px 0 4px; }
  p { margin: 6px 0; line-height: 1.55; }
  strong { font-weight: 700; }
  code { font-family: Consolas, monospace; font-size: 10px;
         background: #f0f4f8; padding: 1px 4px; border-radius: 3px; color: #c0392b; }
  hr { border: none; border-top: 1px solid #ddd; margin: 16px 0; }
  table { width: 100%; border-collapse: collapse; margin: 8px 0 14px;
          font-size: 10.5px; page-break-inside: avoid; }
  th { background: #4A8FC4; color: #fff; padding: 5px 8px;
       text-align: left; font-weight: 600; }
  td { padding: 4px 8px; border-bottom: 1px solid #e0e0e0; vertical-align: top; }
  tr:nth-child(even) td { background: #f5f8fc; }
  .cover { text-align: center; padding: 40px 0 30px; border-bottom: 3px solid #4A8FC4;
           margin-bottom: 24px; page-break-after: avoid; }
  .cover h1 { font-size: 22px; border: none; color: #1a3a6a; }
  .cover p  { font-size: 12px; color: #666; margin-top: 6px; }
  @media print {
    h2 { page-break-before: always; }
    h2:first-of-type { page-break-before: avoid; }
    table { page-break-inside: avoid; }
  }
</style>
</head>
<body>
<div class="cover">
  <h1>GSpot Gaming Hub</h1>
  <p>Entity Relationship Diagram &mdash; Entity Descriptions</p>
  <p style="margin-top:4px;color:#888">Generated: <?= date('F j, Y') ?> | Total Entities: 12</p>
</div>
$body
</body>
</html>
H;

$outPath = __DIR__ . '/erd_descriptions.html';
file_put_contents($outPath, $html);
echo "Written: $outPath\n";
