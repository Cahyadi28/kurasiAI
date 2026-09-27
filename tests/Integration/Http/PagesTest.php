<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Integration\Http;

class PagesTest extends HttpTestCase
{
    public function testUnknownPagesGive404(): void
    {
        $this->loginAs('rina@kurasiai.test');

        $this->assertSame(404, $this->get('/admin')->status);
        $this->assertSame(404, $this->get('/drafts/999')->status);
        $this->assertStringContainsString('Draf #999 tidak ditemukan.', $this->get('/drafts/999')->body);
    }

    public function testHomeFiltersByStatusThroughAWhitelist(): void
    {
        $this->loginAs('rina@kurasiai.test');
        $this->post('/templates/2/generate', ['values' => ['topik' => 'hemat air']]);
        $this->post('/templates/2/generate', ['values' => ['topik' => 'hemat listrik']]);
        $this->post('/drafts/2/submit');

        $this->assertSame(2, substr_count($this->get('/')->body, 'href="/drafts/'));
        $this->assertSame(1, substr_count($this->get('/', ['status' => 'in_review'])->body, 'href="/drafts/'));
        $this->assertSame(422, $this->get('/', ['status' => 'hapus_semua'])->status);
        $this->assertSame(422, $this->get('/', ['sort' => 'password_hash'])->status);
    }

    public function testUsageLogVisibility(): void
    {
        $this->loginAs('rina@kurasiai.test');
        $this->post('/templates/2/generate', ['values' => ['topik' => 'hemat air']]);

        $writerView = $this->get('/usage')->body;
        $this->assertStringContainsString('<td>Rina</td>', $writerView);
        $this->assertStringNotContainsString('<td>Sari</td>', $writerView, 'Penulis hanya melihat miliknya.');
        $this->logout();

        $this->loginAs('budi@kurasiai.test');
        $editorView = $this->get('/usage')->body;
        $this->assertStringContainsString('<td>Sari</td>', $editorView);
        $this->assertStringContainsString('<td>Rina</td>', $editorView);
    }

    public function testTemplatesCanOnlyBeEditedByTheirCreator(): void
    {
        $this->loginAs('budi@kurasiai.test');

        $this->assertSame(403, $this->get('/templates/1/edit')->status);
        $this->assertSame(403, $this->post('/templates/1/edit', ['name' => 'Diambil alih', 'text' => 'Tulis {{x}}.', 'target_type' => 'article'])->status);
    }

    public function testNewTemplateWithValidationErrors(): void
    {
        $this->loginAs('sari@kurasiai.test');

        $invalid = $this->post('/templates/new', ['name' => '', 'text' => '', 'target_type' => 'podcast']);
        $this->assertSame(200, $invalid->status);
        $this->assertStringContainsString('Wajib diisi.', $invalid->body);
        $this->assertStringContainsString('Pilihan tidak dikenal.', $invalid->body);

        $valid = $this->post('/templates/new', ['name' => 'Naskah singkat', 'text' => 'Buat naskah video tentang {{topik}}.', 'target_type' => 'video_script']);
        $this->assertRedirect('/', $valid);
        $this->assertStringContainsString('Naskah singkat', $this->get('/')->body);
    }
}
