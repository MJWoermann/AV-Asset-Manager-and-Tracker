<?php

namespace Tests\Unit;

use App\Support\MacAddress;
use Tests\TestCase;

class MacAddressTest extends TestCase
{
    public function test_compact_strips_separators_and_lowercases(): void
    {
        $this->assertSame('aabbccddeeff', MacAddress::compact('AA:BB:CC:DD:EE:FF'));
        $this->assertSame('aabbccddeeff', MacAddress::compact('aa-bb-cc-dd-ee-ff'));
        $this->assertSame('aabbccddeeff', MacAddress::compact('AABBCCDDEEFF'));
        $this->assertSame('00107fc4f959', MacAddress::compact('00.10.7f.c4.f9.59'));
        $this->assertSame('aabbccddeeff', MacAddress::compact('aa bb cc dd ee ff'));
        $this->assertNull(MacAddress::compact(''));
        $this->assertNull(MacAddress::compact('   '));
        $this->assertNull(MacAddress::compact(null));
    }

    public function test_equals_is_case_and_separator_agnostic(): void
    {
        $this->assertTrue(MacAddress::equals('AA:BB:CC:DD:EE:FF', 'aabbccddeeff'));
        $this->assertTrue(MacAddress::equals('aa-bb-cc-dd-ee-ff', 'AA:BB:CC:DD:EE:FF'));
        $this->assertTrue(MacAddress::equals('00.10.7f.c4.f9.59', '00107FC4F959'));
        $this->assertFalse(MacAddress::equals('AA:BB:CC:DD:EE:FF', 'AA:BB:CC:DD:EE:00'));
        $this->assertFalse(MacAddress::equals('AA:BB:CC:DD:EE:FF', null));
    }

    public function test_contains_matches_partial_compact_forms(): void
    {
        $this->assertTrue(MacAddress::contains('AA:BB:CC:DD:EE:FF', 'aabb'));
        $this->assertTrue(MacAddress::contains('AA:BB:CC:DD:EE:FF', 'BB-CC'));
        $this->assertFalse(MacAddress::contains('AA:BB:CC:DD:EE:FF', '1122'));
    }

    public function test_format_returns_colon_separated_uppercase(): void
    {
        $this->assertSame('AA:BB:CC:DD:EE:FF', MacAddress::format('aabbccddeeff'));
        $this->assertSame('AA:BB:CC:DD:EE:FF', MacAddress::format('aa-bb-cc-dd-ee-ff'));
        $this->assertSame('not-a-mac', MacAddress::format('not-a-mac'));
        $this->assertNull(MacAddress::format(''));
    }

    public function test_is_valid_accepts_12_and_16_hex_digits(): void
    {
        $this->assertTrue(MacAddress::isValid('AA:BB:CC:DD:EE:FF'));
        $this->assertTrue(MacAddress::isValid('aabbccddeeff0011'));
        $this->assertFalse(MacAddress::isValid('aabb'));
        $this->assertFalse(MacAddress::isValid('hello'));
    }
}
