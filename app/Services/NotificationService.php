<?php

namespace App\Services;

use App\Events\NotificationSentEvent;
use App\Models\Notification;
use App\Models\User;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class NotificationService
{
    public function __construct(protected Messaging $messaging)
    {
    }

    public function send(
        User $user,
        string $type,
        string $titleKey,
        string $bodyKey,
        ?array $parameters = null,
        ?array $metadata = null
    ): Notification {
        $notification = Notification::create([
            'user_id'    => $user->id,
            'type'       => $type,
            'title_key'  => $titleKey,
            'body_key'   => $bodyKey,
            'parameters' => $parameters,
            'is_read'    => false,
            'metadata'   => $metadata,
        ]);

        event(new NotificationSentEvent($notification));

        if ($user->fcm_token) {
            $this->sendFirebaseNotification($user, $notification);
        }

        return $notification;
    }

    protected function sendFirebaseNotification(User $user, Notification $notification): void
    {
        try {
            app()->setLocale($user->locale ?? 'en');

            $title = __($notification->title_key, $notification->parameters ?? []);
            $body = __($notification->body_key, $notification->parameters ?? []);

            $customData = array_merge($notification->metadata ?? [], [
                'notification_id' => (string) $notification->id,
                'type'            => $notification->type,
            ]);

            $stringifiedData = array_map(
                fn($value) => is_array($value) ? json_encode($value) : (string) $value,
                $customData
            );

            $message = CloudMessage::new()
                ->withToken($user->fcm_token)
                ->withNotification(FirebaseNotification::create($title, $body))
                ->withData($stringifiedData);

            $this->messaging->send($message);

            \Log::info("FCM Notification sent successfully to user: {$user->id}");

        } catch (\Kreait\Firebase\Exception\Messaging\NotFound $e) {
            $user->update(['fcm_token' => null]);
            \Log::warning("Invalid FCM Token removed for user: {$user->id}");

        } catch (\Throwable $e) {
            \Log::error("FCM Notification failed: " . $e->getMessage());
        }
    }
}
