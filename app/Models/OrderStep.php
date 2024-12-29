<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\OrderStep
 *
 * @property int $id
 * @property int $order_id
 * @property int|null $message_id
 * @property string $current_key
 * @property string $prev_key
 * @property string $name
 * @property string|null $value
 * @property array|null $value_keys
 * @property array|null $form_data
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \App\Models\Order $order
 * @method static Builder|OrderStep newModelQuery()
 * @method static Builder|OrderStep newQuery()
 * @method static Builder|OrderStep query()
 * @method static Builder|OrderStep whereCreatedAt($value)
 * @method static Builder|OrderStep whereCurrentKey($value)
 * @method static Builder|OrderStep whereFormData($value)
 * @method static Builder|OrderStep whereId($value)
 * @method static Builder|OrderStep whereMessageId($value)
 * @method static Builder|OrderStep whereName($value)
 * @method static Builder|OrderStep whereOrderId($value)
 * @method static Builder|OrderStep wherePrevKey($value)
 * @method static Builder|OrderStep whereUpdatedAt($value)
 * @method static Builder|OrderStep whereValue($value)
 * @method static Builder|OrderStep whereValueKeys($value)
 * @mixin \Eloquent
 */
class OrderStep extends Model
{
    protected $fillable = [
        'order_id', 'current_key', 'prev_key', 'name', 'value', 'form_data', 'message_id', 'value_keys'
    ];

    protected $casts = [
        'form_data' => 'array',
        'value_keys' => 'array',
    ];

    /**
     * @return BelongsTo
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
