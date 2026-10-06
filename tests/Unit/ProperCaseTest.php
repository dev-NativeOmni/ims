<?php

namespace Tests\Unit;

use App\Support\ProperCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ProperCaseTest extends TestCase
{
    #[Test]
    public function all_caps_and_all_lowercase_become_proper_case(): void
    {
        $this->assertSame('Abrori Al Muammar', ProperCase::apply('ABRORI AL MUAMMAR'));
        $this->assertSame('Al-Fatih Muhammad', ProperCase::apply('AL-FATIH  MUHAMMAD'));
        $this->assertSame("Nur'aini", ProperCase::apply("NUR'AINI"));
        $this->assertSame('M. Rizki', ProperCase::apply('M. RIZKI'));
        $this->assertSame('Kab. Sukoharjo', ProperCase::apply('KAB. SUKOHARJO'));
        $this->assertSame('Sukoharjo', ProperCase::apply('sukoharjo'));
        $this->assertSame('Kota Cirebon', ProperCase::apply('kota CIREBON'));
    }

    #[Test]
    public function mixed_case_text_is_kept_as_written(): void
    {
        $this->assertSame('Adnan Sebastian Rahman', ProperCase::apply('Adnan Sebastian Rahman'));
        $this->assertSame("Nur'Aini", ProperCase::apply("Nur'Aini"));
        $this->assertNull(ProperCase::apply(null));
    }
}
