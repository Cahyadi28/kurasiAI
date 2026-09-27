<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Integration\Http;

class ReviewFlowTest extends HttpTestCase
{
    public function testFullReviewFlowThroughTheWeb(): void
    {
        $this->loginAs('rina@kurasiai.test');
        $this->assertRedirect('/drafts/1', $this->post('/templates/2/generate', ['values' => ['topik' => 'hemat air']]));
        $this->assertRedirect('/drafts/1', $this->post('/drafts/1/submit'));
        $this->logout();

        $this->loginAs('budi@kurasiai.test');
        $this->assertStringContainsString('/drafts/1', $this->get('/review')->body, 'Draf masuk antrean Budi.');
        $this->assertRedirect('/drafts/1', $this->post('/drafts/1/approve'));
        $this->logout();

        $this->loginAs('rina@kurasiai.test');
        $this->assertRedirect('/drafts/1', $this->post('/drafts/1/publish'));

        $page = $this->get('/drafts/1');
        $this->assertSame('published', $this->draftStatus(1));
        $this->assertStringContainsString('Draf #1 diterbitkan.', $page->body);
        $this->assertSame(3, substr_count($page->body, '<li>'), 'Tiga baris riwayat.');
    }

    public function testButtonsFollowStatusAndRole(): void
    {
        $this->createSubmittedDraft();

        $this->loginAs('budi@kurasiai.test');
        $editorPage = $this->get('/drafts/1')->body;
        $this->assertStringContainsString('>Setujui<', $editorPage);
        $this->assertStringContainsString('>Tolak<', $editorPage);
        $this->logout();

        $this->loginAs('rina@kurasiai.test');
        $ownerPage = $this->get('/drafts/1')->body;
        $this->assertStringNotContainsString('>Setujui<', $ownerPage);
        $this->assertStringNotContainsString('>Kirim ke review<', $ownerPage, 'Sudah dikirim.');
    }

    public function testRejectWithoutNoteKeepsTheStatusAndShowsTheMessage(): void
    {
        $this->createSubmittedDraft();
        $this->loginAs('budi@kurasiai.test');

        $this->assertRedirect('/drafts/1', $this->post('/drafts/1/reject', ['note' => '  ']));

        $this->assertSame('in_review', $this->draftStatus(1));
        $this->assertStringContainsString('Catatan penolakan wajib diisi.', $this->get('/drafts/1')->body);
    }

    public function testRulesAreCheckedOnTheServerEvenWithoutButtons(): void
    {
        $this->createSubmittedDraft();
        $this->loginAs('sari@kurasiai.test');

        $this->assertSame(403, $this->post('/drafts/1/approve')->status, 'Penulis tidak boleh menyetujui.');
        $this->assertSame(403, $this->get('/review')->status, 'Penulis tidak punya antrean review.');
        $this->assertSame('in_review', $this->draftStatus(1));
    }

    public function testImpossibleTransitionGives409(): void
    {
        $this->createSubmittedDraft();
        $this->loginAs('rina@kurasiai.test');

        $this->assertSame(409, $this->post('/drafts/1/publish')->status, 'Belum disetujui.');
    }

    private function createSubmittedDraft(): void
    {
        $this->loginAs('rina@kurasiai.test');
        $this->post('/templates/2/generate', ['values' => ['topik' => 'hemat air']]);
        $this->post('/drafts/1/submit');
        $this->logout();
    }
}
