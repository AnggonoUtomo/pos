<?php

namespace Tests\Unit\Support;

use League\CommonMark\GithubFlavoredMarkdownConverter;
use PHPUnit\Framework\TestCase;

class MarkdownDependencyTest extends TestCase
{
    public function test_disallowed_tag_at_end_of_html_literal_is_escaped(): void
    {
        $converter = new GithubFlavoredMarkdownConverter;

        foreach (['script', 'iframe', 'style', 'textarea'] as $tag) {
            $result = (string) $converter->convert("<div>\n<{$tag}\n\n<span data-control=\"test\">");

            $this->assertStringNotContainsString("<{$tag}", $result);
            $this->assertStringContainsString("&lt;{$tag}", $result);
        }

        $this->assertStringContainsString('<strong>Normal</strong>', (string) $converter->convert('**Normal**'));
        $this->assertStringContainsString('<div>normal</div>', (string) $converter->convert('<div>normal</div>'));
    }

    public function test_table_and_bounded_pipe_free_paragraph_keep_normal_output(): void
    {
        $converter = new GithubFlavoredMarkdownConverter;

        $this->assertStringContainsString('<table>', (string) $converter->convert("Nama | Jumlah\n--- | ---\nBarang | 2"));
        $this->assertStringContainsString('<td>Barang</td>', (string) $converter->convert("Nama | Jumlah\n--- | ---\nBarang | 2"));

        $result = (string) $converter->convert(str_repeat("12345678\n", 2000));
        $this->assertSame(2000, substr_count($result, '12345678'));
        $this->assertStringNotContainsString('<table>', $result);
    }
}
