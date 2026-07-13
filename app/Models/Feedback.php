<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $fillable = [

        'service_id',

        // Location — always copied from the logged-in user, never chosen manually
        'city_id',
        'subcity_id',
        'woreda_id',

        // Who submitted it (logged-in user / kiosk officer account)
        'submitted_by',

        'overall_rating',

        'staff_behavior',

        'waiting_time',

        'service_quality',

        'cleanliness',

        'comment',

        'satisfaction',

        'gender',

        'age',

        'ip_address',

        'user_agent',

        'device',
    ];

    protected $casts = [

        'overall_rating' => 'integer',

        'staff_behavior' => 'integer',

        'waiting_time' => 'integer',

        'service_quality' => 'integer',

        'cleanliness' => 'integer',

        'city_id' => 'integer',

        'subcity_id' => 'integer',

        'woreda_id' => 'integer',

        'submitted_by' => 'integer',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function subcity()
    {
        return $this->belongsTo(Subcity::class);
    }

    public function woreda()
    {
        return $this->belongsTo(Woreda::class);
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * Scope feedback to a user's own location.
     * Most specific level wins: woreda > subcity > city.
     */
    public function scopeForUserLocation($query, ?User $user)
    {
        if (!$user) {
            return $query;
        }

        if ($user->woreda_id) {
            return $query->where('woreda_id', $user->woreda_id);
        }

        if ($user->subcity_id) {
            return $query->where('subcity_id', $user->subcity_id);
        }

        if ($user->city_id) {
            return $query->where('city_id', $user->city_id);
        }

        return $query;
    }
}
