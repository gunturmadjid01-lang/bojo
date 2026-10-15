<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidationLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_messages_use_indonesian(): void
    {
        config()->set('app.locale', 'id');

        $this->actingAs(User::factory()->create());

        $response = $this->from('/dusun')->post('/dusun', [
            'desa_id' => '',
            'nama' => '',
            'aktif' => true,
        ]);

        $response->assertSessionHasErrors(['nama']);
        $this->assertSame('Kolom nama wajib diisi.', __('validation.required', ['attribute' => 'nama']));
    }
}
