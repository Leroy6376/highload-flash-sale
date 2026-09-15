<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Domain\Catalog\Models\TicketType;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketTypePolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    #[DataProvider('abilities')]
    public function policy_allows_ability_when_user_has_matching_permission(string $ability, string $permission): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo($permission);
        $ticketType = TicketType::factory()->create();

        self::assertTrue(Gate::forUser($user)->allows($ability, $this->subject($ability, $ticketType)));
    }

    #[Test]
    #[DataProvider('abilities')]
    public function policy_denies_ability_when_user_has_no_matching_permission(string $ability, string $permission): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $ticketType = TicketType::factory()->create();

        self::assertFalse(Gate::forUser($user)->allows($ability, $this->subject($ability, $ticketType)));
    }

    #[Test]
    public function gate_allows_super_admin_without_a_specific_catalog_permission(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole(UserRole::SuperAdmin->value);
        $ticketType = TicketType::factory()->create();

        self::assertTrue(Gate::forUser($user)->allows('delete', $ticketType));
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
            'view any' => ['viewAny', 'catalog.ticket-types.view'],
            'view' => ['view', 'catalog.ticket-types.view'],
            'create' => ['create', 'catalog.ticket-types.create'],
            'update' => ['update', 'catalog.ticket-types.update'],
            'delete' => ['delete', 'catalog.ticket-types.delete'],
        ];
    }

    private function subject(string $ability, TicketType $ticketType): string|TicketType
    {
        return in_array($ability, ['viewAny', 'create'], true) ? TicketType::class : $ticketType;
    }
}
