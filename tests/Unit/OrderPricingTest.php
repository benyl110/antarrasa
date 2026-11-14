<?php

namespace Tests\Unit;

use App\Services\OrderWorkflow;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OrderPricingTest extends TestCase
{
    #[DataProvider('deliveryTariffs')]
    public function test_delivery_tariff_is_fixed_by_verified_road_distance(float $distance, int $expectedFee): void
    {
        $this->assertSame($expectedFee, OrderWorkflow::deliveryFee($distance));
    }

    public static function deliveryTariffs(): array
    {
        return [
            'at two kilometers' => [2.0, 5000],
            'just above two kilometers' => [2.01, 8000],
            'at five kilometers' => [5.0, 8000],
        ];
    }
}
