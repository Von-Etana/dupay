<?php

namespace App\Services;

use Illuminate\Http\Request;
use Kreait\Firebase;
use Kreait\Firebase\Factory;

class FirebaseService
{

    public static function connect()
    {
        $credentials = config('firebase.projects.app.credentials.file');
        if (!$credentials) {
            throw new \RuntimeException('Firebase credentials are not configured');
        }
        $credentialsPath = str_starts_with($credentials, '/') ||
            preg_match('/^[A-Za-z]:[\\\\\/]/', $credentials)
            ? $credentials
            : base_path($credentials);

        $firebase = (new Factory)
            ->withServiceAccount($credentialsPath)
            ->withDatabaseUri(config('firebase.projects.app.database.url'));

        return $firebase->createDatabase();
    }
    
    
     public function addUserNotification($user, array $notification)
    {
        // 1. Save notification in Realtime Database
            try {
        // Send the device alert first so transaction notifications are not
        // delayed by the Realtime Database write.
        if (!empty($user->fcmToken)) {
            $token = $user->fcmToken;

            $title = $notification['service'] ?? 'New Notification';
            $body = $notification['message'];
            $data = $notification['data'] ?? [];

            sendFcmNotification($token, $title, $body, $data);
        }

        // Persist the full notification for the in-app center after delivery.
        $database = FirebaseService::connect();
        $database
            ->getReference('notifications/' . $user->id)
            ->push($notification);

        return [
            'status' => true,
            'message' => 'Notification added successfully.'
        ];

    } catch (\Exception $e) {
        \Log::error("Error adding user notification for user_id {$user->id}: " . $e->getMessage());

        return [
            'status' => false,
            'message' => 'Failed to add notification.',
            'error' => $e->getMessage()
        ];
    }
    
    
    }
    
    






}
