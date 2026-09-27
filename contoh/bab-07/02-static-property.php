<?php

declare(strict_types=1);

// CONTOH YANG SEBAIKNYA DIHINDARI: static property yang bisa berubah.
class DraftCounter
{
    public static int $count = 0;

    public function __construct()
    {
        self::$count++;
    }
}

new DraftCounter();
new DraftCounter();
echo DraftCounter::$count . PHP_EOL; // 2

// Siapa pun, dari mana pun, bisa mengubahnya:
DraftCounter::$count = 100;

new DraftCounter();
echo DraftCounter::$count . PHP_EOL; // 101, bukan 3
