<?php

namespace Tests\Unit;

use App\Support\MessageText;
use PHPUnit\Framework\TestCase;

class MessageTextTest extends TestCase
{
    public function test_urls_become_links_and_the_rest_stays_text(): void
    {
        $html = MessageText::html("資料です\nhttps://docs.google.com/spreadsheets/d/1HP9lHXfZjbZTEFTcUqdMa2IRuLluPUxWFLlr8JAH3DE/edit?usp=drive_link");

        $this->assertStringContainsString('<br>', $html);
        $this->assertStringContainsString(
            'href="https://docs.google.com/spreadsheets/d/1HP9lHXfZjbZTEFTcUqdMa2IRuLluPUxWFLlr8JAH3DE/edit?usp=drive_link"',
            $html
        );
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
        $this->assertStringContainsString('資料です', $html);
    }

    public function test_markup_in_the_message_is_escaped(): void
    {
        $html = MessageText::html('<script>alert(1)</script> https://example.com/a?b=1&c=2.');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('href="https://example.com/a?b=1&amp;c=2"', $html);
        $this->assertMatchesRegularExpression('/<\/a>\.$/', $html);
    }

    public function test_trailing_japanese_and_parentheses_stay_outside_the_link(): void
    {
        $html = MessageText::html('参照（https://example.com/wiki/PHP_(lang)）。');

        $this->assertStringContainsString('href="https://example.com/wiki/PHP_(lang)"', $html);
        $this->assertStringContainsString('</a>）。', $html);
    }
}
