<?php

namespace App\Services;

use App\Models\Subscriber;
use Illuminate\Support\Facades\Http;

class PublicationService
{
    /**
     * Notifies a single subscriber
     * 
     * @param Subscriber $subscriber
     * @param array $params
     *
     * @return bool
     */
    public function notifySubscriber(Subscriber $subscriber, array $params): bool
    {
        $request = Http::post($subscriber->url, $params);

        // Request is successful
        if ($request->ok()) {
            return true;
        }

        return false;
    }
}
