<?php

declare(strict_types=1);

use KurasiAI\Ai\FakeAiProvider;
use KurasiAI\Domain\Content\ContentType;
use KurasiAI\Domain\Draft;
use KurasiAI\Domain\PromptTemplate;
use KurasiAI\Domain\Role;
use KurasiAI\Domain\User;
use KurasiAI\Exception\KurasiException;
use KurasiAI\Persistence\Database;
use KurasiAI\Service\DraftGenerator;
use KurasiAI\Service\ReviewService;
use KurasiAI\Support\Env;
use KurasiAI\Support\IdSequence;
use KurasiAI\Support\IndonesianDate;
use KurasiAI\Support\SystemClock;

require_once __DIR__ . '/../vendor/autoload.php';

// Jalankan dulu: php bin/setup-db.php && php bin/seed.php
$root = dirname(__DIR__);

try {
    $env = Env::fromFile($root . '/.env');
    $db = Database::fromEnv($env, $root);
    $clock = new SystemClock($env->timezone());
} catch (KurasiException $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}

$pdo = $db->pdo();

// 1. Membaca banyak baris.
$rows = $pdo->query('SELECT id, name, email, role FROM users ORDER BY id')->fetchAll();

if ($rows === []) {
    fwrite(STDERR, 'Belum ada akun. Jalankan dulu: php bin/seed.php' . PHP_EOL);
    exit(1);
}

echo 'Akun di database:' . PHP_EOL;

foreach ($rows as $row) {
    printf('  #%d %-5s %-20s %s' . PHP_EOL, $row['id'], $row['name'], $row['email'], Role::from($row['role'])->label());
}

// 2. Mencari satu baris dengan prepared statement, lalu mengubahnya menjadi objek User.
function findUserByEmail(PDO $pdo, string $email): ?User
{
    $statement = $pdo->prepare('SELECT id, name, email, role FROM users WHERE email = :email');
    $statement->execute(['email' => $email]);
    $row = $statement->fetch();

    if ($row === false) {
        return null;
    }

    return new User((int) $row['id'], (string) $row['name'], (string) $row['email'], Role::from((string) $row['role']));
}

echo PHP_EOL;

foreach (['rina@kurasiai.test', "' OR '1'='1"] as $email) {
    $user = findUserByEmail($pdo, $email);
    echo str_pad("Cari \"$email\"", 32) . '-> ' . ($user === null ? 'tidak ditemukan' : "{$user->name} ({$user->role->label()})") . PHP_EOL;
}

$rina = findUserByEmail($pdo, 'rina@kurasiai.test');

if ($rina === null) {
    exit(1);
}

// 3. Template dari database menjadi objek PromptTemplate.
$row = $pdo->query("SELECT name, text, target_type, created_at FROM prompt_templates WHERE target_type = 'caption' LIMIT 1")->fetch();

if ($row === false) {
    exit(1);
}

$template = new PromptTemplate(
    (string) $row['name'],
    (string) $row['text'],
    ContentType::from((string) $row['target_type']),
    new DateTimeImmutable((string) $row['created_at']),
);

// 4. Membuat draf lalu mengirimnya ke review, seperti Bab 11.
//    Sementara nomor draf diambil dari MAX(id) + 1; Bab 13 menyerahkannya ke database.
$nextId = (int) $pdo->query('SELECT COALESCE(MAX(id), 0) + 1 FROM drafts')->fetchColumn();
$generator = new DraftGenerator(new FakeAiProvider(), new IdSequence(start: $nextId), $clock);
$draft = $generator->generate($rina, $template, ['topik' => 'hemat air']);
(new ReviewService($clock))->submit($draft, $rina);

// 5. Menyimpan draf, riwayat, dan log pemakaian dalam satu transaksi.
function saveNewDraft(Database $db, Draft $draft, string $provider): void
{
    $db->transaction(function (PDO $pdo) use ($draft, $provider): void {
        $pdo->prepare(
            'INSERT INTO drafts (id, author_id, type, title, body, prompt, status,
                                 input_tokens, output_tokens, created_at, updated_at)
             VALUES (:id, :author_id, :type, :title, :body, :prompt, :status,
                     :input_tokens, :output_tokens, :created_at, :updated_at)'
        )->execute([
            'id' => $draft->id,
            'author_id' => $draft->authorId,
            'type' => $draft->content()->type()->value,
            'title' => $draft->title(),
            'body' => $draft->content()->body,
            'prompt' => $draft->prompt->text,
            'status' => $draft->status()->value,
            'input_tokens' => $draft->usage?->input,
            'output_tokens' => $draft->usage?->output,
            'created_at' => $draft->createdAt()->format(DATE_ATOM),
            'updated_at' => $draft->updatedAt()->format(DATE_ATOM),
        ]);

        $insertChange = $pdo->prepare(
            'INSERT INTO status_changes (draft_id, from_status, to_status, actor_id, note, changed_at)
             VALUES (:draft_id, :from_status, :to_status, :actor_id, :note, :changed_at)'
        );

        foreach ($draft->history() as $change) {
            $insertChange->execute([
                'draft_id' => $draft->id,
                'from_status' => $change->from->value,
                'to_status' => $change->to->value,
                'actor_id' => $change->actorId,
                'note' => $change->note,
                'changed_at' => $change->at->format(DATE_ATOM),
            ]);
        }

        if ($draft->usage !== null) {
            $pdo->prepare(
                'INSERT INTO usage_logs (user_id, draft_id, provider, input_tokens, output_tokens, created_at)
                 VALUES (:user_id, :draft_id, :provider, :input_tokens, :output_tokens, :created_at)'
            )->execute([
                'user_id' => $draft->authorId,
                'draft_id' => $draft->id,
                'provider' => $provider,
                'input_tokens' => $draft->usage->input,
                'output_tokens' => $draft->usage->output,
                'created_at' => $draft->createdAt()->format(DATE_ATOM),
            ]);
        }
    });
}

saveNewDraft($db, $draft, (new FakeAiProvider())->name());

echo PHP_EOL . "Draf #{$draft->id} \"{$draft->title()}\" tersimpan pada " . IndonesianDate::format($draft->createdAt())
    . " dengan status {$draft->status()->label()}." . PHP_EOL;

function countRows(PDO $pdo): string
{
    $counts = [];

    // Nama tabel ditulis di kode ini, bukan berasal dari input pengguna.
    foreach (['drafts' => 'draf', 'status_changes' => 'riwayat', 'usage_logs' => 'log pemakaian'] as $table => $label) {
        $counts[] = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn() . " $label";
    }

    return implode(', ', $counts);
}

echo 'Isi database: ' . countRows($pdo) . PHP_EOL;

// 6. Transaksi yang gagal di tengah jalan dibatalkan seluruhnya.
try {
    $db->transaction(function (PDO $pdo) use ($rina): void {
        $pdo->prepare(
            "INSERT INTO drafts (author_id, type, title, body, prompt, created_at, updated_at)
             VALUES (:author, 'caption', 'Draf titipan', 'Isi. #coba', 'Coba.', 'x', 'x')"
        )->execute(['author' => $rina->id]);

        // Status 'selesai' tidak ada di skema, jadi perintah ini ditolak database.
        $pdo->prepare(
            "INSERT INTO status_changes (draft_id, from_status, to_status, actor_id, changed_at)
             VALUES (:draft, 'draft', 'selesai', :actor, 'x')"
        )->execute(['draft' => $pdo->lastInsertId(), 'actor' => $rina->id]);
    });
} catch (PDOException $e) {
    echo PHP_EOL . 'Transaksi dibatalkan: ' . $e->getMessage() . PHP_EOL;
}

echo 'Isi database: ' . countRows($pdo) . ' (tidak berubah)' . PHP_EOL;
