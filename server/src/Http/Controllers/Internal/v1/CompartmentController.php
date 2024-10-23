<?php

namespace Fleetbase\FleetOps\Http\Controllers\Internal\v1;

use Fleetbase\FleetOps\Exports\CompartmentExport;
use Fleetbase\FleetOps\Http\Controllers\FleetOpsController;
use Fleetbase\FleetOps\Http\Requests\Internal\CreateCompartmentRequest;
use Fleetbase\FleetOps\Http\Requests\Internal\UpdateCompartmentRequest;
use Fleetbase\FleetOps\Imports\CompartmentImport;
use Fleetbase\FleetOps\Models\Compartment;
use Fleetbase\Http\Requests\ExportRequest;
use Fleetbase\Http\Requests\ImportRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class CompartmentController extends FleetOpsController
{
    /**
     * The resource to query.
     *
     * @var string
     */
    public $resource = 'compartment';

    /**
     * Creates a record with request payload.
     *
     * @return \Illuminate\Http\Response
     */
    public function createRecord(Request $request)
    {
        $input = $request->input('compartment');

        // create validation request
        $createCompartmentRequest = CreateCompartmentRequest::createFrom($request);
        $rules = $createCompartmentRequest->rules();

        // manually validate request
        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            return $createCompartmentRequest->responseWithErrors($validator);
        }

        // Check if a compartment with the same number already exists for this company
        $existingCompartment = Compartment::where('company_uuid', session('company'))
            ->where('compartment_number', $input['compartment_number'])
            ->first();

        if ($existingCompartment) {
            return response()->error('A compartment with this number already exists.');
        }

        // Check if the vehicle already has this compartment number
        if (isset($input['vehicle_uuid'])) {
            $existingVehicleCompartment = Compartment::where('vehicle_uuid', $input['vehicle_uuid'])
                ->where('compartment_number', $input['compartment_number'])
                ->first();

            if ($existingVehicleCompartment) {
                return response()->error('This vehicle already has a compartment with this number.');
            }
        }

        try {
            $record = $this->model->createRecordFromRequest(
                $request,
                function (&$request, &$input) {
                    // Additional logic for creating compartment
                },
                function ($request, &$compartment) {
                    // Additional logic after compartment creation
                }
            );

            return ['compartment' => new $this->resource($record)];
        } catch (\Exception $e) {
            return response()->error($e->getMessage());
        }
    }

    /**
     * Updates a record with request payload.
     *
     * @return \Illuminate\Http\Response
     */
    public function updateRecord(Request $request, string $id)
    {
        // get input data
        $input = $request->input('compartment');

        // create validation request
        $updateCompartmentRequest = UpdateCompartmentRequest::createFrom($request);
        $rules = $updateCompartmentRequest->rules();

        // manually validate request
        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            return $updateCompartmentRequest->responseWithErrors($validator);
        }

        try {
            $record = $this->model->updateRecordFromRequest(
                $request,
                $id,
                function (&$request, &$compartment, &$input) {
                    // Additional logic for updating compartment
                },
                function ($request, &$compartment) {
                    // Additional logic after compartment update
                }
            );

            return ['compartment' => new $this->resource($record)];
        } catch (\Exception $e) {
            return response()->error($e->getMessage());
        }
    }

    /**
     * Get all status options for a compartment.
     *
     * @return \Illuminate\Http\Response
     */
    public function statuses()
    {
        $statuses = DB::table('compartments')
            ->select('status')
            ->where('company_uuid', session('company'))
            ->distinct()
            ->get()
            ->pluck('status')
            ->filter()
            ->values();

        return response()->json($statuses);
    }

    /**
     * Export the compartments to excel or csv.
     *
     * @return \Illuminate\Http\Response
     */
    public static function export(ExportRequest $request)
    {
        $format = $request->input('format', 'xlsx');
        $selections = $request->array('selections');
        $fileName = trim(Str::slug('compartments-' . date('Y-m-d-H:i')) . '.' . $format);

        return Excel::download(new CompartmentExport($selections), $fileName);
    }

    /**
     * Process import files (excel,csv) into Fleetbase compartment data.
     *
     * @return \Illuminate\Http\Response
     */
    public function import(ImportRequest $request)
    {
        $disk = $request->input('disk', config('filesystems.default'));
        $files = $request->resolveFilesFromIds();

        foreach ($files as $file) {
            try {
                Excel::import(new CompartmentImport(), $file->path, $disk);
            } catch (\Throwable $e) {
                return response()->error('Invalid file, unable to process.');
            }
        }

        return response()->json(['status' => 'ok', 'message' => 'Import completed']);
    }
}
