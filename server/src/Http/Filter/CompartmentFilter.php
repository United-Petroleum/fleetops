<?php

namespace Fleetbase\FleetOps\Http\Filter;

use Fleetbase\FleetOps\Support\Utils;
use Fleetbase\Http\Filter\Filter;
use Illuminate\Support\Str;

class CompartmentFilter extends Filter
{
    public function queryForInternal()
    {
        $this->builder->where(
            function ($query) {
                $query->where('company_uuid', $this->session->get('company'));
            }
        );
    }

    public function queryForPublic()
    {
        $this->queryForInternal();
    }

    public function query(?string $searchQuery)
    {
        $this->builder->where(function ($query) use ($searchQuery) {
            $query->where('compartment_number', 'LIKE', "%{$searchQuery}%")
                ->orWhere('public_id', 'LIKE', "%{$searchQuery}%");
        });
    }

    public function internalId(?string $internalId)
    {
        $this->builder->searchWhere('internal_id', $internalId);
    }

    public function compartmentNumber(?string $compartmentNumber)
    {
        $this->builder->searchWhere('compartment_number', $compartmentNumber);
    }

    public function publicId(?string $publicId)
    {
        $this->builder->searchWhere('public_id', $publicId);
    }

    public function vehicle(string $vehicle)
    {
        if (Str::isUuid($vehicle)) {
            $this->builder->where('vehicle_uuid', $vehicle);
        } else {
            $this->builder->whereHas(
                'vehicle',
                function ($query) use ($vehicle) {
                    $query->search($vehicle);
                }
            );
        }
    }

    public function vendor(string $vendor)
    {
        if (Str::isUuid($vendor)) {
            $this->builder->where('vendor_uuid', $vendor);
        } else {
            $this->builder->whereHas(
                'vendor',
                function ($query) use ($vendor) {
                    $query->search($vendor);
                }
            );
        }
    }

    public function capacityGal($capacityGal)
    {
        $this->builder->where('capacity_gal', $capacityGal);
    }

    public function filledCapacityGal($filledCapacityGal)
    {
        $this->builder->where('filled_capacity_gal', $filledCapacityGal);
    }

    public function status(?string $status)
    {
        $this->builder->searchWhere('status', $status);
    }

    public function createdAt($createdAt)
    {
        $createdAt = Utils::dateRange($createdAt);

        if (is_array($createdAt)) {
            $this->builder->whereBetween('created_at', $createdAt);
        } else {
            $this->builder->whereDate('created_at', $createdAt);
        }
    }

    public function updatedAt($updatedAt)
    {
        $updatedAt = Utils::dateRange($updatedAt);

        if (is_array($updatedAt)) {
            $this->builder->whereBetween('updated_at', $updatedAt);
        } else {
            $this->builder->whereDate('updated_at', $updatedAt);
        }
    }
}

