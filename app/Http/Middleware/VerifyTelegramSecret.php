<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerifyTelegramSecret
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return JsonResponse|mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $secret = $request->header('X-Telegram-Bot-Api-Secret-Token');
        if ($secret !== config('telegram.bots.mybot.webhook_secret')) {
            logger()->debug('Invalid telegram secret key: ' . $secret);
            return response()->json([
                'message' => 'Invalid secret key.'
            ], 427);
        }
        return $next($request);
    }
}
