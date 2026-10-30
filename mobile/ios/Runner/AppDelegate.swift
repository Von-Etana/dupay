import Flutter
import UIKit
import UserNotifications

@main
@objc class AppDelegate: FlutterAppDelegate, FlutterImplicitEngineDelegate {
  override func application(
    _ application: UIApplication,
    didFinishLaunchingWithOptions launchOptions: [UIApplication.LaunchOptionsKey: Any]?
  ) -> Bool {
    // Push notifications (FCM).
    //
    // firebase_messaging asks for permission and registers for remote
    // notifications from Dart (NotificationService.initialize), but iOS needs a
    // UNUserNotificationCenter delegate set before the app finishes launching
    // for foreground banners and notification taps to be delivered.
    // FlutterAppDelegate already conforms to UNUserNotificationCenterDelegate,
    // and the Firebase method swizzling forwards the APNs token from here to
    // FirebaseMessaging automatically.
    if #available(iOS 10.0, *) {
      UNUserNotificationCenter.current().delegate = self
    }
    application.registerForRemoteNotifications()

    return super.application(application, didFinishLaunchingWithOptions: launchOptions)
  }

  func didInitializeImplicitFlutterEngine(_ engineBridge: FlutterImplicitEngineBridge) {
    GeneratedPluginRegistrant.register(with: engineBridge.pluginRegistry)
  }
}
