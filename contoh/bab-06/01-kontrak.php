<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

$ai = new FakeAiProvider();

var_dump($ai instanceof FakeAiProvider); // bool(true)
var_dump($ai instanceof AiProvider);     // bool(true): FakeAiProvider memenuhi kontrak AiProvider

$provider = new AiProvider();            // Error: interface tidak bisa dibuat dengan new
