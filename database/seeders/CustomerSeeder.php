<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use Illuminate\Database\Seeder;

final class CustomerSeeder extends Seeder
{
    public const string ActiveCartEmail = 'cart-active@example.test';

    public const string ExpiredCartEmail = 'cart-expired@example.test';

    public const string PendingOrderEmail = 'order-pending@example.test';

    public const string PaidOrderEmail = 'order-paid@example.test';

    public const string CancelledOrderEmail = 'order-cancelled@example.test';

    public const string ExpiredOrderEmail = 'order-expired@example.test';

    public function run(): void
    {
        foreach ($this->customers() as $email => $name) {
            $user = User::query()->updateOrCreate(['email' => $email], [
                'email_verified_at' => now(),
                'name' => $name,
                'password' => 'password',
            ]);
            $user->syncRoles([UserRole::Customer->value]);
        }
    }

    /** @return array<string, string> */
    private function customers(): array
    {
        return [
            self::ActiveCartEmail => 'Покупатель с активной корзиной',
            self::ExpiredCartEmail => 'Покупатель с истёкшей корзиной',
            self::PendingOrderEmail => 'Покупатель с ожидающей оплатой',
            self::PaidOrderEmail => 'Покупатель с оплаченным заказом',
            self::CancelledOrderEmail => 'Покупатель с отменённым заказом',
            self::ExpiredOrderEmail => 'Покупатель с истёкшим заказом',
        ];
    }
}
