<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\WhatsappPhone;
use Tests\TestCase;

final class WhatsappPhoneTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.whatsapp.default_country_code' => '52']);
    }

    public function test_normalizes_ten_local_digits(): void
    {
        $this->assertSame('5216121942057', WhatsappPhone::normalize('6121942057'));
    }

    public function test_normalizes_mexican_mobile_with_leading_one(): void
    {
        $this->assertSame('5216121942057', WhatsappPhone::normalize('16121942057'));
    }

    public function test_normalizes_full_international_digits(): void
    {
        $this->assertSame('5216121942057', WhatsappPhone::normalize('5216121942057'));
        $this->assertSame('5216121942057', WhatsappPhone::normalize('526121942057'));
    }

    public function test_local_mexico_digits_strips_prefixes(): void
    {
        $this->assertSame('6121942057', WhatsappPhone::localMexicoDigits('16121942057'));
        $this->assertSame('6121942057', WhatsappPhone::localMexicoDigits('5216121942057'));
        $this->assertSame('6121942057', WhatsappPhone::localMexicoDigits('6121942057'));
    }
}
