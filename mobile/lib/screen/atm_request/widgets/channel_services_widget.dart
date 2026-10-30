// lib/screen/atm_request/widgets/channel_services_widget.dart

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import '../../../providers/physical_card_provider.dart';
import '../../../widgets/pin_entry_screen.dart';

class ChannelServicesWidget extends ConsumerStatefulWidget {
  final PhysicalCardModel card;
  const ChannelServicesWidget({super.key, required this.card});

  @override
  ConsumerState<ChannelServicesWidget> createState() =>
      _ChannelServicesWidgetState();
}

class _ChannelServicesWidgetState
    extends ConsumerState<ChannelServicesWidget> {
  late bool _atm;
  late bool _pos;
  late bool _web;
  late bool _mobile;

  @override
  void initState() {
    super.initState();
    _atm    = widget.card.atm;
    _pos    = widget.card.pos;
    _web    = widget.card.web;
    _mobile = widget.card.mobile;
  }

  Future<void> _saveChanges() async {
    final pin = await showPinEntry(
      context,
      title: 'Update Card Channels',
      subtitle: 'Enter your transaction PIN to save these changes.',
    );

    if (pin == null || pin.length < 4) return;

    if (!mounted) return;
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(
          child: CircularProgressIndicator(color: Color(0xFF34544A))),
    );

    final response = await ref
        .read(physicalCardNotifierProvider.notifier)
        .updateChannels(
          atm:            _atm,
          pos:            _pos,
          web:            _web,
          mobile:         _mobile,
          transactionPin: pin,
        );

    if (!mounted) return;
    Navigator.of(context).pop(); // close loader

    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(response.success
          ? 'Channel settings updated!'
          : (response.message.isNotEmpty ? response.message : 'Update failed')),
      backgroundColor: response.success ? Colors.green : Colors.red,
    ));

    if (response.success) Navigator.of(context).pop();
  }

  @override
  Widget build(BuildContext context) {
    final channels = [
      {'label': 'Mobile Payments', 'value': _mobile, 'key': 'mobile'},
      {'label': 'Web Payments',    'value': _web,    'key': 'web'},
      {'label': 'ATM Withdrawals', 'value': _atm,    'key': 'atm'},
      {'label': 'POS Payments',    'value': _pos,    'key': 'pos'},
    ];

    return Scaffold(
      backgroundColor: const Color(0xFFF4F7FA),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios, color: Colors.black),
          onPressed: () => Navigator.of(context).pop(),
        ),
        title: Text('Channel Services',
            style: TextStyle(
                color: Colors.black,
                fontSize: 20.sp,
                fontWeight: FontWeight.w600)),
        centerTitle: true,
      ),
      body: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24.0, vertical: 18),
          child: Text('Manage Card Channels',
              style:
                  TextStyle(fontSize: 24.sp, fontWeight: FontWeight.bold)),
        ),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24.0),
          child: Text('Enable or disable where this card can be used',
              style: TextStyle(fontSize: 15.sp, color: Colors.grey[700])),
        ),
        const SizedBox(height: 24),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 12.0),
          child: Container(
            decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                boxShadow: [
                  BoxShadow(
                      color: Colors.grey.withOpacity(0.08),
                      blurRadius: 8,
                      offset: const Offset(0, 2))
                ]),
            child: ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: channels.length,
              separatorBuilder: (_, __) =>
                  Divider(height: 1, color: Colors.grey[200]),
              itemBuilder: (context, i) {
                final ch    = channels[i];
                final label = ch['label'] as String;
                final key   = ch['key']   as String;
                final value = ch['value'] as bool;
                return ListTile(
                  title: Text(label,
                      style: TextStyle(
                          fontSize: 19.sp, fontWeight: FontWeight.w500)),
                  trailing: Switch(
                    value: value,
                    onChanged: (val) => setState(() {
                      switch (key) {
                        case 'atm':    _atm    = val; break;
                        case 'pos':    _pos    = val; break;
                        case 'web':    _web    = val; break;
                        case 'mobile': _mobile = val; break;
                      }
                    }),
                    activeColor: const Color(0xFF34544A),
                  ),
                );
              },
            ),
          ),
        ),
        const Spacer(),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 24),
          child: SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF34544A),
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(32.r)),
                padding: EdgeInsets.symmetric(vertical: 16.h),
              ),
              onPressed: ref.watch(physicalCardNotifierProvider).isLoading
                  ? null
                  : _saveChanges,
              child: ref.watch(physicalCardNotifierProvider).isLoading
                  ? const CircularProgressIndicator(color: Colors.white)
                  : Text('Save Changes',
                      style: TextStyle(
                          fontSize: 18.sp, fontWeight: FontWeight.bold)),
            ),
          ),
        ),
      ]),
    );
  }
}