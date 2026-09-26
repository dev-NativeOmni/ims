<?php

namespace Tests\Unit\Support;

use App\Support\AyahLabel;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AyahLabelTest extends TestCase
{
    #[Test]
    public function only_the_last_ayah_or_page_is_written(): void
    {
        $this->assertSame('38', AyahLabel::end('34-38'));
        $this->assertSame('38', AyahLabel::end('34 - 38'));
        $this->assertSame('7', AyahLabel::end('1-5, 7'));
        $this->assertSame('15', AyahLabel::end('Hal. 12-15'));
        $this->assertSame('38', AyahLabel::end(38));
        $this->assertSame('-', AyahLabel::end(null));
        $this->assertSame('-', AyahLabel::end(''));
    }
}
