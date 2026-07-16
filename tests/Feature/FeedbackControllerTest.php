<?php

namespace Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class FeedbackControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_users_cannot_submit_feedback()
    {
        $response = $this->postJson(route('api.feedback.store'), [
            'body' => 'This is a test feedback.'
        ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_authenticated_users_can_submit_feedback()
    {
        $user = User::factory()->create();

        $payload = [
            'user_id' => $user->id,
            'body' => 'This is a test feedback.',
            'is_public' => true,
        ];

        $response = $this->actingAs($user)
            ->postJson(route('api.feedback.store'), $payload);

        $response->assertStatus(Response::HTTP_CREATED);

        $response->assertJsonFragment([
            'user_id' => $payload['user_id'],
            'body' => $payload['body']
        ]);
    }

    public function test_feedback_requires_body_and_valid_user_id()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('api.feedback.store'), []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['body', 'user_id']);

        $response = $this->actingAs($user)
            ->postJson(route('api.feedback.store'), [
                'user_id' => 99999,
                'body' => 'Valid body',
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['user_id']);
    }
}
