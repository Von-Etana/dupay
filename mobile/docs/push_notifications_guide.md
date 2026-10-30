# Push Notifications Architecture & Verification Guide (Android & iOS)

This document outlines the complete push notifications architecture for DuPay, the changes implemented, and step-by-step verification instructions for both platforms.

---

## 1. Architecture Overview

Push notifications in DuPay are powered by **Firebase Cloud Messaging (FCM)** with direct APNs integration for iOS.

```
       [ DuPay Backend ]
               │
               ▼ (FCM HTTP v1 API)
       [ Firebase Cloud Messaging ]
         │                     │
         ▼                     ▼
    [ Android (FCM) ]     [ iOS (APNs via FCM) ]
         │                     │
  High-importance channel   Foreground presentation
   System Tray / Heads-Up   Alert / Badge / Sound
         │                     │
         └──────────┬──────────┘
                    ▼
          [ DuPay Flutter App ]
          • Token Sync to backend (/update-fcm-token)
          • Foreground SnackBar with 'View' Action
          • Background Tap Routing
          • Terminated (Cold-Start) Tap Routing
          • Token Revocation on Logout
```

---

## 2. Platform Implementations & Fixes

### Android (`com.dupay.wallet.com`)
1. **High-Importance Notification Channel (`dupay_channel`)**:
   - Created in `android/app/src/main/kotlin/com/dupay/wallet/com/MainActivity.kt` with `NotificationManager.IMPORTANCE_HIGH`, badge enabled, and vibration enabled.
   - Configured in `android/app/src/main/AndroidManifest.xml` via `com.google.firebase.messaging.default_notification_channel_id` metadata.
   - *Impact*: Ensures notifications show as heads-up banners with sound and vibration even when sent from Firebase Console without custom channel parameters.
2. **Notification Icons**:
   - Registered `com.google.firebase.messaging.default_notification_icon` metadata pointing to `@mipmap/launcher_icon`.
3. **Permissions**:
   - Declared `POST_NOTIFICATIONS` for Android 13+ (API 33+) runtime permission prompt.
4. **Google Services**:
   - Configured `google-services.json` and applied `com.google.gms.google-services` plugin in `android/app/build.gradle.kts`.

### iOS (`com.my.dupay.app.new`)
1. **APNs & Capabilities**:
   - `aps-environment` entitlement in `ios/Runner/Runner.entitlements`.
   - `UIBackgroundModes` with `remote-notification` and `fetch` in `ios/Runner/Info.plist`.
   - Push Notifications and Background Modes enabled in `ios/Runner.xcodeproj/project.pbxproj`.
2. **AppDelegate**:
   - `UNUserNotificationCenter.current().delegate = self` and `application.registerForRemoteNotifications()` in `AppDelegate.swift`.
3. **APNs-to-FCM Token Bridge**:
   - `NotificationService._bootstrapToken()` polls for APNs token before requesting FCM token to prevent the iOS `apns-token-not-set` race condition.
4. **Foreground Presentation**:
   - `setForegroundNotificationPresentationOptions(alert: true, badge: true, sound: true)` enabled.

### Application Lifecycle & Tap Routing (Both Platforms)
1. **Foreground Delivery**:
   - Renders a floating notification banner/SnackBar with a direct **View** action button.
2. **Background Tap Handling**:
   - `FirebaseMessaging.onMessageOpenedApp` resolves the route from `message.data['route']` or `message.data['screen']` (or defaults to `/notification`) and pushes to `navigatorKey`.
3. **Terminated / Cold-Start Tap Handling**:
   - `FirebaseMessaging.instance.getInitialMessage()` captures the launch notification and navigates seamlessly via `SplashScreen` once authenticated.
4. **Token Lifecycle & Security**:
   - Refreshed tokens are synced to the backend via `FcmTokenSync` (`/update-fcm-token`).
   - `NotificationService.deleteToken()` is invoked upon logout in `AuthProvider.logout()`, ensuring a logged-out user stops receiving push notifications.

---

## 3. Server Payload Specification

The backend can send either standard display notifications, data messages, or both:

### Standard Notification with Deep-Linking:
```json
{
  "message": {
    "token": "<FCM_DEVICE_TOKEN>",
    "notification": {
      "title": "Transfer Received",
      "body": "You have received ₦5,000.00 from John Doe."
    },
    "data": {
      "route": "/notification",
      "type": "transaction"
    },
    "android": {
      "priority": "high",
      "notification": {
        "channel_id": "dupay_channel",
        "sound": "default"
      }
    },
    "apns": {
      "payload": {
        "aps": {
          "alert": {
            "title": "Transfer Received",
            "body": "You have received ₦5,000.00 from John Doe."
          },
          "sound": "default",
          "badge": 1
        }
      }
    }
  }
}
```

---

## 4. How to Test & Verify

### A. Testing on Android
1. Connect an Android device or emulator.
2. Run the application:
   ```bash
   flutter run -d <android-device-id>
   ```
3. Grant notification permissions when prompted.
4. Check debug logs for the confirmation:
   ```
   🔔 Notification permission: AuthorizationStatus.authorized
   ```
5. Send a test message from **Firebase Console → Messaging → New Campaign → Notifications**:
   - Add Title & Text.
   - Click **Send test message** and enter the device's FCM token (or target the package `com.dupay.wallet.com`).
6. Test all 3 states:
   - **Foreground**: In-app banner appears with "View" button.
   - **Background**: Heads-up banner pops up at top of screen with sound; tapping opens the app to `/notification`.
   - **Terminated (Force closed)**: System tray notification appears; tapping launches app through splash to `/notification`.

### B. Testing on iOS
> [!IMPORTANT]
> iOS Push Notifications require a **physical Apple device** (the Simulator does not generate APNs device tokens). Also ensure the APNs Auth Key (.p8) is uploaded to Firebase Console under Project Settings → Cloud Messaging → Apple App Configuration.

1. Connect your physical iPhone.
2. Run the application:
   ```bash
   flutter run --release -d <your-iphone-id>
   ```
3. Grant notification permissions when the iOS system dialog appears.
4. Check device logs:
   ```
   🔔 Notification permission: AuthorizationStatus.authorized
   🍎 APNs token acquired
   ```
5. Send a test message from Firebase Console to test Foreground banner, Background tap, and Terminated launch.
