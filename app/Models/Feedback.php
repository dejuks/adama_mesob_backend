<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $fillable = [

        'service_id',

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
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
