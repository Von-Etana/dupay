# iOS Push Notifications (FCM) — setup

The app code is wired up. Two things remain that can only be done from your
Apple Developer and Firebase accounts.

## What's already done in this repo

| Item | Where |
| --- | --- |
| Push Notifications entitlement (`aps-environment`) | `ios/Runner/Runner.entitlements` |
| Push + Background Modes capabilities on the Runner target | `ios/Runner.xcodeproj/project.pbxproj` |
| `UIBackgroundModes` = `remote-notification`, `fetch` | `ios/Runner/Info.plist` |
| `UNUserNotificationCenter` delegate + `registerForRemoteNotifications()` | `ios/Runner/AppDelegate.swift` |
| Firebase init on iOS (was Android-only) | `lib/main.dart` |
| APNs-token wait before `getToken()` (fixes the iOS `apns-token-not-set` error) | `lib/services/notifications/notification_service.dart` |

## 1. Add the iOS app to Firebase

1. Firebase console → the same project that Android uses → **Add app → iOS**.
2. Bundle ID: **`com.my.dupay.app.new`** (must match exactly — it's the
   `PRODUCT_BUNDLE_IDENTIFIER` in the Xcode project).
3. Download **`GoogleService-Info.plist`**.
4. Open `ios/Runner.xcworkspace` in Xcode, drag the file onto the **Runner**
   group in the project navigator, and in the dialog tick
   **Copy items if needed** and **Target: Runner**.
   The file must end up at `ios/Runner/GoogleService-Info.plist` *and* appear
   under Runner → Build Phases → Copy Bundle Resources.

Without this file `Firebase.initializeApp()` throws on iOS. It's caught, so the
app still runs — you'll see `⚠️ Firebase/FCM init skipped` in the logs and push
simply won't work.

## 2. Upload an APNs auth key to Firebase

1. [developer.apple.com](https://developer.apple.com) → Certificates, Identifiers
   & Profiles → **Keys → +** → tick **Apple Push Notifications service (APNs)**
   → Continue → Register → **Download** the `.p8`. Apple lets you download it
   **once** — keep it safe.
2. Note the **Key ID** (on that page) and your **Team ID**
   (this project signs with `VDFCU7TD4W`).
3. Firebase console → **Project settings → Cloud Messaging → Apple app
   configuration → APNs Authentication Key → Upload**, and supply the `.p8`,
   the Key ID, and the Team ID.

One key covers development and production, and all your apps on that team.

## 3. Check the App ID has Push enabled

In Certificates, Identifiers & Profiles → **Identifiers** → `com.my.dupay.app.new`,
make sure **Push Notifications** is ticked. If you use automatic signing, Xcode
does this for you the first time it builds after the capability was added —
just let it regenerate the provisioning profile.

## 4. Build and test

```bash
flutter clean && flutter pub get && cd ios && pod install && cd ..
flutter run --release -d <your-iphone>
```

Push requires a **physical device** (the Simulator only handles remote pushes
on iOS 16+ via a paired Mac, and never produces a real APNs token).

On launch, check for notification permission and an APNs token in the device logs. The app does not print the FCM token. To test delivery, use a device token obtained through a secure debug workflow and do not paste it into logs or tickets.

```
🔔 Notification permission: AuthorizationStatus.authorized
🍎 APNs token acquired
```

Copy that FCM token and send a test message from
**Firebase console → Messaging → Send test message**.

## Notes

- `aps-environment` is `development` in `Runner.entitlements`. Xcode rewrites it
  to `production` when you archive and export with a distribution profile, so
  there's nothing to change for TestFlight/App Store builds.
- The FCM token is sent to your backend on login/register
  (`NotificationService.fcmToken`) and again via `onTokenChanged` whenever it
  rotates — no iOS-specific backend work is needed; the server sends to the same
  FCM endpoint for both platforms.
- Foreground notifications now show an in-app banner. Notification tap routing
  to a specific transaction is still pending and needs a reviewed payload
  contract from the server.
