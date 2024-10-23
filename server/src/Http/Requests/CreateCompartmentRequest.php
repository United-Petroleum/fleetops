<?php

namespace Fleetbase\FleetOps\Http\Requests;

use Fleetbase\Http\Requests\FleetbaseRequest;
use Illuminate\Validation\Rule;

class CreateCompartmentRequest extends FleetbaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return request()->is('navigator/v1/*') || request()->session()->has('api_credential') || request()->session()->has('is_sanctum_token');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $isCreating = $this->isMethod('POST');

        return [
            'compartment_number' => [Rule::requiredIf($isCreating), 'string', Rule::unique('compartments')->where(function ($query) {
                return $query->where('company_uuid', session('company'));
            })],
            'vehicle_uuid'       => ['nullable', 'string', 'exists:vehicles,uuid'],
            'capacity_gal'       => ['required', 'numeric', 'min:0'],
            'filled_capacity_gal'=> ['nullable', 'numeric', 'min:0'],
            'acceptable_fuels'   => ['nullable', 'array'],
            'status'             => ['nullable', 'string', 'in:available,in_use,maintenance,out_of_service'],
            'vendor'             => ['nullable', 'exists:vendors,public_id'],
            'meta'               => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'compartment_number' => 'compartment number',
            'vehicle_uuid'       => 'vehicle',
            'capacity_gal'       => 'capacity in gallons',
            'filled_capacity_gal'=> 'filled capacity in gallons',
            'acceptable_fuels'   => 'acceptable fuels',
        ];
    }
}

