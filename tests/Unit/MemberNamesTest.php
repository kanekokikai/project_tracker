<?php

namespace Tests\Unit;

use App\Support\MemberNames;
use PHPUnit\Framework\TestCase;

class MemberNamesTest extends TestCase
{
    public function test_only_exact_employee_names_are_kept(): void
    {
        $picked = MemberNames::pick(
            ['堀内', '堀内さん', '金子', ' 星 ', '未登録', 12],
            ['堀内', '金子', '星'],
        );

        $this->assertSame(['堀内', '金子', '星'], $picked);
    }

    public function test_names_already_on_the_topic_stay_selectable(): void
    {
        $picked = MemberNames::pick(
            ['堀内', '旧姓'],
            ['堀内'],
            ['旧姓'],
        );

        $this->assertSame(['堀内', '旧姓'], $picked);
    }
}
