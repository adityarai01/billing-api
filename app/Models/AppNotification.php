<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $table = 'app_notifications';
    protected $guarded = [];

    protected $casts = [
        'notification_type' => 'integer',
        'source_type'       => 'integer',
        'priority'          => 'integer',
        'is_read'           => 'integer',
        'status'            => 'integer',
        'deleted'           => 'integer',
        'read_at'           => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
