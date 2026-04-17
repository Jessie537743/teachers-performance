<?php

namespace Tests\Unit\Services;

use App\Services\MarkdownRenderer;
use Tests\TestCase;

class MarkdownRendererTest extends TestCase
{
    private MarkdownRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new MarkdownRenderer();
    }

    public function test_renders_basic_markdown_to_html(): void
    {
        $html = $this->renderer->render("**hello** world");
        $this->assertStringContainsString('<strong>hello</strong>', $html);
    }

    public function test_strips_script_tags(): void
    {
        $html = $this->renderer->render("<script>alert(1)</script>\n\nsafe");
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringContainsString('safe', $html);
    }

    public function test_strips_javascript_urls(): void
    {
        $html = $this->renderer->render('[x](javascript:alert(1))');
        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_strips_inline_styles(): void
    {
        $html = $this->renderer->render('<p style="color:red">x</p>');
        $this->assertStringNotContainsString('style=', $html);
    }

    public function test_preserves_allowed_links_and_forces_noopener(): void
    {
        $html = $this->renderer->render('[click](https://example.com)');
        $this->assertStringContainsString('href="https://example.com"', $html);
        $this->assertMatchesRegularExpression('/rel="[^"]*noopener[^"]*"/', $html);
    }

    public function test_preserves_lists_and_headings(): void
    {
        $html = $this->renderer->render("## Title\n\n- a\n- b");
        $this->assertStringContainsString('<h2>', $html);
        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString('<li>a</li>', $html);
    }
}
