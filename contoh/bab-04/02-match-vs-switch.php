<?php

declare(strict_types=1);

$status = 'approved';

// switch: perlu break di setiap case, dan hasilnya harus ditampung sendiri.
switch ($status) {
    case 'approved':
        $label = 'Disetujui';
        break;
    case 'rejected':
        $label = 'Ditolak';
        break;
    default:
        $label = 'Lainnya';
}
echo "switch: $label" . PHP_EOL;

// match: langsung menghasilkan nilai, tanpa break.
$label = match ($status) {
    'approved' => 'Disetujui',
    'rejected' => 'Ditolak',
    default => 'Lainnya',
};
echo "match : $label" . PHP_EOL;

// Perbedaan penting: switch membandingkan longgar (==), match ketat (===).
$tokenLimit = '1000'; // string, misalnya dari form

switch ($tokenLimit) {
    case 1000:
        echo 'switch: dianggap sama dengan angka 1000' . PHP_EOL;
        break;
}

echo match ($tokenLimit) {
    1000 => 'match : dianggap sama dengan angka 1000',
    default => 'match : string "1000" BUKAN angka 1000',
} . PHP_EOL;

// Tanpa default dan tidak ada yang cocok: match melempar error, tidak diam saja.
echo match ($status) {
    'draft' => 'Draf',
    'rejected' => 'Ditolak',
};
