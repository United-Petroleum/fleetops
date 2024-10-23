<?php

namespace Fleetbase\FleetOps\Http\Resources\v1;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Http;

class Compartment extends FleetbaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'                     => $this->when(Http::isInternalRequest(), $this->id, $this->public_id),
            'uuid'                   => $this->when(Http::isInternalRequest(), $this->uuid),
            'public_id'              => $this->when(Http::isInternalRequest(), $this->public_id),
            'internal_id'            => $this->internal_id,
            'company_uuid'           => $this->when(Http::isInternalRequest(), $this->company_uuid),
            'vehicle_uuid'           => $this->when(Http::isInternalRequest(), $this->vehicle_uuid),
            'vendor_uuid'            => $this->when(Http::isInternalRequest(), $this->vendor_uuid),
            'order_uuid'             => $this->when(Http::isInternalRequest(), $this->order_uuid),
            'payload_uuid'           => $this->when(Http::isInternalRequest(), $this->payload_uuid),
            'compartment_number'     => $this->compartment_number,
            'capacity_gal'           => $this->capacity_gal,
            'filled_capacity_gal'    => $this->filled_capacity_gal,
            'acceptable_fuels'       => $this->acceptable_fuels,
            'status'                 => $this->status,
            'vehicle'                => $this->when(Http::isInternalRequest(), new VehicleWithoutDriver($this->vehicle)),
            'vendor'                 => $this->when(Http::isInternalRequest(), new Vendor($this->vendor)),
            'order'                  => $this->when(Http::isInternalRequest(), new Order($this->order)),
            'payload'                => $this->when(Http::isInternalRequest(), new Payload($this->payload)),
            'meta'                   => $this->meta,
            'updated_at'             => $this->updated_at,
            'created_at'             => $this->created_at,
        ];
    }
}

