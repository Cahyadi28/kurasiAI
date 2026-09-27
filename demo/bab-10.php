<?php

declare(strict_types=1);

use KurasiAI\Ai\FakeAiProvider;
use KurasiAI\Domain\Content\Caption;
use KurasiAI\Domain\Content\ContentType;
use KurasiAI\Domain\PromptTemplate;
use KurasiAI\Domain\Role;
use KurasiAI\Domain\User;
use KurasiAI\Exception\AccessDeniedException;
use KurasiAI\Exception\InvalidStateException;
use KurasiAI\Exception\InvalidTransitionException;
use KurasiAI\Exception\KurasiException;
use KurasiAI\Exception\ValidationException;
use KurasiAI\Service\DraftGenerator;
use KurasiAI\Support\IdSequence;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Menjalankan satu tindakan dan menerjemahkan setiap jenis masalah menjadi pesan untuk pengguna.
 * Urutan catch: dari yang paling khusus ke yang paling umum.
 */
function attempt(string $description, callable $action): void
{
    try {
        $action();
        echo "[OK]      $description" . PHP_EOL;
    } catch (ValidationException $e) {
        echo "[DATA]    $description -> Periksa isian: {$e->getMessage()}" . PHP_EOL;
    } catch (AccessDeniedException $e) {
        echo "[AKSES]   $description -> Tidak berhak: {$e->getMessage()}" . PHP_EOL;
    } catch (InvalidTransitionException $e) {
        echo "[STATUS]  $description -> Belum bisa dari \"{$e->from->label()}\" ke \"{$e->to->label()}\"" . PHP_EOL;
    } catch (InvalidStateException $e) {
        echo "[STATUS]  $description -> {$e->getMessage()}" . PHP_EOL;
    } catch (KurasiException $e) {
        echo "[LAINNYA] $description -> {$e->getMessage()}" . PHP_EOL;
    }
}

$generator = new DraftGenerator(new FakeAiProvider(), new IdSequence());
$rina = new User(1, 'Rina', 'rina@kurasiai.test', Role::Writer);
$sari = new User(2, 'Sari', 'sari@kurasiai.test', Role::Writer);
$budi = new User(3, 'Budi', 'budi@kurasiai.test', Role::Editor);

$template = new PromptTemplate('Caption kampanye', 'Buat caption Instagram tentang {{topik}}.', ContentType::Caption);
$draft = $generator->generate($rina, $template, ['topik' => 'hemat air']);

attempt('Rina menerbitkan tanpa review', fn () => $draft->publish($rina));
attempt('Rina mengirim ke review', fn () => $draft->submit($rina));
attempt('Rina merevisi saat direview', fn () => $draft->revise($rina, new Caption('Hemat air', 'Isi baru. #air')));
attempt('Sari menyetujui draf Rina', fn () => $draft->approve($sari));
attempt('Budi menolak tanpa catatan', fn () => $draft->reject($budi, ''));
attempt('Budi menolak dengan catatan', fn () => $draft->reject($budi, 'Tambahkan satu fakta.'));
attempt('Rina merevisi jadi terlalu panjang', fn () => $draft->revise($rina, new Caption('Hemat air', str_repeat('a', 2300))));
attempt('Membuat pengguna dengan email salah', fn () => new User(4, 'Tono', 'tono-di-mana', Role::Writer));
