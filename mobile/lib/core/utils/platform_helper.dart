import 'dart:io' show Platform;

/// Small platform helper used to guard native-only features (contacts,
/// camera/QR scanner) that crash on the iOS Simulator.
///
/// The iOS Simulator has no real camera and its Contacts framework can hard
/// -crash the `flutter_contacts` plugin. Those crashes happen at the native
/// layer, so a Dart `try/catch` cannot recover from them — the only safe fix
/// is to detect the simulator and skip the native call entirely.
///
/// On a real device every getter below returns `false`, so behaviour there is
/// completely unchanged.
class PlatformHelper {
  PlatformHelper._();

  /// `true` only when running on the iOS Simulator.
  ///
  /// iOS sets the `SIMULATOR_DEVICE_NAME` environment variable inside the
  /// simulator but never on a physical device, so this is a reliable,
  /// dependency-free check.
  static bool get isIOSSimulator {
    try {
      return Platform.isIOS &&
          Platform.environment.containsKey('SIMULATOR_DEVICE_NAME');
    } catch (_) {
      // Platform isn't available (e.g. web) — treat as not a simulator.
      return false;
    }
  }

  /// `true` on the iOS or Android emulator/simulator where the camera and
  /// contacts hardware are typically unavailable.
  static bool get isMobileSimulator => isIOSSimulator;
}