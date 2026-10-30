import 'dart:async';

/// Signals that the server rejected the current session.
class SessionEvents {
  SessionEvents._();

  static final StreamController<void> _expired =
      StreamController<void>.broadcast(sync: true);

  static Stream<void> get expired => _expired.stream;

  static void notifyExpired() => _expired.add(null);
}
