<?php

namespace App\Shared\Infrastructure\Tools;

use Faker\Factory;
use Faker\Generator;

trait WithFaker
{
    protected function faker(): Generator
    {
        return Factory::create();
    }
}