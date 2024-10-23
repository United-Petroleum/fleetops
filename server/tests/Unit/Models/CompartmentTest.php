<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use Fleetbase\Packages\FleetOps\Models\Compartment;

class CompartmentTest extends TestCase
{
    public function test_compartment_can_be_created()
    {
        $compartment = Compartment::factory()->create();
        $this->assertInstanceOf(Compartment::class, $compartment);
    }

    public function test_compartment_has_required_attributes()
    {
        $compartment = Compartment::factory()->create();
        $this->assertNotNull($compartment->name);
        $this->assertNotNull($compartment->type);
        $this->assertNotNull($compartment->dimensions);
    }

    public function test_compartment_belongs_to_vehicle()
    {
        $compartment = Compartment::factory()->create();
        $this->assertInstanceOf(\Fleetbase\FleetOps\Models\Vehicle::class, $compartment->vehicle);
    }
}
