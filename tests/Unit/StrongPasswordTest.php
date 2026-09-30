<?php

namespace Tests\Unit;

use App\Helpers\ContentHelper;
use App\Rules\StrongPassword;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StrongPasswordTest extends TestCase
{
    public function test_password_requires_uppercase_number_and_symbol(): void
    {
        $weak = Validator::make(
            ['password' => 'password', 'password_confirmation' => 'password'],
            ['password' => StrongPassword::rules()]
        );
        $strong = Validator::make(
            ['password' => 'NewPassword1!', 'password_confirmation' => 'NewPassword1!'],
            ['password' => StrongPassword::rules()]
        );

        $this->assertTrue($weak->fails());
        $this->assertTrue($strong->passes());
    }

    public function test_content_renderer_preserves_remote_figure_urls(): void
    {
        $html = ContentHelper::renderContent(
            '[figure: https://example.com/figure.png | Figure 1. Online image]'
        );

        $this->assertStringContainsString('src="https://example.com/figure.png"', $html);
        $this->assertStringNotContainsString('Image not available', $html);
    }

    public function test_content_renderer_converts_google_drive_links_to_thumbnail_urls(): void
    {
        $html = ContentHelper::renderContent(
            '[figure: https://drive.google.com/file/d/abc123/view?usp=sharing | Figure 1. Drive image]'
        );

        $this->assertStringContainsString('https://drive.google.com/thumbnail?id=abc123&amp;sz=w2000', $html);
        $this->assertStringNotContainsString('Image not available', $html);
    }

    public function test_pdf_renderer_embeds_remote_figure_images(): void
    {
        $image = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');

        Http::fake([
            'example.com/*' => Http::response($image, 200, ['Content-Type' => 'image/png']),
        ]);

        $html = ContentHelper::renderContent(
            '[figure: https://example.com/figure.png | Figure 1. Online image]',
            '',
            'pdf'
        );

        $this->assertStringContainsString('src="', $html);
        $this->assertStringContainsString('.png"', $html);
        $this->assertStringNotContainsString('Image not available', $html);

        preg_match('/src="([^"]+)"/', $html, $matches);
        $pdf = Pdf::loadHtml($html)->output();

        $this->assertStringStartsWith('%PDF', $pdf);

        if (! empty($matches[1]) && file_exists($matches[1])) {
            unlink($matches[1]);
        }
    }
}
