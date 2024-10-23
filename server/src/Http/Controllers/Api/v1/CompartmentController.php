<?php

namespace Fleetbase\FleetOps\Http\Controllers\Api\v1;

use Fleetbase\FleetOps\Http\Requests\CreateCompartmentRequest;
use Fleetbase\FleetOps\Http\Requests\UpdateCompartmentRequest;
use Fleetbase\FleetOps\Http\Resources\v1\Compartment as CompartmentResource;
use Fleetbase\FleetOps\Http\Resources\v1\DeletedResource;
use Fleetbase\FleetOps\Models\Compartment;
use Fleetbase\FleetOps\Support\Utils;
use Fleetbase\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CompartmentController extends Controller
{
    /**
     * Creates a new Fleetbase Compartment resource.
     *
     * @param \Fleetbase\FleetOps\Http\Requests\CreateCompartmentRequest $request
     * @return \Fleetbase\Http\Resources\Compartment
     */
    public function create(CreateCompartmentRequest $request)
    {
        // get request input
        $input = $request->only(['compartment_number', 'vehicle_uuid', 'capacity_gal', 'acceptable_fuels', 'meta']);

        // set company
        $input['company_uuid'] = session('company');

        // Check if a compartment with the same number already exists for this company
        $existingCompartment = Compartment::where('company_uuid', session('company'))
            ->where('compartment_number', $input['compartment_number'])
            ->first();

        if ($existingCompartment) {
            return response()->json(['error' => 'A compartment with this number already exists.'], 422);
        }

        // Check if the vehicle already has this compartment number
        if ($input['vehicle_uuid']) {
            $existingVehicleCompartment = Compartment::where('vehicle_uuid', $input['vehicle_uuid'])
                ->where('compartment_number', $input['compartment_number'])
                ->first();

            if ($existingVehicleCompartment) {
                return response()->json(['error' => 'This vehicle already has a compartment with this number.'], 422);
            }
        }

        // create the compartment
        $compartment = Compartment::create($input);

        // response the compartment resource
        return new CompartmentResource($compartment);
    }

    /**
     * Updates a Fleetbase Compartment resource.
     *
     * @param string $id
     * @param \Fleetbase\FleetOps\Http\Requests\UpdateCompartmentRequest $request
     * @return \Fleetbase\Http\Resources\Compartment
     */
    public function update($id, UpdateCompartmentRequest $request)
    {
        // find for the compartment
        $compartment = Compartment::findRecord($id);

        // get request input
        $input = $request->only(['compartment_number', 'vehicle_uuid', 'capacity_gal', 'filled_capacity_gal', 'acceptable_fuels', 'meta']);

        // vehicle assignment public_id -> uuid
        if ($request->has('vehicle')) {
            $input['vehicle_uuid'] = Utils::getUuid('vehicles', [
                'public_id' => $request->input('vehicle'),
                'company_uuid' => session('company'),
            ]);
        }

        // update the compartment
        $compartment->update($input);

        // response the compartment resource
        return new CompartmentResource($compartment);
    }

    /**
     * Query for Fleetbase Compartment resources.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Fleetbase\Http\Resources\CompartmentCollection
     */
    public function query(Request $request)
    {
        $results = Compartment::queryWithRequest($request);

        return CompartmentResource::collection($results);
    }

    /**
     * Finds a single Fleetbase Compartment resource.
     *
     * @param string $id
     * @return \Fleetbase\Http\Resources\Compartment
     */
    public function find($id)
    {
        $compartment = Compartment::findRecord($id);

        return new CompartmentResource($compartment);
    }

    /**
     * Deletes a Fleetbase Compartment resource.
     *
     * @param string $id
     * @return \Fleetbase\Http\Resources\DeletedResource
     */
    public function delete($id)
    {
        $compartment = Compartment::findRecord($id);

        $compartment->delete();

        return new DeletedResource($compartment);
    }
}
