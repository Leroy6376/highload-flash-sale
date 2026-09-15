<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Domain\Catalog\Models\Event;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EventPolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    #[DataProvider('abilities')]
    public function policy_allows_ability_when_user_has_matching_permission(string $ability, string $permission): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo($permission);
        $event = Event::factory()->create();

        self::assertTrue(Gate::forUser($user)->allows($ability, $this->subject($ability, $event)));
    }

    #[Test]
    #[DataProvider('abilities')]
    public function policy_denies_ability_when_user_has_no_matching_permission(string $ability, string $permission): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $event = Event::factory()->create();

        self::assertFalse(Gate::forUser($user)->allows($ability, $this->subject($ability, $event)));
    }

    #[Test]
    public function gate_allows_super_admin_without_a_specific_catalog_permission(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole(UserRole::SuperAdmin->value);
        $event = Event::factory()->create();

        self::assertTrue(Gate::forUser($user)->allows('delete', $event));
    }

    /**
     * @return array<string, array{
     *     0: string,
     *     1: string,
     * }>
     */
    public static function abilities(): array
    {
        return [
            'view any' => ['viewAny', 'catalog.events.view'],
            'view' => ['view', 'catalog.events.view'],
            'create' => ['create', 'catalog.events.create'],
            'update' => ['update', 'catalog.events.update'],
            'delete' => ['delete', 'catalog.events.delete'],
        ];
    }

    private function subject(string $ability, Event $event): string|Event
    {
        return in_array($ability, ['viewAny', 'create'], true) ? Event::class : $event;
    }
}
