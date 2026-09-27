<?php

declare(strict_types=1);

enum TrafficLight
{
    case Red;
    case Yellow;
    case Green;
}

$light = TrafficLight::Red;

var_dump($light === TrafficLight::Red);   // bool(true)
var_dump($light === TrafficLight::Green); // bool(false)

$light = TrafficLight::Blue;              // Error: case Blue tidak ada
