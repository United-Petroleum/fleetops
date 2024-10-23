<?php

namespace Fleetbase\FleetOps\Observers;

use Fleetbase\FleetOps\Models\Compartment;
use Fleetbase\FleetOps\Models\Order;
use Fleetbase\FleetOps\Models\Vehicle;

class CompartmentObserver
{
    /**
     * Handle the Compartment "creating" event.
     *
     * @param \Fleetbase\FleetOps\Models\Compartment $compartment
     * @return void
     */
    public function creating(Compartment $compartment)
    {
        // Set default values if not provided
        if (empty($compartment->status)) {
            $compartment->status = 'available';
        }

        if (empty($compartment->filled_capacity_gal)) {
            $compartment->filled_capacity_gal = 0;
        }
    }

    /**
     * Handle the Compartment "updating" event.
     *
     * @param \Fleetbase\FleetOps\Models\Compartment $compartment
     * @return void
     */
    public function updating(Compartment $compartment)
    {
        // Ensure filled capacity doesn't exceed total capacity
        if ($compartment->filled_capacity_gal > $compartment->capacity_gal) {
            $compartment->filled_capacity_gal = $compartment->capacity_gal;
        }
    }

    /**
     * Handle the Compartment "deleting" event.
     *
     * @param \Fleetbase\FleetOps\Models\Compartment $compartment
     * @return void
     */
    public function deleting(Compartment $compartment)
    {
        // Unassign the compartment from the vehicle
        if ($compartment->vehicle_uuid) {
            $compartment->vehicle_uuid = null;
            $compartment->save();
        }
    }

    /**
     * Handle the Compartment "deleted" event.
     *
     * @param \Fleetbase\FleetOps\Models\Compartment $compartment
     * @return void
     */
    public function deleted(Compartment $compartment)
    {
        // Unassign the compartment from any order it is assigned to
        Order::where(['payload_uuid' => $compartment->payload_uuid])->update(['payload_uuid' => null]);

        // If the compartment was assigned to a vehicle, update the vehicle's compartment count
        if ($compartment->vehicle_uuid) {
            $vehicle = Vehicle::find($compartment->vehicle_uuid);
            if ($vehicle) {
                $vehicle->decrement('compartment_count');
            }
        }
    }
}

