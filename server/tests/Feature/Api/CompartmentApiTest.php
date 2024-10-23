<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Fleetbase\Packages\FleetOps\Models\Compartment;

class CompartmentApiTest extends TestCase
{
    public function test_can_list_compartments()
    {
        $response = $this->getJson('/api/v1/fleet-ops/compartments');
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    public function test_can_create_compartment()
    {
        $data = Compartment::factory()->make()->toArray();
        $response = $this->postJson('/api/v1/fleet-ops/compartments', $data);
        $response->assertStatus(201);
        $response->assertJsonStructure(['data' => ['id', 'name', 'type', 'dimensions']]);
    }

    public function test_can_retrieve_compartment()
    {
        $compartment = Compartment::factory()->create();
        $response = $this->getJson("/api/v1/fleet-ops/compartments/{$compartment->id}");
        $response->assertStatus(200);
        $response->assertJson(['data' => ['id' => $compartment->id]]);
    }

    public function test_can_update_compartment()
    {
        $compartment = Compartment::factory()->create();
        $data = ['name' => 'Updated Compartment'];
        $response = $this->patchJson("/api/v1/fleet-ops/compartments/{$compartment->id}", $data);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['name' => 'Updated Compartment']]);
    }

    public function test_can_delete_compartment()
    {
        $compartment = Compartment::factory()->create();
        $response = $this->deleteJson("/api/v1/fleet-ops/compartments/{$compartment->id}");
        $response->assertStatus(204);
    }
}
