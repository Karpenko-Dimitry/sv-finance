<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ChatMember
 *
 * @property int $id
 * @property int $chat_id
 * @property string $title
 * @property string $type
 * @property string|null $order_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMember newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMember newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMember query()
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMember whereChatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMember whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMember whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMember whereOrderType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMember whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMember whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMember whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ChatMember extends Model
{
    protected $fillable = [
        'chat_id', 'title', 'type', 'order_type'
    ];

    public static function makeNew(?array $data) {
        if (!isset($data['id'])) {
            return null;
        }

        $values = array_merge(collect($data)->only([
            'title', 'type',
        ])->toArray(), [
            'chat_id' => (string) $data['id'],
            'order_type' => $data['order_type'] ?? null,
        ]);

        return self::firstOrCreate(['chat_id' => $values['chat_id']], $values);
    }
}
