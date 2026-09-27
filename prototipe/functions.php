<?php

declare(strict_types=1);

// Prototipe KurasiAI versi prosedural (Bab 1).
// Semua draf disimpan di $_SESSION['drafts'] sebagai array asosiatif.

/**
 * Mengambil semua draf dari sesi.
 *
 * @return array<int, array{id: int, title: string, body: string, type: string, status: string}>
 */
function get_all_drafts(): array
{
    return $_SESSION['drafts'] ?? [];
}

/**
 * Menyimpan semua draf ke sesi.
 *
 * @param array<int, array{id: int, title: string, body: string, type: string, status: string}> $drafts
 */
function save_all_drafts(array $drafts): void
{
    $_SESSION['drafts'] = $drafts;
}

/**
 * AI tiruan: menghasilkan teks contoh berdasarkan topik dan jenis konten.
 * Hasilnya selalu sama untuk masukan yang sama, jadi tidak butuh API key.
 */
function fake_generate(string $topic, string $type): string
{
    if ($type === 'video_script') {
        return "ADEGAN 1 - Pembuka: sapa penonton dan sebutkan topik \"$topic\".\n"
            . "ADEGAN 2 - Isi: jelaskan tiga hal penting tentang $topic.\n"
            . 'ADEGAN 3 - Penutup: ajak penonton mencoba dan berkomentar.';
    }

    if ($type === 'caption') {
        $hashtag = preg_replace('/[^a-z0-9]+/', '', strtolower($topic));

        return "Sudah kenal $topic? Simpan postingan ini dan coba hari ini juga! #belajar #$hashtag";
    }

    return "$topic adalah topik yang menarik untuk dibahas. "
        . "Artikel ini menjelaskan pengertian $topic, manfaatnya, "
        . 'dan langkah awal untuk mulai mencobanya.';
}

/**
 * Membuat draf baru dari topik dan jenis konten, lalu menyimpannya.
 */
function add_draft(string $topic, string $type): void
{
    $drafts = get_all_drafts();
    $topic = trim($topic);
    $id = count($drafts) === 0 ? 1 : max(array_keys($drafts)) + 1;

    $drafts[$id] = [
        'id' => $id,
        'title' => ucfirst($topic),
        'body' => fake_generate($topic, $type),
        'type' => $type,
        'status' => 'draft',
    ];

    save_all_drafts($drafts);
}

/**
 * Mengganti status sebuah draf.
 */
function change_status(int $id, string $newStatus): void
{
    $drafts = get_all_drafts();

    if (!isset($drafts[$id])) {
        return;
    }

    $drafts[$id]['status'] = $newStatus;
    save_all_drafts($drafts);
}

/**
 * Tombol aksi yang ditampilkan untuk sebuah status.
 * Kunci = status tujuan, nilai = teks tombol.
 *
 * @return array<string, string>
 */
function available_actions(string $status): array
{
    if ($status === 'draft') {
        return ['in_review' => 'Kirim ke review'];
    }

    if ($status === 'in_review') {
        return ['approved' => 'Setujui', 'rejected' => 'Tolak'];
    }

    if ($status === 'rejected') {
        return ['in_review' => 'Kirim ulang'];
    }

    if ($status === 'approved') {
        return ['published' => 'Terbitkan'];
    }

    return [];
}

function status_label(string $status): string
{
    $labels = [
        'draft' => 'Draf',
        'in_review' => 'Sedang direview',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'published' => 'Terbit',
    ];

    return $labels[$status] ?? $status;
}

function type_label(string $type): string
{
    $labels = [
        'article' => 'Artikel',
        'video_script' => 'Naskah Video',
        'caption' => 'Caption',
    ];

    return $labels[$type] ?? $type;
}

/**
 * Mengamankan teks sebelum ditampilkan di HTML.
 */
function e(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
