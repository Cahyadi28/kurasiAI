<?php

declare(strict_types=1);

namespace KurasiAI\Http;

use KurasiAI\App;
use KurasiAI\Http\Controller\AuthController;
use KurasiAI\Http\Controller\DraftController;
use KurasiAI\Http\Controller\HomeController;
use KurasiAI\Http\Controller\ReviewController;
use KurasiAI\Http\Controller\TemplateController;
use KurasiAI\Http\Controller\UsageController;

/**
 * Seluruh alamat KurasiAI dalam satu layar.
 * $objek->method(...) adalah first-class callable syntax (PHP 8.1): method itu sendiri,
 * belum dipanggil, dikirim ke router sebagai "yang harus dijalankan nanti".
 */
final class Routes
{
    public static function register(Router $router, App $app): void
    {
        $home = new HomeController($app);
        $auth = new AuthController($app);
        $templates = new TemplateController($app);
        $drafts = new DraftController($app);
        $review = new ReviewController($app);
        $usage = new UsageController($app);

        $router->get('/', $home->index(...));

        $router->get('/login', $auth->showLogin(...));
        $router->post('/login', $auth->login(...));
        $router->post('/logout', $auth->logout(...));

        $router->get('/templates/new', $templates->create(...));
        $router->post('/templates/new', $templates->store(...));
        $router->get('/templates/{id}/edit', $templates->edit(...));
        $router->post('/templates/{id}/edit', $templates->update(...));

        $router->get('/templates/{id}/generate', $drafts->generateForm(...));
        $router->post('/templates/{id}/generate', $drafts->generate(...));
        $router->get('/drafts/{id}', $drafts->show(...));
        $router->post('/drafts/{id}', $drafts->revise(...));

        $router->post('/drafts/{id}/submit', $review->submit(...));
        $router->post('/drafts/{id}/approve', $review->approve(...));
        $router->post('/drafts/{id}/reject', $review->reject(...));
        $router->post('/drafts/{id}/publish', $review->publish(...));
        $router->get('/review', $review->queue(...));

        $router->get('/usage', $usage->index(...));
    }
}
