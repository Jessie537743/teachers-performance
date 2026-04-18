<?php

namespace App\Services;

use League\CommonMark\CommonMarkConverter;
use Mews\Purifier\Facades\Purifier;

class MarkdownRenderer
{
    private CommonMarkConverter $converter;

    public function __construct()
    {
        $this->converter = new CommonMarkConverter([
            'html_input'         => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    public function render(string $markdown): string
    {
        $raw = (string) $this->converter->convert($markdown);
        $sanitized = Purifier::clean($raw, 'announcement');

        // Purifier with HTML.TargetBlank + HTML.TargetNoreferrer adds rel="noreferrer".
        // Ensure noopener is also present for older browsers.
        return preg_replace_callback(
            '/<a\b([^>]*)>/i',
            function ($m) {
                $attrs = $m[1];
                if (! preg_match('/\brel=/i', $attrs)) {
                    return '<a' . $attrs . ' rel="noopener noreferrer" target="_blank">';
                }
                if (! str_contains($attrs, 'noopener')) {
                    $attrs = preg_replace('/rel="([^"]*)"/i', 'rel="$1 noopener"', $attrs);
                }
                return '<a' . $attrs . '>';
            },
            $sanitized
        );
    }
}
