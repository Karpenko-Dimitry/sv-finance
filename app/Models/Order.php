<?php

namespace App\Models;

use App\Services\MessageService\Actions\Home;
use App\Services\MessageService\MessageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\User as TelegramBotUser;

/**
 * App\Models\Order
 *
 * @property int $id
 * @property int $telegram_user_id
 * @property int|null $chat_id
 * @property string|null $type
 * @property string $status
 * @property array|null $file_ids
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Collection<int, \App\Models\OrderStep> $steps
 * @property-read int|null $steps_count
 * @method static Builder|Order newModelQuery()
 * @method static Builder|Order newQuery()
 * @method static Builder|Order query()
 * @method static Builder|Order whereChatId($value)
 * @method static Builder|Order whereCreatedAt($value)
 * @method static Builder|Order whereFileIds($value)
 * @method static Builder|Order whereId($value)
 * @method static Builder|Order whereStatus($value)
 * @method static Builder|Order whereTelegramUserId($value)
 * @method static Builder|Order whereType($value)
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
        'type', 'status', 'telegram_user_id', 'file_ids', 'chat_id'
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

    /**
     * @param TelegramBotUser $telegramUser
     * @param int $chatId
     * @return Order|Model|HasMany|\LaravelIdea\Helper\App\Models\_IH_Order_QB|object|null
     */
    public static function getOrder(TelegramBotUser $telegramUser, int $chatId)
    {
        if ($chatId < 0) {
            return null;
        }

        $user_id = $telegramUser->id;
        $telegramUser = TelegramUser::where(compact('user_id'))->first() ?? TelegramUser::makeNew($telegramUser->toArray());
        resolve('message')->setLocalTelegramUser($telegramUser);
        return $telegramUser->orders()->with(['steps'])->where('chat_id', $chatId)->where('status', self::STATUS_WAITING)->first()
            ?? $telegramUser->orders()->create(['status' => self::STATUS_WAITING, 'chat_id' => $chatId]);
    }

    public static function getOrderByLocalUser(TelegramUser $telegramUser) {
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

    /**
     * @param string $current_key
     * @param string $name
     * @param string|null $value
     * @param array|null $form_data
     * @param string|null $prev_key
     * @param array|null $value_keys
     * @return $this
     */
    public function syncSteps(
        string $current_key,
        string $name,
        ?string $value = null,
        ?array $form_data = null,
        ?string $prev_key = null,
        ?array $value_keys = null
    ): static
    {
        $current_key = explode(':', $current_key)[0] ?? '';

        $messageService = resolve('message');
        $message_id = $messageService->messageId;
        $lastStep = resolve('message')->lastStep;

        /** @var OrderStep $existingStep */
        $prev_key = $prev_key ?? $lastStep?->current_key ?? $messageService->defaultKey;

        if (explode('@', $current_key)[0] == (new Home())->getName()) {
            $this->steps()->delete();
            $prev_key = (new Home())->getActionKeyWithoutPostfix();
        }

        if ($existingStep = $this->steps()->where('current_key', $current_key)->first()) {
            $messageService->setLastStep($existingStep);
            $this->steps()->where('id', '>', $existingStep->id)->delete();

            !$existingStep->value && !$existingStep->form_data && $existingStep->update(compact('value', 'form_data'));
        } else {
            $this->steps()->create(compact(
                'current_key',
                'prev_key',
                'name',
                'value',
                'form_data',
                'message_id',
                'value_keys'
            ));
        }
        $this->load('steps');

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
        $messageService = resolve('message');
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

        if ($key == $messageService->defaultKey) {
            $order->steps()->delete();
        } else {
            /** @var OrderStep $existingStep */
            $existingStep = $order->steps()->where('key', $key)->first();
            $existingStep && $order->steps()->where('id', '>', $existingStep->id)->delete();
            !$existingStep && $order->steps()->create(compact('key', 'name', 'value'));
        }

        return $order;
    }

    /**
     * @return string
     */
    public function getStepsFormattedData(): string
    {
        return $this->steps->filter(fn(OrderStep $step) => $step->value || $step->form_data)->sortBy('id')
            ->map(function(OrderStep $step) {
                $formData = collect($step->form_data ?? [])->filter(fn($item) => !is_null($item));
                $result = $step->name . ' ' . $step->value . ($formData->count() ? "\n" : '');
                $transKey = explode('@', $step->current_key)[0] ?? '';
                $result .= $formData->map(function(array $item) use ($transKey) {
                    $name = trans('telegram.' . $transKey . '.form.label.' . $item['name'] ?? '');
                    $name = trim($name, ':');
                    $value =  $item['value'] ?? '';

                    return "$name: $value";
                })->implode("\n");
                return $result;
            })->implode("\n");
    }

    /**
     * @return array
     */
    public function getStepsValueKeys(): array
    {
        return $this->steps->filter(fn(OrderStep $step) => $step->value_keys)
            ->reduce(function(array $collection, OrderStep $step) {
                return array_merge($collection,  $step->value_keys ?? []);
            }, []);
    }


}
