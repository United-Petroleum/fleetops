<?php

namespace Fleetbase\FleetOps\Models;

use Fleetbase\Casts\Json;
use Fleetbase\FleetOps\Casts\Point;
use Fleetbase\FleetOps\Scopes\DriverScope;
use Fleetbase\FleetOps\Support\Utils;
use Fleetbase\FleetOps\Support\Utils as FleetOpsUtils;
use Fleetbase\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Fleetbase\Models\File;
use Fleetbase\Models\Model;
use Fleetbase\Models\User;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasInternalId;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;
use Fleetbase\Traits\SendsWebhooks;
use Fleetbase\Traits\TracksApiCredential;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\CausesActivity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use WebSocket\Message\Ping;

class Compartment extends Model
{
    use HasUuid;
    use HasPublicId;
    use HasInternalId;
    use TracksApiCredential;
    use HasApiModelBehavior;
    use Notifiable;
    use SendsWebhooks;
    use SpatialTrait;
    use HasSlug;
    use LogsActivity;
    use CausesActivity;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'compartments';

    /**
     * The type of public Id to generate.
     *
     * @var string
     */
    protected $publicIdType = 'compartment';

    /**
     * The attributes that can be queried.
     *
     * @var array
     */
    protected $searchableColumns = ['compartment_number'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        '_key',
        'public_id',
        'internal_id',
        'company_uuid',
        'vehicle_uuid',
        'vendor_uuid',
        'current_job_uuid',
        'auth_token',
        'signup_token_used',
        'avatar_url',
        'compartment_number',
        'capacity',
        'acceptable_fuels',
        'location',
        'heading',
        'bearing',
        'altitude',
        'speed',
        'country',
        'currency',
        'city',
        'online',
        'current_status',
        'slug',
        'status',
        'meta',
    ];

    /**
     * The attributes that are guarded and not mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that are spatial columns.
     *
     * @var array
     */
    protected $spatialFields = ['location'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'location'           => Point::class,
        'online'             => 'boolean',
        'meta'               => Json::class,
    ];

    /**
     * Relationships to auto load with compartment.
     *
     * @var array
     */
    protected $with = [];

    /**
     * Dynamic attributes that are appended to object.
     *
     * @var array
     */
    protected $appends = [
        'current_job_id',
        'vehicle_id',
        'vendor_id',
        'photo_url',
        'rotation',
        'vehicle_name',
        'vehicle_avatar',
        'vendor_name',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['currentJob', 'vendor', 'vehicle', 'user', 'latitude', 'longitude', 'auth_token'];

    /**
     * Attributes that is filterable on this model.
     *
     * @var array
     */
    protected $filterParams = ['vendor', 'facilitator', 'customer', 'fleet', 'photo_uuid', 'avatar_uuid', 'avatar_value'];

    /**
     * Get the activity log options for the model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['*'])->logOnlyDirty();
    }

    /**
     * Get the options for generating the slug.
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('compartment_number')
            ->saveSlugsTo('slug');
    }

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new CompartmentScope());
    }

    /**
     * @return BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(\Fleetbase\Models\Company::class);
    }

    /**
     * @return BelongsTo
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class)->select([
            'uuid',
            'vendor_uuid',
            'photo_uuid',
            'avatar_url',
            'public_id',
            'year',
            'make',
            'model',
            'model_data',
            'vin_data',
            'telematics',
            'meta',
            'trim',
            'plate_number',
            DB::raw("CONCAT(vehicles.year, ' ', vehicles.make, ' ', vehicles.model, ' ', vehicles.trim, ' ', vehicles.plate_number) AS display_name"),
        ]);
    }

    public function vendor(): BelongsTo|Builder
    {
        return $this->belongsTo(Vendor::class)->select(['id', 'uuid', 'public_id', 'name']);
    }

    public function currentJob(): BelongsTo|Builder
    {
        return $this->belongsTo(Order::class)->select(['id', 'uuid', 'public_id', 'payload_uuid', 'compartment_assigned_uuid'])->without(['compartment']);
    }

    public function currentOrder(): BelongsTo|Builder
    {
        return $this->belongsTo(Order::class, 'current_job_uuid')->select(['id', 'uuid', 'public_id', 'payload_uuid', 'compartment_assigned_uuid'])->without(['compartment']);
    }

    public function jobs(): HasMany|Builder
    {
        return $this->hasMany(Order::class, 'compartment_assigned_uuid')->without(['compartment']);
    }

    public function orders(): HasMany|Builder
    {
        return $this->hasMany(Order::class, 'compartment_assigned_uuid')->without(['compartment']);
    }

    public function fleets(): HasManyThrough
    {
        return $this->hasManyThrough(Fleet::class, FleetDriver::class, 'driver_uuid', 'uuid', 'uuid', 'fleet_uuid');
    }

    /**
     * Get avatar url.
     */
    public function getAvatarUrlAttribute($value): ?string
    {
        // if vehicle assigned us the vehicle avatar
        $this->loadMissing('vehicle');
        if ($this->vehicle) {
            return $this->vehicle->avatar_url;
        }

        if (!$value) {
            return static::getAvatar();
        }

        if (Str::isUuid($value)) {
            return static::getAvatar($value);
        }

        return $value;
    }

    /**
     * Get an avatar url by key.
     *
     * @param string $key
     */
    public static function getAvatar($key = 'moto-driver'): ?string
    {
        if (Str::isUuid($key)) {
            $file = File::where('uuid', $key)->first();
            if ($file) {
                return $file->url;
            }

            return null;
        }

        return static::getAvatarOptions()->get($key);
    }

    /**
     * Get all avatar options for a vehicle.
     */
    public static function getAvatarOptions(): Collection
    {
        $options = [
            'moto-driver.png',
        ];

        // Get custom avatars
        $customAvatars = collect(File::where('type', 'driver-avatar')->get()->mapWithKeys(
            function ($file) {
                $key = str_replace(['.svg', '.png'], '', 'Custom: ' . $file->original_filename);

                return [$key => $file->uuid];
            }
        )->toArray());

        // Create default avatars included from fleetbase
        $avatars = collect($options)->mapWithKeys(
            function ($option) {
                $key = str_replace(['.svg', '.png'], '', $option);

                return [$key => Utils::assetFromS3('static/driver-icons/' . $option)];
            }
        );

        return $customAvatars->merge($avatars);
    }

    /**
     * Get assigned vehicle assigned name.
     */
    public function getCurrentJobIdAttribute(): ?string
    {
        return $this->currentJob()->value('public_id');
    }

    /**
     * Get assigned vehicle assigned name.
     */
    public function getVehicleNameAttribute(): ?string
    {
        $this->loadMissing('vehicle');

        return $this->vehicle ? $this->vehicle->display_name : null;
    }

    /**
     * Get assigned vehicles public ID.
     */
    public function getVehicleIdAttribute(): ?string
    {
        return $this->vehicle()->value('public_id');
    }

    /**
     * Get assigned vehicles public ID.
     */
    public function getVehicleAvatarAttribute()
    {
        if ($this->isVehicleNotAssigned()) {
            return Vehicle::getAvatar();
        }

        return $this->vehicle()->value('avatar_url');
    }

    /**
     * Get compartments vendor ID.
     */
    public function getVendorIdAttribute()
    {
        return $this->vendor()->select(['uuid', 'public_id', 'name'])->value('public_id');
    }

    /**
     * Get compartments vendor name.
     */
    public function getVendorNameAttribute()
    {
        return $this->vendor()->select(['uuid', 'public_id', 'name'])->value('name');
    }

    /**
     * Unassigns the current order from the compartment if a compartment is assigned.
     *
     * @return bool True if the compartment was unassigned and the changes were saved, false otherwise
     */
    public function unassignCurrentOrder()
    {
        if (!empty($this->compartment_assigned_uuid)) {
            $this->compartment_assigned_uuid = null;

            return $this->save();
        }

        return false;
    }

    /**
     * Assigns the specified vehicle to the current compartment.
     *
     * This method performs the following actions:
     * 1. Unassigns the vehicle from any other compartments by setting their `vehicle_uuid` to `null`.
     * 2. Assigns the vehicle to the current compartment by updating the vehicle's `compartment_uuid`.
     * 3. Associates the vehicle with the current compartment instance.
     * 4. Saves the changes to persist the assignment.
     *
     * @param Vehicle $vehicle the vehicle instance to assign to the compartment
     *
     * @return $this returns the current compartment instance after assignment
     *
     * @throws \Exception if the vehicle assignment fails
     */
    public function assignVehicle(Vehicle $vehicle): self
    {
        // Unassign vehicle from other compartments
        static::where('vehicle_uuid', $vehicle->uuid)->update(['vehicle_uuid' => null]);

        // Set this vehicle to the driver instance
        $this->setVehicle($vehicle);
        $this->save();

        return $this;
    }

    /**
     * Sets the vehicle for the current compartment instance.
     *
     * This method updates the `vehicle_uuid` attribute of the compartment and establishes
     * the relationship between the compartment and the vehicle model instance.
     *
     * @param Vehicle $vehicle the vehicle instance to associate with the compartment
     *
     * @return $this returns the current compartment instance after setting the vehicle
     */
    public function setVehicle(Vehicle $vehicle)
    {
        // Update the compartment's vehicle UUID
        $this->vehicle_uuid = $vehicle->uuid;

        // Establish the relationship with the vehicle
        $this->setRelation('vehicle', $vehicle);

        return $this;
    }

    /**
     * Checks if the vehicle is assigned to the compartment.
     *
     * @return bool True if the vehicle is assigned, false otherwise
     */
    public function isVehicleAssigned()
    {
        return $this->isVehicleNotAssigned() === false;
    }

    /**
     * Checks if the vehicle is not assigned to the compartment.
     *
     * @return bool True if the vehicle is not assigned, false otherwise
     */
    public function isVehicleNotAssigned()
    {
        return !$this->vehicle_uuid;
    }

    /**
     * Find a compartment by its identifier.
     *
     * @param string|null $identifier
     * @return Compartment|null
     */
    public static function findByIdentifier(?string $identifier = null): ?Compartment
    {
        if (is_null($identifier)) {
            return null;
        }

        return static::where('company_uuid', session('company'))
            ->where(function ($query) use ($identifier) {
                $query->where('public_id', $identifier)
                    ->orWhere('compartment_number', $identifier);
            })
            ->first();
    }
}
