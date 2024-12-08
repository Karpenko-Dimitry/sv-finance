<?php

namespace App\Models;

use App\Services\MessageService\MessageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\User as TelegramBotUser;

/**
 * App\Models\Order
 *
 * @property int $id
 * @property int $telegram_user_id
 * @property string $status
 * @property array|null $file_ids
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Collection<int, \App\Models\OrderStep> $steps
 * @property-read int|null $steps_count
 * @method static Builder|Order newModelQuery()
 * @method static Builder|Order newQuery()
 * @method static Builder|Order query()
 * @method static Builder|Order whereCreatedAt($value)
 * @method static Builder|Order whereFileIds($value)
 * @method static Builder|Order whereId($value)
 * @method static Builder|Order whereStatus($value)
 * @method static Builder|Order whereTelegramUserId($value)
 * @method static Builder|Order whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Order extends Model
{
    const STATUS_WAITING = 'waiting';
    const STATUS_PROCESSING = 'processing';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_COMPLETED = 'completed';
    const TYPE_INDIVIDUALS = 'individuals';
    const TYPE_LEGAL_ENTITIES = 'legal_entities';
    const FILE_TYPE_DOOCUMENT = 'document';
    const FILE_TYPE_PHOTO = 'photo';

    protected $fillable = [
        'type', 'status', 'telegram_user_id', 'file_ids'
    ];

    protected $casts = [
        'file_ids' => 'array'
    ];

    /**
     * @return HasMany
     */
    public function steps(): HasMany
    {
        return $this->hasMany(OrderStep::class)->orderByDesc('created_at');
    }


    public static function getOrder(TelegramBotUser $telegramUser)
    {
        $user_id = $telegramUser->id;
        $telegramUser = TelegramUser::where(compact('user_id'))->first() ?? TelegramUser::makeNew($telegramUser->toArray());
        resolve('message')->setLocalTelegramUser($telegramUser);
        return $telegramUser->orders()->with(['steps'])->where('status', self::STATUS_WAITING)->first()
            ?? $telegramUser->orders()->create(['status' => self::STATUS_WAITING]);
    }

    /**
     * @return OrderStep|null
     */
    public function getLastStep(): OrderStep|null
    {
        return $this->steps->first();
    }

    public function getSecondStep(): OrderStep|null
    {
        $secondStep = $this->steps->skip(1)->first();
        return $secondStep?->first();
    }

    /**
     * @param string $current_key
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function syncSteps(string $current_key, string $name, string $value): static
    {
        /** @var OrderStep $existingStep */
        $prev_key = resolve('message')->lastStep?->current_key ?? 'start';
        if ($existingStep = $this->steps()->where('current_key', $current_key)->first()) {
            resolve('message')->setLastStep($existingStep);
            $this->steps()->where('id', '>', $existingStep->id)->delete();
            !$existingStep->value && $existingStep->update(compact('value'));
        } else {
            $this->steps()->create(compact('current_key', 'prev_key', 'name', 'value'));
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function checkout(): static
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
        return $this;
    }

    /**
     * @param TelegramBotUser $telegramUser
     * @param Message $message
     * @param string $key
     * @param string|null $name
     * @return Order|null
     */
    public static function make(TelegramBotUser $telegramUser, Message $message, string $key, ?string $name = null): ?Order
    {
        $key = trim($key, '/');
        $name = $name ?? $message->text ?? $message->caption ?? '';

        $keyBoards = Arr::flatten($message->replyMarkup?->inline_keyboard ?? [],1);
        $keyBoard = collect($keyBoards)->where('callback_data', $key)->first();
        $value = $keyBoard['text'] ?? $message->text ?? $message->caption ?? '';
        $user_id = $telegramUser->id;
        $telegramUser = TelegramUser::where(compact('user_id'))->first() ?? TelegramUser::makeNew($telegramUser->toArray());

        if (!$telegramUser) {
            return null;
        }

        /** @var Order $order */
        $order = $telegramUser->orders()->where('status', self::STATUS_WAITING)->first() ?? $telegramUser->orders()->create();

        if ($key == MessageService::DEFAULT_KEY) {
            $order->steps()->delete();
        } else {
            /** @var OrderStep $existingStep */
            $existingStep = $order->steps()->where('key', $key)->first();
            $existingStep && $order->steps()->where('id', '>', $existingStep->id)->delete();
            !$existingStep && $order->steps()->create(compact('key', 'name', 'value'));

            log_debug('test', compact('existingStep', 'key'));
        }

        return $order;
    }
}
