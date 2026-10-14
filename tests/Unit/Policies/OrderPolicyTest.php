<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Orders\Models\Order;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderPolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    #[DataProvider('abilities')]
    public function policy_allows_ability_when_user_has_matching_permission(string $ability, string $permission): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo($permission);
        $order = Order::factory()->create();

        self::assertTrue(Gate::forUser($user)->allows($ability, $this->subject($ability, $order)));
    }

    #[Test]
    #[DataProvider('abilities')]
    public function policy_denies_ability_when_user_has_no_matching_permission(string $ability, string $permission): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $order = Order::factory()->create();

        self::assertFalse(Gate::forUser($user)->allows($ability, $this->subject($ability, $order)));
    }

    #[Test]
    public function gate_allows_super_admin_without_a_specific_order_permission(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole(UserRole::SuperAdmin->value);

        self::assertTrue(Gate::forUser($user)->allows('confirmPayment', Order::factory()->create()));
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
            'view any' => ['viewAny', 'orders.view'],
            'view' => ['view', 'orders.view'],
            'confirm payment' => ['confirmPayment', 'orders.payment-confirm'],
        ];
    }

    private function subject(string $ability, Order $order): string|Order
    {
        return $ability === 'viewAny' ? Order::class : $order;
    }
}
