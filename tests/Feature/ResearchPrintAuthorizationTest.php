<?php

namespace Tests\Feature;

use Tests\TestCase;

class ResearchPrintAuthorizationTest extends TestCase
{
    public function test_student_cannot_open_the_research_print_view_directly(): void
    {
        $this->withSession([
            'user_id' => 10,
            'user_role' => 'student',
        ])->get(route('research.index', ['print' => 1]))
            ->assertForbidden();
    }
}
