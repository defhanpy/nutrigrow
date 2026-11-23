<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\ChatMessage;
use App\Models\HealthFacility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NutritionChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_nutrition_chat(): void
    {
        $response = $this->get('/konsultasi-gizi');
        $response->assertRedirect('/masuk');
    }

    public function test_authenticated_user_can_view_chat_page(): void
    {
        $facility = HealthFacility::factory()->create();
        $user = User::factory()->orangTua()->create(['facility_id' => $facility->id]);

        $response = $this->actingAs($user)->get('/konsultasi-gizi');
        $response->assertOk();
        $response->assertSee('NutriBot');
        $response->assertSee('Asisten Pintar Gizi Balita');
    }

    public function test_user_can_send_message_and_receive_response(): void
    {
        $facility = HealthFacility::factory()->create();
        $user = User::factory()->orangTua()->create(['facility_id' => $facility->id]);
        $child = Child::factory()->create(['facility_id' => $facility->id]);

        $response = $this->actingAs($user)->postJson('/konsultasi-gizi/kirim', [
            'message' => 'Apa ide menu MPASI balita 9 bulan?',
            'child_id' => $child->id,
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['success', 'user_message', 'reply', 'created_at']);
        $this->assertTrue($response->json('success'));
        $this->assertNotEmpty($response->json('reply'));

        $this->assertDatabaseHas('chat_messages', [
            'user_id' => $user->id,
            'role' => 'user',
            'message' => 'Apa ide menu MPASI balita 9 bulan?',
        ]);

        $this->assertDatabaseHas('chat_messages', [
            'user_id' => $user->id,
            'role' => 'assistant',
        ]);
    }

    public function test_empty_message_is_rejected(): void
    {
        $facility = HealthFacility::factory()->create();
        $user = User::factory()->orangTua()->create(['facility_id' => $facility->id]);

        $response = $this->actingAs($user)->postJson('/konsultasi-gizi/kirim', [
            'message' => '',
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_reset_own_chat_history(): void
    {
        $facility = HealthFacility::factory()->create();
        $user = User::factory()->orangTua()->create(['facility_id' => $facility->id]);
        $otherUser = User::factory()->orangTua()->create(['facility_id' => $facility->id]);

        ChatMessage::create(['user_id' => $user->id, 'role' => 'user', 'message' => 'Halo user 1']);
        ChatMessage::create(['user_id' => $otherUser->id, 'role' => 'user', 'message' => 'Halo user 2']);

        $response = $this->actingAs($user)->post('/konsultasi-gizi/reset');
        $response->assertRedirect('/konsultasi-gizi');

        $this->assertDatabaseMissing('chat_messages', ['user_id' => $user->id]);
        $this->assertDatabaseHas('chat_messages', ['user_id' => $otherUser->id]);
    }
}
