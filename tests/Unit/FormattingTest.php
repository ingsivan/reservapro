<?php

namespace Tests\Unit;

use App\Models\Schedule;
use App\Models\Service;
use App\Support\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FormattingTest extends TestCase
{
    #[Test]
    public function money_is_formatted_from_cents(): void
    {
        $this->assertSame('$0.00', Money::format(0));
        $this->assertSame('$180.50', Money::format(18050));
        $this->assertSame('$1,250.00', Money::format(125000));
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function durations(): array
    {
        return [
            'minutes only' => [45, '45 min'],
            'whole hours' => [120, '2 h'],
            'hours and minutes' => [90, '1 h 30 min'],
        ];
    }

    #[Test]
    #[DataProvider('durations')]
    public function service_durations_read_naturally(int $minutes, string $expected): void
    {
        $this->assertSame($expected, (new Service(['duration_minutes' => $minutes]))->formattedDuration());
    }

    #[Test]
    public function a_window_that_closes_before_it_opens_crosses_midnight(): void
    {
        $this->assertTrue((new Schedule(['opens_at' => '22:00:00', 'closes_at' => '02:00:00']))->crossesMidnight());
        $this->assertFalse((new Schedule(['opens_at' => '09:00:00', 'closes_at' => '18:00:00']))->crossesMidnight());
        $this->assertSame('09:00 – 18:00', (new Schedule(['opens_at' => '09:00:00', 'closes_at' => '18:00:00']))->label());
    }
}
