<?php

namespace Tests\Unit\functions;

use PHPUnit\Framework\TestCase;

class IsValidEmailTest extends TestCase
{
    /**
     * Тест функции is_valid_email(). Функция возвращает true, если имейл валидный и false, если нет
     */
    public function test_is_valid_email()
    {
        $this->assertTrue( is_valid_email('kulpovvvan@ivan.gmail.com') );

        $this->assertTrue( is_valid_email('3285u_-ivanTankist@ivan.vanchick.gmail.com') );

        $this->assertTrue( is_valid_email('228.3322_213@gmail.com') );

        $this->assertTrue( is_valid_email('228-3322.213@ivan.gmail.com') );

        $this->assertFalse( is_valid_email('228-3322.21 3@ivan.gmail.com') );

        $this->assertFalse( is_valid_email('ярусский@ivan.gmail.com') );
    }
}
