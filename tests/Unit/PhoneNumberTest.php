<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    /**
     * @return array<string, array{?string, ?string}>
     */
    public static function numbers(): array
    {
        return [
            'ten digits with spaces' => ['55 1234 5678', '+525512345678'],
            'with punctuation' => ['(55) 1234-5678', '+525512345678'],
            'with country code' => ['+52 55 1234 5678', '+525512345678'],
            'country code without plus' => ['52 55 1234 5678', '+525512345678'],
            'international with 00' => ['0034 612 345 678', '+34612345678'],
            'other country with plus' => ['+1 (415) 555-0100', '+14155550100'],
            'too short' => ['1234 5678', null],
            'empty' => ['', null],
            'missing' => [null, null],
        ];
    }

    #[Test]
    #[DataProvider('numbers')]
    public function it_writes_numbers_in_international_format(?string $phone, ?string $expected): void
    {
        $this->assertSame($expected, PhoneNumber::toE164($phone));
    }

    #[Test]
    public function it_builds_a_whatsapp_chat_link(): void
    {
        $this->assertSame('https://wa.me/525512345678', PhoneNumber::whatsappUrl('55 1234 5678'));
        $this->assertNull(PhoneNumber::whatsappUrl('123'));
    }
}
