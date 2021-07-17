<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use App\Http\Requests\PublishRequest;
use App\Http\Requests\SubscriptionRequest;
use App\Http\Resources\SubscriberResource;
use App\Services\PublicationService;
use Collection;
use DB;
use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TopicController extends Controller
{
    /**
     * Initialize Controller
     * 
     * @param PublicationService $publicationService
     */
    public function __construct(PublicationService $publicationService)
    {
        $this->publicationService = $publicationService;
    }

    /**
     * Registers a subscriber for the specified topic
     * 
     * @param string $topic
     * @param SubscriptionRequest $request
     *
     * @return JsonResponse
     */
    public function subscribe(string $topic, SubscriptionRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            if ($this->subscriberExists($request->input('url'), $topic)) {
                return response()->json([
                    'message' => __('topics.subscribers.exists'),
                ], Response::HTTP_NOT_ACCEPTABLE);
            }

            $subscriber = new Subscriber();
            $subscriber->topic = $topic;
            $subscriber->url = $request->input('url');
            $subscriber->save();

            DB::commit();

            return response()->json(
                new SubscriberResource($subscriber),
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Attempts to notify subscribers of specified topic
     * 
     * @param string $topic
     * @param PublishRequest $request
     *
     * @return JsonResponse
     */
    public function publish(string $topic, PublishRequest $request): JsonResponse
    {
        // Retrieve subscribers for topic
        $subscribers = Subscriber::whereTopic($topic)->get();

        // Return if no subscribers are found
        if ($subscribers->isEmpty()) {
            return response()->json([
                'message' => __('topics.subscribers.empty'),
            ], Response::HTTP_NOT_ACCEPTABLE);
        }

        // Create a collections of results
        $data = collect();

        foreach ($subscribers as $subscriber) {
            $data->push([
                'subscriber' => new SubscriberResource($subscriber),
                'sent' => $this->publicationService->notifySubscriber($subscriber, $request->all()),
            ]);
        }

        return response()->json(
            compact('data'),
            Response::HTTP_OK
        );
    }

    /**
     * Checks if a subscriber for a topic and url already exists
     * 
     * @param string $url
     * @param string $topic
     *
     * @return bool
     */
    public function subscriberExists(string $url, string $topic): bool
    {
        return Subscriber::whereUrl($url)->whereTopic($topic)->count() > 0;
    }
}
