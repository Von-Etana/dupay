// lib/screen/pos/pos_request_screen.dart
// Shows existing POS application status OR the application form.

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import '../../providers/pos_provider.dart';
import '../../providers/auth_provider.dart';
import '../../core/utils/formatters.dart';
import '../../models/pos/pos_request_model.dart';

class PosRequestScreen extends ConsumerStatefulWidget {
  const PosRequestScreen({Key? key}) : super(key: key);

  @override
  ConsumerState<PosRequestScreen> createState() => _PosRequestScreenState();
}

class _PosRequestScreenState extends ConsumerState<PosRequestScreen> {
  final _businessAddressCtrl    = TextEditingController();
  final _confirmAddressCtrl     = TextEditingController();
  final _terminalCountCtrl      = TextEditingController();
  final _transactionVolumeCtrl  = TextEditingController();

  @override
  void initState() {
    super.initState();
    // Always fetch on open so we know if a request already exists
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(posNotifierProvider.notifier).getPosRequest();
    });
  }

  @override
  void dispose() {
    _businessAddressCtrl.dispose();
    _confirmAddressCtrl.dispose();
    _terminalCountCtrl.dispose();
    _transactionVolumeCtrl.dispose();
    super.dispose();
  }

  // ── Submit new request ────────────────────────────────────────────────────
  Future<void> _submit() async {
    if (_businessAddressCtrl.text.isEmpty ||
        _confirmAddressCtrl.text.isEmpty ||
        _terminalCountCtrl.text.isEmpty ||
        _transactionVolumeCtrl.text.isEmpty) {
      _snack('Please fill all fields', Colors.red);
      return;
    }
    if (_businessAddressCtrl.text != _confirmAddressCtrl.text) {
      _snack('Business addresses do not match', Colors.red);
      return;
    }

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(
          child: CircularProgressIndicator(color: Color(0xFF34544A))),
    );

    final response = await ref.read(posNotifierProvider.notifier).submitPosRequest(
          businessAddress:   _businessAddressCtrl.text,
          numberOfTerminal:  _terminalCountCtrl.text,
          transactionVolume: _transactionVolumeCtrl.text,
        );

    if (!mounted) return;
    Navigator.of(context).pop(); // close loader

    if (response.success) {
      _snack('POS request submitted successfully!', Colors.green);
    } else {
      _snack(response.message.isNotEmpty ? response.message : 'Request failed',
          Colors.red);
    }
  }

  void _snack(String msg, Color color) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(msg), backgroundColor: color),
    );
  }

  // ── Build ─────────────────────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    final posState = ref.watch(posNotifierProvider);
    final user     = ref.watch(currentUserProvider);

    return Scaffold(
      resizeToAvoidBottomInset: true,
      backgroundColor: const Color(0xFF34544A),
      body: SafeArea(
        bottom: false,
        child: Column(
          children: [
            const SizedBox(height: 30),
            // ── Header ──
            Row(
              children: [
                IconButton(
                  icon: const Icon(Icons.arrow_back_ios, color: Colors.white),
                  onPressed: () => Navigator.of(context).pop(),
                ),
                Expanded(
                  child: Text(
                    'POS Terminal Request',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 22.sp,
                      fontWeight: FontWeight.w600,
                      color: Colors.white,
                    ),
                  ),
                ),
                const SizedBox(width: 48),
              ],
            ),
            const SizedBox(height: 20),
            // ── Content ──
            Expanded(
              child: Container(
                decoration: const BoxDecoration(
                  color: Color(0xFF7CA183),
                  borderRadius: BorderRadius.only(
                    topLeft: Radius.circular(20),
                    topRight: Radius.circular(20),
                  ),
                ),
                child: Padding(
                  padding: const EdgeInsets.only(top: 20),
                  child: Container(
                    decoration: const BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.only(
                        topLeft: Radius.circular(20),
                        topRight: Radius.circular(20),
                      ),
                    ),
                    child: posState.isLoading && !posState.hasChecked
                        ? _loadingView()
                        : posState.currentPosRequest != null
                            ? _existingRequestView(
                                posState.currentPosRequest!, user?.balance)
                            : _applicationForm(user?.balance),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ── Loading ───────────────────────────────────────────────────────────────
  Widget _loadingView() => const Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            CircularProgressIndicator(color: Color(0xFF34544A)),
            SizedBox(height: 16),
            Text('Checking your POS application...'),
          ],
        ),
      );

  // ── Existing request status ───────────────────────────────────────────────
  Widget _existingRequestView(PosRequestModel req, String? balance) {
    final statusMap = {
      '0': {'label': 'Pending Review',    'color': Colors.orange,           'icon': Icons.hourglass_top},
      '1': {'label': 'Approved',          'color': const Color(0xFF1DBF73), 'icon': Icons.check_circle},
      '2': {'label': 'Rejected',          'color': Colors.red,              'icon': Icons.cancel},
      '3': {'label': 'In Processing',     'color': Colors.blue,             'icon': Icons.sync},
      '4': {'label': 'Terminal Deployed', 'color': const Color(0xFF34544A), 'icon': Icons.point_of_sale},
    };
    final info  = statusMap[req.status] ?? statusMap['0']!;
    final color = info['color'] as Color;
    final icon  = info['icon'] as IconData;
    final label = info['label'] as String;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Balance row
          Row(
            mainAxisAlignment: MainAxisAlignment.end,
            children: [
              Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
                Text(
                  formatCurrency(balance),
                  style: TextStyle(
                    fontSize: 22.sp,
                    color: const Color(0xFF5B7B5B),
                    fontWeight: FontWeight.bold,
                  ),
                ),
                Text('Current Balance',
                    style: TextStyle(
                        fontSize: 13.sp, color: const Color(0xFF7CA183))),
              ]),
            ],
          ),
          const SizedBox(height: 24),

          // Status card
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: color.withOpacity(0.08),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: color.withOpacity(0.3)),
            ),
            child: Column(
              children: [
                Icon(icon, color: color, size: 56),
                const SizedBox(height: 12),
                Text(
                  label,
                  style: TextStyle(
                    color: color,
                    fontWeight: FontWeight.bold,
                    fontSize: 20.sp,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  _statusMessage(req.status),
                  textAlign: TextAlign.center,
                  style:
                      TextStyle(color: Colors.grey[600], fontSize: 14.sp),
                ),
                if (req.comment != null && req.comment!.isNotEmpty) ...[
                  const SizedBox(height: 12),
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.grey[100],
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      'Note: ${req.comment}',
                      style: TextStyle(
                          color: Colors.grey[700], fontSize: 13.sp),
                    ),
                  ),
                ],
              ],
            ),
          ),
          const SizedBox(height: 24),

          // Application details
          Text('Application Details',
              style: TextStyle(
                  fontWeight: FontWeight.bold, fontSize: 16.sp)),
          const SizedBox(height: 12),
          _DetailCard(children: [
            _DetailRow('Business Address', req.businessAddress),
            _DetailRow('Terminals Requested', req.numberOfTerminal),
            _DetailRow('Monthly Volume', req.transactionVolume),
            if (req.createdAt != null)
              _DetailRow('Submitted On', req.createdAt!.split('T').first),
          ]),
          const SizedBox(height: 24),

          // Allow resubmit only if rejected
          if (req.status == '2') ...[
            const Divider(),
            const SizedBox(height: 12),
            Text('Your application was rejected. You can submit a new one.',
                style:
                    TextStyle(color: Colors.grey[600], fontSize: 14.sp)),
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF34544A),
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12)),
                ),
                onPressed: () => ref
                    .read(posNotifierProvider.notifier)
                    .clearError(), // will fall back to form
                child: const Text('Submit New Application',
                    style: TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                        fontSize: 16)),
              ),
            ),
          ],
        ],
      ),
    );
  }

  String _statusMessage(String status) {
    switch (status) {
      case '0':
        return 'Your application is under review. We\'ll notify you within 3–5 working days.';
      case '1':
        return 'Your POS terminal request has been approved. Expect delivery soon.';
      case '2':
        return 'Unfortunately your application was not approved this time.';
      case '3':
        return 'Your terminal is being processed and will be dispatched shortly.';
      case '4':
        return 'Your POS terminal has been deployed. Check your delivery address.';
      default:
        return 'Your application is being processed.';
    }
  }

  // ── Application form (no existing request) ────────────────────────────────
  Widget _applicationForm(String? balance) {
    return SingleChildScrollView(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Balance
          Row(mainAxisAlignment: MainAxisAlignment.end, children: [
            Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
              Text(
                formatCurrency(balance),
                style: TextStyle(
                  fontSize: 22.sp,
                  color: const Color(0xFF5B7B5B),
                  fontWeight: FontWeight.bold,
                ),
              ),
              Text('Current Balance',
                  style: TextStyle(
                      fontSize: 13.sp, color: const Color(0xFF7CA183))),
            ]),
          ]),
          const SizedBox(height: 20),
          Text(
            'Apply for a POS Terminal',
            style:
                TextStyle(fontSize: 18.sp, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 6),
          Text(
            'Fill in the details below to apply for a POS terminal.',
            style: TextStyle(fontSize: 13.sp, color: Colors.grey[600]),
          ),
          const SizedBox(height: 20),
          _FormCard(children: [
            _FieldLabel('Business Address'),
            _StyledTextField(
              hint: 'Enter your business address',
              controller: _businessAddressCtrl,
              textInputAction: TextInputAction.next,
            ),
            const SizedBox(height: 14),
            _FieldLabel('Confirm Business Address'),
            _StyledTextField(
              hint: 'Confirm your business address',
              controller: _confirmAddressCtrl,
              textInputAction: TextInputAction.next,
            ),
            const SizedBox(height: 14),
            _FieldLabel('Number of Terminals Required'),
            _StyledTextField(
              hint: 'e.g. 2',
              controller: _terminalCountCtrl,
              keyboardType: TextInputType.number,
              textInputAction: TextInputAction.next,
            ),
            const SizedBox(height: 14),
            _FieldLabel('Expected Transaction Volume / Month'),
            _StyledTextField(
              hint: 'e.g. 500000',
              controller: _transactionVolumeCtrl,
              keyboardType: TextInputType.number,
              textInputAction: TextInputAction.done,
            ),
          ]),
          const SizedBox(height: 28),
          SizedBox(
            width: double.infinity,
            height: 50,
            child: ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF34544A),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(32)),
                padding: const EdgeInsets.symmetric(vertical: 14),
              ),
              onPressed: _submit,
              child: Text('Submit Application',
                  style: TextStyle(
                      fontSize: 18.sp,
                      fontWeight: FontWeight.bold,
                      color: Colors.white)),
            ),
          ),
        ],
      ),
    );
  }
}

// ─── Helper widgets ───────────────────────────────────────────────────────────

class _FieldLabel extends StatelessWidget {
  final String text;
  const _FieldLabel(this.text);
  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.only(bottom: 6),
        child: Text(text,
            style: TextStyle(
                fontSize: 13.sp,
                color: Colors.grey[600],
                fontWeight: FontWeight.w500)),
      );
}

class _FormCard extends StatelessWidget {
  final List<Widget> children;
  const _FormCard({required this.children});
  @override
  Widget build(BuildContext context) => Container(
        padding:
            const EdgeInsets.symmetric(vertical: 18, horizontal: 12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
                color: Colors.grey.withOpacity(0.08),
                blurRadius: 8,
                offset: const Offset(0, 2))
          ],
        ),
        child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: children),
      );
}

class _DetailCard extends StatelessWidget {
  final List<Widget> children;
  const _DetailCard({required this.children});
  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: const Color(0xFFF8FAF9),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(children: children),
      );
}

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;
  const _DetailRow(this.label, this.value);
  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 6),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(label,
                style: TextStyle(
                    color: Colors.grey[600], fontSize: 14.sp)),
            Flexible(
              child: Text(value,
                  textAlign: TextAlign.right,
                  style: const TextStyle(
                      fontWeight: FontWeight.w600, fontSize: 14)),
            ),
          ],
        ),
      );
}

class _StyledTextField extends StatelessWidget {
  final String hint;
  final TextEditingController? controller;
  final TextInputType? keyboardType;
  final TextInputAction? textInputAction;

  const _StyledTextField({
    required this.hint,
    this.controller,
    this.keyboardType,
    this.textInputAction,
  });

  @override
  Widget build(BuildContext context) => TextField(
        controller: controller,
        keyboardType: keyboardType,
        textInputAction: textInputAction,
        decoration: InputDecoration(
          hintText: hint,
          hintStyle:
              TextStyle(color: Colors.grey[400], fontSize: 14.sp),
          filled: true,
          fillColor: const Color(0xFFF8FAF9),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide.none,
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide.none,
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide:
                const BorderSide(color: Color(0xFF34544A), width: 1.5),
          ),
          contentPadding:
              EdgeInsets.symmetric(vertical: 16.h, horizontal: 16.w),
        ),
      );
}
