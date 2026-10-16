<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;

class HttpFoundationHeaderTest extends TestCase
{
    #[DataProvider('whitespaceRuns')]
    public function test_bounded_whitespace_does_not_drop_cache_control_directives(string $whitespace): void
    {
        $before = ini_set('pcre.backtrack_limit', '10000');
        try {
            $request = Request::create('/', server: ['HTTP_CACHE_CONTROL' => 'max-age=3600'.$whitespace.', no-cache']);
            $this->assertSame('3600', $request->headers->getCacheControlDirective('max-age'));
            $this->assertTrue($request->headers->hasCacheControlDirective('no-cache'));
            $this->assertSame(PREG_NO_ERROR, preg_last_error());
        } finally {
            ini_set('pcre.backtrack_limit', $before);
        }
    }

    public static function whitespaceRuns(): array
    {
        return [[str_repeat(' ', 48)], [str_repeat("\t", 48)], [str_repeat(" \t", 24)]];
    }

    public function test_legitimate_quoted_headers_and_whitespace_separators_remain_compatible(): void
    {
        $request = Request::create('/', server: ['HTTP_CACHE_CONTROL' => 'private, max-age=60, custom="a,b"']);
        $this->assertTrue($request->headers->hasCacheControlDirective('private'));
        $this->assertSame('60', $request->headers->getCacheControlDirective('max-age'));
        $this->assertSame('a,b', $request->headers->getCacheControlDirective('custom'));
        $this->assertSame(['foo', 'bar'], HeaderUtils::split('foo bar', ' '));
        $this->assertSame(['foo', 'bar'], HeaderUtils::split("foo\tbar", "\t"));
        $this->assertSame(['foo bar'], HeaderUtils::split('foo bar', ','));
    }
}
