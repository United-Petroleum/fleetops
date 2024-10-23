<?php

namespace Tests\Unit\Http\Controllers;

use Tests\TestCase;
use Fleetbase\Packages\FleetOps\Http\Controllers\CompartmentController;
use Fleetbase\Packages\FleetOps\Models\Compartment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompartmentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new CompartmentController();
    }

    public function test_index_returns_compartments()
    {
        Compartment::factory()->count(5)->create();
        $request = request();
        $response = $this->controller->index($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(5, $response->getData()->data);
    }

    public function test_store_creates_new_compartment()
    {
        $data = Compartment::factory()->make()->toArray();
        $request = request()->merge($data);
        $response = $this->controller->store($request);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertDatabaseHas('compartments', ['name' => $data['name']]);
    }

    public function test_show_returns_specific_compartment()
    {
        $compartment = Compartment::factory()->create();
        $response = $this->controller->show($compartment->id);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($compartment->id, $response->getData()->data->id);
    }

    public function test_update_modifies_compartment()
    {
        $compartment = Compartment::factory()->create();
        $data = ['name' => 'Updated Compartment'];
        $request = request()->merge($data);
        $response = $this->controller->update($request, $compartment->id);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Updated Compartment', $response->getData()->data->name);
    }

    public function test_destroy_deletes_compartment()
    {
        $compartment = Compartment::factory()->create();
        $response = $this->controller->destroy($compartment->id);
        $this->assertEquals(204, $response->getStatusCode());
        $this->assertDatabaseMissing('compartments', ['id' => $compartment->id]);
    }
}
