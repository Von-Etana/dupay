import 'dart:io';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:webview_flutter/webview_flutter.dart';
import 'package:webview_flutter_android/webview_flutter_android.dart';

// ─────────────────────────────────────────────────────────────────────────────
// SETUP: Add these to your pubspec.yaml under dependencies if not already there:
//   webview_flutter: ^4.7.0
//   webview_flutter_android: ^4.0.0
// Then run: flutter pub get
//
// Also add this permission to android/app/src/main/AndroidManifest.xml
// (needed for the native file picker the WebView opens, e.g. camera/gallery):
//   <uses-permission android:name="android.permission.READ_EXTERNAL_STORAGE" />
//   <uses-permission android:name="android.permission.CAMERA" />
// ─────────────────────────────────────────────────────────────────────────────

class LiveChatScreen extends StatefulWidget {
  const LiveChatScreen({super.key});

  @override
  State<LiveChatScreen> createState() => _LiveChatScreenState();
}

class _LiveChatScreenState extends State<LiveChatScreen> {
  late final WebViewController _controller;
  bool _isLoading = true;
  bool _hasError = false;

  // ── Replace the value below with your actual Tawk.to widget URL ──────────
  // Find it in: Tawk.to Dashboard → Administration → Chat Widget → Direct Chat Link
  // It looks like: https://tawk.to/chat/XXXXXXXXX/XXXXXXXXX
  static const String _tawkUrl = 'https://tawk.to/chat/68517a7801479d190ef50a9b/1itv4cqdv';

  @override
  void initState() {
    super.initState();

    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setBackgroundColor(Colors.white)
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (_) => setState(() {
            _isLoading = true;
            _hasError  = false;
          }),
          onPageFinished: (_) => setState(() => _isLoading = false),
          onWebResourceError: (_) => setState(() {
            _isLoading = false;
            _hasError  = true;
          }),
        ),
      )
      ..loadRequest(Uri.parse(_tawkUrl));

    // ── This is the actual fix for "attach file does nothing" ──────────────
    // webview_flutter does not enable HTML <input type="file"> by default on
    // Android. Tawk's own paperclip inside the chat clicks a hidden
    // <input type="file">, which silently no-ops unless the Android platform
    // controller has a file-selector callback registered. Wiring this up
    // makes Tawk's native attach button open the real Android file/camera
    // picker and actually hand the picked file's content back to the page.
    final platform = _controller.platform;
    if (platform is AndroidWebViewController) {
      platform.setOnShowFileSelector(_androidFilePicker);
    }
  }

  /// Called by the Android WebView whenever the page (Tawk) opens a native
  /// `<input type="file">` picker — e.g. when the user taps Tawk's own
  /// paperclip icon inside the chat. Opens the system file picker and
  /// returns the chosen file(s) as `file://` URIs, which is what lets Tawk
  /// actually receive and upload the file.
  Future<List<String>> _androidFilePicker(FileSelectorParams params) async {
    try {
      final allowMultiple = params.mode == FileSelectorMode.openMultiple;
      final result = await FilePicker.platform.pickFiles(
        allowMultiple: allowMultiple,
        type: FileType.any,
      );
      if (result == null) return [];

      return result.files
          .where((f) => f.path != null)
          .map((f) => File(f.path!).uri.toString())
          .toList();
    } catch (_) {
      return [];
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF34544A),
      body: SafeArea(
        bottom: false,
        child: Column(
          children: [
            // ── Header ──────────────────────────────────────────────────────
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 16),
              child: Row(
                children: [
                  IconButton(
                    icon: const Icon(Icons.arrow_back_ios, color: Colors.white),
                    onPressed: () => Navigator.of(context).pop(),
                  ),
                  Expanded(
                    child: Row(
                      children: [
                        Container(
                          width: 38,
                          height: 38,
                          decoration: BoxDecoration(
                            color: Colors.white,
                            shape: BoxShape.circle,
                            border: Border.all(color: Colors.green, width: 2),
                          ),
                          child: const Icon(Icons.support_agent,
                              color: Color(0xFF34544A), size: 20),
                        ),
                        const SizedBox(width: 10),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('Dupay Support',
                                style: TextStyle(
                                    color: Colors.white,
                                    fontSize: 16.sp,
                                    fontWeight: FontWeight.w600)),
                            Row(children: [
                              Container(
                                width: 8,
                                height: 8,
                                decoration: const BoxDecoration(
                                    color: Colors.green,
                                    shape: BoxShape.circle),
                              ),
                              const SizedBox(width: 4),
                              Text('Online',
                                  style: TextStyle(
                                      color: Colors.white70, fontSize: 12.sp)),
                            ]),
                          ],
                        ),
                      ],
                    ),
                  ),
                  // Reload button
                  IconButton(
                    icon: const Icon(Icons.refresh, color: Colors.white),
                    onPressed: () => _controller.reload(),
                  ),
                ],
              ),
            ),

            // ── WebView body ─────────────────────────────────────────────────
            Expanded(
              child: Container(
                decoration: const BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.only(
                    topLeft: Radius.circular(20),
                    topRight: Radius.circular(20),
                  ),
                ),
                child: ClipRRect(
                  borderRadius: const BorderRadius.only(
                    topLeft: Radius.circular(20),
                    topRight: Radius.circular(20),
                  ),
                  child: Stack(
                    children: [
                      if (!_hasError)
                        WebViewWidget(controller: _controller)
                      else
                        _errorView(),

                      if (_isLoading && !_hasError)
                        const Center(
                          child: CircularProgressIndicator(
                              color: Color(0xFF34544A)),
                        ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _errorView() => Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.wifi_off, size: 64, color: Colors.grey[400]),
              const SizedBox(height: 16),
              Text(
                'Could not load support chat',
                style:
                    TextStyle(fontSize: 16.sp, fontWeight: FontWeight.w600),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 8),
              Text(
                'Check your internet connection and try again.',
                style: TextStyle(color: Colors.grey[600], fontSize: 14.sp),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 24),
              ElevatedButton.icon(
                onPressed: () => _controller.reload(),
                icon: const Icon(Icons.refresh),
                label: const Text('Retry'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF34544A),
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12)),
                ),
              ),
            ],
          ),
        ),
      );
}