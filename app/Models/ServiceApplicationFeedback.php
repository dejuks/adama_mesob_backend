<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceApplicationFeedback extends Model
{
    protected $table = 'service_application_feedbacks';

    protected $fillable = [
        'application_id',
        'customer_id',
        'satisfaction_scale',
        'comment',
    ];

    protected $casts = [
        'application_id' => 'integer',
        'customer_id' => 'integer',
    ];

    public function application()
    {
        return $this->belongsTo(ServiceApplication::class, 'application_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
