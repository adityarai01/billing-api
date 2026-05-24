<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    protected $table = 'notification_settings';
    protected $guarded = [];

    protected $casts = [
        'notification_type'      => 'integer',
        'is_enabled'             => 'integer',
        'notify_admin'           => 'integer',
        'notify_cashier'         => 'integer',
        'notify_inventory_staff' => 'integer',
        'notify_manager'         => 'integer',
        'threshold_value'        => 'decimal:3',
        'before_days'            => 'integer',
        'show_popup'             => 'integer',
        'send_email'             => 'integer',
        'send_whatsapp'          => 'integer',
    ];
}
