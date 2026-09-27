<?php

declare(strict_types=1);

namespace KurasiAI\Http\Controller;

use KurasiAI\Http\Request;
use KurasiAI\Http\Response;
use KurasiAI\Persistence\UsageTotal;

final class UsageController extends Controller
{
    /**
     * Log pemakaian AI: Editor melihat semua pengguna, Penulis hanya dirinya sendiri.
     *
     * @param array<string, int> $params
     */
    public function index(Request $request, array $params): Response
    {
        $user = $this->requireUser();
        // Yang ditampilkan: pemakaian provider yang sedang dipakai (AI_PROVIDER), supaya
        // token FakeAiProvider dari masa belajar tidak ikut dihitung biayanya.
        $provider = $this->app->aiProvider()->name();
        $totals = $this->app->usage()->totalsPerUser($provider);

        if (!$user->role->canReview()) {
            $totals = array_values(array_filter($totals, fn (UsageTotal $total): bool => $total->userId === $user->id));
        }

        return $this->render('usage', [
            'pageTitle' => 'Log pemakaian AI',
            'totals' => $totals,
            'provider' => $provider,
            // Mesin tiruan tidak berbayar, jadi perkiraan biaya hanya untuk provider sungguhan.
            'costs' => $provider === 'fake' ? null : $this->app->costEstimator(),
        ]);
    }
}
