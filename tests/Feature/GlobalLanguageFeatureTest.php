<?php

namespace Tests\Feature;

use Tests\TestCase;

class GlobalLanguageFeatureTest extends TestCase
{
    public function test_login_page_includes_a_global_english_and_filipino_page_language_switcher(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-page-language', false)
            ->assertSee('<option value="en">English</option>', false)
            ->assertSee('<option value="tl">Filipino</option>', false)
            ->assertSee('translate.google.com/translate_a/element.js', false)
            ->assertSee('googtrans=/en/', false);
    }

    public function test_landing_page_includes_the_global_page_language_switcher(): void
    {
        $this->view('welcome', [
            'featuredResearch' => collect(),
            'trendingResearch' => collect(),
            'topDownloadedResearch' => collect(),
        ])
            ->assertSee('data-page-language', false)
            ->assertSee('<option value="tl">Filipino</option>', false)
            ->assertSee('translate.google.com/translate_a/element.js', false);
    }
}