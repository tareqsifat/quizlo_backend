<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Passport\Passport;
use App\Models\User;
use App\Models\ExamType;

abstract class TestCase extends BaseTestCase
{
    protected function actingAsUser(?User $user = null): static
    {
        $user ??= User::factory()->create();
        Passport::actingAs($user, ['user']);
        return $this;
    }

    protected function actingAsAdmin(): static
    {
        $admin = User::factory()->admin()->create();
        Passport::actingAs($admin, ['admin']);
        return $this;
    }

    protected function bcsExamType(): ExamType
    {
        return ExamType::where('code', 'BCS')->first()
            ?? ExamType::factory()->create(['code' => 'BCS']);
    }
}
