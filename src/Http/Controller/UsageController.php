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
        $totals = $this->app->usage()->totalsPerUser();

        if (!$user->role->canReview()) {
            $totals = array_values(array_filter($totals, fn (UsageTotal $total): bool => $total->userId === $user->id));
        }

        return $this->render('usage', ['pageTitle' => 'Log pemakaian AI', 'totals' => $totals]);
    }
}
