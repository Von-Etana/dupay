// lib/screen/qr_code/scan_qrcode.dart

import 'dart:async';
import 'dart:io';
import 'dart:typed_data';
import 'dart:ui' as ui;

import 'package:flutter/material.dart';
import 'package:flutter/rendering.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import 'package:path_provider/path_provider.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'package:share_plus/share_plus.dart';
import '../../core/utils/platform_helper.dart';
import '../../providers/auth_provider.dart';
import '../../core/network/api_service.dart';
import '../send_money/p2p_screen.dart';

class ScanQrcodeScreen extends ConsumerStatefulWidget {
  const ScanQrcodeScreen({super.key});

  @override
  ConsumerState<ScanQrcodeScreen> createState() => _ScanQrcodeScreenState();
}

class _ScanQrcodeScreenState extends ConsumerState<ScanQrcodeScreen> {
  int _tab = 0; // 0 = My QR, 1 = Scan

  @override
  Widget build(BuildContext context) {
    final user        = ref.watch(currentUserProvider);
    final username    = user?.profile?.username ?? '';
    final phone       = user?.phoneNumber ?? '';
    final qrData      = username.isNotEmpty ? username : phone;
    final displayName = user?.profile?.displayName ?? user?.email ?? '';

    return Scaffold(
      backgroundColor: const Color(0xFF34544A),
      body: SafeArea(
        bottom: false,
        child: Column(children: [
          const SizedBox(height: 30),
          Row(children: [
            IconButton(
              icon: const Icon(Icons.arrow_back_ios, color: Colors.white),
              onPressed: () => Navigator.of(context).pop(),
            ),
            Expanded(
              child: Text('QR Code',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                      fontSize: 20.sp,
                      fontWeight: FontWeight.bold,
                      color: Colors.white)),
            ),
            const SizedBox(width: 48),
          ]),
          const SizedBox(height: 16),

          // ── Tab switcher ──────────────────────────────────────────────
          Container(
            margin: const EdgeInsets.symmetric(horizontal: 24),
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(0.15),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(children: [
              _TabBtn(
                label: 'My QR Code',
                icon: Icons.qr_code,
                selected: _tab == 0,
                onTap: () => setState(() => _tab = 0),
              ),
              _TabBtn(
                label: 'Scan & Send',
                icon: Icons.qr_code_scanner,
                selected: _tab == 1,
                onTap: () => setState(() => _tab = 1),
              ),
            ]),
          ),
          const SizedBox(height: 20),

          Expanded(
            child: Container(
              decoration: const BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.only(
                      topLeft: Radius.circular(20),
                      topRight: Radius.circular(20))),
              child: _tab == 0
                  ? _MyQrView(
                      qrData: qrData,
                      displayName: displayName,
                      username: username,
                    )
                  : _ScannerView(onScanned: _onScanned),
            ),
          ),
        ]),
      ),
    );
  }

  Future<void> _onScanned(String value) async {
    final query = value.trim().replaceAll('@', '');
    if (query.isEmpty) return;

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(
          child: CircularProgressIndicator(color: Color(0xFF34544A))),
    );

    try {
      final response = await ApiService.instance.get<Map<String, dynamic>>(
        '/find-user/$query',
        fromJson: (j) => j,
      );

      if (!mounted) return;
      Navigator.of(context).pop();

      if (response.success && response.data != null) {
        final data   = response.data!['data'] ?? response.data!;
        final userId = data['id']?.toString() ?? '';
        final uname  = data['username']?.toString() ?? query;
        final name   = data['name']?.toString() ?? uname;

        if (userId.isEmpty) {
          _snack('User not found');
          return;
        }

        setState(() => _tab = 0);
        Navigator.of(context).push(MaterialPageRoute(
          builder: (_) => P2PScreen(
            preselectedUser: {
              'id':        userId,
              'wallet_id': userId,
              'name':      name,
              'username':  uname,
            },
          ),
        ));
      } else {
        _snack('User "@$query" not found on Dupay');
      }
    } catch (e) {
      if (mounted) {
        Navigator.of(context).pop();
        _snack('Could not find user');
      }
    }
  }

  void _snack(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(msg),
      backgroundColor: Colors.red,
    ));
  }
}

// ─── My QR View ───────────────────────────────────────────────────────────────
class _MyQrView extends StatefulWidget {
  final String qrData;
  final String displayName;
  final String username;

  const _MyQrView({
    required this.qrData,
    required this.displayName,
    required this.username,
  });

  @override
  State<_MyQrView> createState() => _MyQrViewState();
}

class _MyQrViewState extends State<_MyQrView> {
  final GlobalKey _qrKey = GlobalKey();
  bool _sharing = false;

  Future<void> _shareQrCode() async {
    if (_sharing || widget.qrData.isEmpty) return;
    setState(() => _sharing = true);

    // Make sure the QR card has been painted at least once before capture.
    await WidgetsBinding.instance.endOfFrame;

    BuildContext? loaderCtx;
    if (mounted) {
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (ctx) {
          loaderCtx = ctx;
          return const Center(
            child: CircularProgressIndicator(color: Color(0xFF34544A)),
          );
        },
      );
    }

    void dismissLoader() {
      if (loaderCtx != null && Navigator.canPop(loaderCtx!)) {
        Navigator.of(loaderCtx!).pop();
      }
      loaderCtx = null;
    }

    try {
      final boundaryContext = _qrKey.currentContext;
      if (boundaryContext == null) {
        throw StateError('QR not ready');
      }
      final boundary =
          boundaryContext.findRenderObject() as RenderRepaintBoundary;

      final ui.Image image = await boundary.toImage(pixelRatio: 3.0);
      final byteData =
          await image.toByteData(format: ui.ImageByteFormat.png);
      if (byteData == null) {
        throw StateError('Failed to encode QR image');
      }
      final Uint8List pngBytes = byteData.buffer.asUint8List();

      final directory = await getTemporaryDirectory();
      final file = File(
        '${directory.path}/dupay_qr_${DateTime.now().millisecondsSinceEpoch}.png',
      );
      await file.writeAsBytes(pngBytes);

      // iOS needs an anchor rect for the share-sheet popover, or the sheet
      // silently fails to appear. Capture it before dismissing the loader,
      // while the widget is still laid out. Android ignores this parameter.
      final box = context.findRenderObject() as RenderBox?;
      final Rect sharePosition = box != null
          ? box.localToGlobal(Offset.zero) & box.size
          : Rect.fromLTWH(0, 0, MediaQuery.of(context).size.width,
              MediaQuery.of(context).size.height / 2);

      dismissLoader();
      // Let the loader finish dismissing before presenting the share sheet,
      // otherwise iOS refuses to present while a dismissal is in progress.
      await Future.delayed(const Duration(milliseconds: 250));

      final handle =
          widget.username.isNotEmpty ? '@${widget.username}' : widget.qrData;
      await SharePlus.instance.share(
        ShareParams(
          files: [
            XFile(file.path, mimeType: 'image/png', name: 'dupay_qr.png'),
          ],
          text:
              'Scan my Dupay QR code to send me money instantly.\nHandle: $handle',
          subject: 'My Dupay QR Code',
          sharePositionOrigin: sharePosition,
        ),
      );
    } catch (_) {
      dismissLoader();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Could not share QR code. Please try again.'),
            backgroundColor: Colors.red,
            duration: Duration(seconds: 2),
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _sharing = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final qrData      = widget.qrData;
    final displayName = widget.displayName;
    final username    = widget.username;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Column(children: [
        const SizedBox(height: 8),
        Text('Your Dupay QR Code',
            style: TextStyle(
                fontSize: 18.sp,
                fontWeight: FontWeight.bold,
                color: Colors.grey[800])),
        const SizedBox(height: 4),
        Text(
          'Others scan this to send you money instantly',
          style: TextStyle(fontSize: 13.sp, color: Colors.grey[500]),
          textAlign: TextAlign.center,
        ),
        const SizedBox(height: 28),

        if (qrData.isNotEmpty)
          // RepaintBoundary lets us capture *just* the QR card (logo +
          // QR + name + handle) as a PNG to share — without the surrounding
          // page chrome, header, or tab switcher.
          RepaintBoundary(
            key: _qrKey,
            child: Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(20),
                boxShadow: [
                  BoxShadow(
                      color: Colors.black.withOpacity(0.08),
                      blurRadius: 20,
                      offset: const Offset(0, 4)),
                ],
                border: Border.all(
                    color: const Color(0xFF34544A).withOpacity(0.2),
                    width: 2),
              ),
              child: Column(children: [
                // ── Real scannable QR code ──────────────────────────
                QrImageView(
                  data: qrData,
                  version: QrVersions.auto,
                  size: 220,
                  backgroundColor: Colors.white,
                  eyeStyle: const QrEyeStyle(
                    eyeShape: QrEyeShape.square,
                    color: Color(0xFF34544A),
                  ),
                  dataModuleStyle: const QrDataModuleStyle(
                    dataModuleShape: QrDataModuleShape.square,
                    color: Color(0xFF34544A),
                  ),
                ),
                const SizedBox(height: 12),
                Text(displayName,
                    style: const TextStyle(
                        fontWeight: FontWeight.bold, fontSize: 16)),
                if (username.isNotEmpty)
                  Text('@$username',
                      style: TextStyle(
                          color: Colors.grey[500], fontSize: 13)),
              ]),
            ),
          )
        else
          Container(
            height: 220,
            alignment: Alignment.center,
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.person_off_outlined,
                    size: 56, color: Colors.grey[300]),
                const SizedBox(height: 12),
                Text(
                  'No username set.\nPlease update your profile first.',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                      color: Colors.grey[500], fontSize: 14),
                ),
              ],
            ),
          ),

        const SizedBox(height: 20),

        // ── Share QR Code button (replaces the old "Copy @username") ───
        if (qrData.isNotEmpty)
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: _sharing ? null : _shareQrCode,
              icon: _sharing
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(
                          color: Colors.white, strokeWidth: 2),
                    )
                  : const Icon(Icons.share_rounded, color: Colors.white),
              label: Text(
                _sharing ? 'Preparing…' : 'Share QR Code',
                style: const TextStyle(
                    color: Colors.white, fontWeight: FontWeight.bold),
              ),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF34544A),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(32)),
                padding: const EdgeInsets.symmetric(vertical: 14),
              ),
            ),
          ),
        const SizedBox(height: 24),
      ]),
    );
  }
}

// ─── Scanner View ─────────────────────────────────────────────────────────────
class _ScannerView extends StatefulWidget {
  final Future<void> Function(String value) onScanned;
  const _ScannerView({required this.onScanned});

  @override
  State<_ScannerView> createState() => _ScannerViewState();
}

class _ScannerViewState extends State<_ScannerView>
    with WidgetsBindingObserver {
  // On the iOS Simulator there is no camera, and creating/attaching a
  // MobileScannerController crashes the app. Only create it on a real device.
  //
  // autoStart is disabled so the widget never starts the camera on its own.
  // We start it exactly once (after the first frame) and manage the rest of
  // the lifecycle by hand. This is the documented fix for the
  // "MobileScannerController is already running / already started" error that
  // App Review hit on iPad.
  final MobileScannerController? _ctrl = PlatformHelper.isIOSSimulator
      ? null
      : MobileScannerController(autoStart: false);
  bool _scanned = false;
  bool _torchOn = false;

  @override
  void initState() {
    super.initState();
    final ctrl = _ctrl;
    if (ctrl == null) return;

    WidgetsBinding.instance.addObserver(this);
    // Start once, after the first frame, so the camera preview is mounted
    // before we attach the camera. Starting too early can race on iPad.
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) unawaited(ctrl.start());
    });
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    final ctrl = _ctrl;
    if (ctrl == null) return;

    // Don't touch the controller before it has camera permission — the
    // permission dialog itself triggers lifecycle changes that would
    // otherwise cause a start/stop race.
    if (!ctrl.value.hasCameraPermission) return;

    switch (state) {
      case AppLifecycleState.detached:
      case AppLifecycleState.hidden:
      case AppLifecycleState.paused:
        return;
      case AppLifecycleState.resumed:
        // Only resume scanning if the user hasn't already captured a code.
        if (!_scanned) unawaited(ctrl.start());
      case AppLifecycleState.inactive:
        unawaited(ctrl.stop());
    }
  }

  @override
  Future<void> dispose() async {
    if (_ctrl != null) {
      WidgetsBinding.instance.removeObserver(this);
    }
    super.dispose();
    await _ctrl?.dispose();
  }

  @override
  Widget build(BuildContext context) {
    // No camera on the simulator → show a friendly placeholder instead of
    // crashing. The rest of the QR screen (My QR Code tab) still works.
    if (_ctrl == null) {
      return Padding(
        padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 40),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.no_photography_outlined,
                size: 64, color: Colors.grey[400]),
            const SizedBox(height: 16),
            Text('Camera not available',
                style: TextStyle(
                    fontSize: 18.sp,
                    fontWeight: FontWeight.bold,
                    color: Colors.grey[800])),
            const SizedBox(height: 8),
            Text(
              'QR scanning needs a real camera, which the iOS Simulator '
              'doesn\'t have. Run the app on a physical device to scan codes.',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 13.sp, color: Colors.grey[500]),
            ),
          ],
        ),
      );
    }

    return Column(children: [
      const SizedBox(height: 16),
      Text('Scan Dupay QR Code',
          style: TextStyle(
              fontSize: 18.sp,
              fontWeight: FontWeight.bold,
              color: Colors.grey[800])),
      const SizedBox(height: 4),
      Text(
        'Point your camera at a Dupay QR code',
        style: TextStyle(fontSize: 13.sp, color: Colors.grey[500]),
        textAlign: TextAlign.center,
      ),
      const SizedBox(height: 16),

      // ── Camera ──────────────────────────────────────────────────
      Expanded(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(20),
            child: Stack(children: [
              MobileScanner(
                controller: _ctrl!,
                onDetect: (capture) {
                  if (_scanned) return;
                  final raw = capture.barcodes.firstOrNull?.rawValue;
                  if (raw == null || raw.isEmpty) return;
                  _scanned = true;
                  _ctrl?.stop();
                  widget.onScanned(raw);
                },
              ),

              // Dark overlay with transparent centre
              ColorFiltered(
                colorFilter: ColorFilter.mode(
                    Colors.black.withOpacity(0.45),
                    BlendMode.srcOut),
                child: Stack(children: [
                  Container(
                      decoration: const BoxDecoration(
                          color: Colors.black,
                          backgroundBlendMode: BlendMode.dstOut)),
                  Center(
                    child: Container(
                      width: 220,
                      height: 220,
                      decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(16)),
                    ),
                  ),
                ]),
              ),

              // Corner brackets
              Center(
                child: SizedBox(
                  width: 220, height: 220,
                  child: Stack(children: [
                    for (final pos in [
                      [0.0, 0.0, true,  true,  false, false],
                      [1.0, 0.0, false, true,  true,  false],
                      [0.0, 1.0, true,  false, false, true],
                      [1.0, 1.0, false, false, true,  true],
                    ])
                      Align(
                        alignment: Alignment(
                            (pos[0] as double) * 2 - 1,
                            (pos[1] as double) * 2 - 1),
                        child: Container(
                          width: 28, height: 28,
                          decoration: BoxDecoration(
                            border: Border(
                              left: pos[2] as bool
                                  ? const BorderSide(
                                      color: Color(0xFF34544A),
                                      width: 4)
                                  : BorderSide.none,
                              right: pos[4] as bool
                                  ? const BorderSide(
                                      color: Color(0xFF34544A),
                                      width: 4)
                                  : BorderSide.none,
                              top: pos[3] as bool
                                  ? const BorderSide(
                                      color: Color(0xFF34544A),
                                      width: 4)
                                  : BorderSide.none,
                              bottom: pos[5] as bool
                                  ? const BorderSide(
                                      color: Color(0xFF34544A),
                                      width: 4)
                                  : BorderSide.none,
                            ),
                          ),
                        ),
                      ),
                  ]),
                ),
              ),

              // Scan again button (after first scan)
              if (_scanned)
                Center(
                  child: ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF34544A)),
                    onPressed: () {
                      setState(() => _scanned = false);
                      _ctrl?.start();
                    },
                    icon: const Icon(Icons.refresh,
                        color: Colors.white),
                    label: const Text('Scan Again',
                        style: TextStyle(color: Colors.white)),
                  ),
                ),
            ]),
          ),
        ),
      ),

      // ── Controls ─────────────────────────────────────────────────
      Padding(
        padding: const EdgeInsets.symmetric(
            vertical: 16, horizontal: 40),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            _ControlBtn(
              icon: _torchOn
                  ? Icons.flashlight_on_rounded
                  : Icons.flashlight_off_rounded,
              label: 'Flash',
              onTap: () {
                _ctrl?.toggleTorch();
                setState(() => _torchOn = !_torchOn);
              },
            ),
          ],
        ),
      ),
    ]);
  }
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

class _TabBtn extends StatelessWidget {
  final String label;
  final IconData icon;
  final bool selected;
  final VoidCallback onTap;
  const _TabBtn({
    required this.label,
    required this.icon,
    required this.selected,
    required this.onTap,
  });
  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 200),
          margin: const EdgeInsets.all(4),
          padding: const EdgeInsets.symmetric(vertical: 10),
          decoration: BoxDecoration(
            color: selected ? Colors.white : Colors.transparent,
            borderRadius: BorderRadius.circular(10),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(icon,
                  size: 16,
                  color: selected
                      ? const Color(0xFF34544A)
                      : Colors.white),
              const SizedBox(width: 6),
              Text(label,
                  style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.bold,
                      color: selected
                          ? const Color(0xFF34544A)
                          : Colors.white)),
            ],
          ),
        ),
      ),
    );
  }
}

class _ControlBtn extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onTap;
  const _ControlBtn({
    required this.icon,
    required this.label,
    required this.onTap,
  });
  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Column(children: [
        Container(
          width: 52, height: 52,
          decoration: BoxDecoration(
            color: const Color(0xFF34544A).withOpacity(0.1),
            shape: BoxShape.circle,
            border: Border.all(
                color: const Color(0xFF34544A).withOpacity(0.3)),
          ),
          child: Icon(icon, color: const Color(0xFF34544A), size: 24),
        ),
        const SizedBox(height: 4),
        Text(label,
            style: TextStyle(
                fontSize: 12, color: Colors.grey[600])),
      ]),
    );
  }
}