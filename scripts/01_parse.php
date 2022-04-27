<?php
$page = file_get_contents('https://traffic.tainan.gov.tw/ActivitiesDetailC004110.aspx?Cond=3279a1cc-34c1-446f-9b33-e5eaf611c345');
$pos = strpos($page, '<h5 class="kf-relate_tit kf-relare_download">');
$pos = strpos($page, '<li>', $pos);
$posEnd = strpos($page, '</ol>', $pos);
$lines = explode('</li>', substr($page, $pos, $posEnd - $pos));
foreach ($lines as $line) {
    $text = trim(strip_tags($line));
    $parts = explode('"', $line);
    $code = preg_replace('/[^0-9-]/', '', $text);
    if (isset($parts[1])) {
        $path = dirname(__DIR__) . '/data/' . $code;
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
            $pdfFile = $path . '/raw.pdf';
            file_put_contents($pdfFile, file_get_contents('https://traffic.tainan.gov.tw/' . $parts[1]));
            exec("/usr/bin/pdftotext {$pdfFile} {$path}/text.txt");
            exec("/usr/bin/pdfimages -j {$pdfFile} {$path}/test");
        }
    }
}
