import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:flutter/rendering.dart';
import 'package:flutter/services.dart';
import 'dart:ui' as ui;
import 'dart:typed_data';
import 'package:path_provider/path_provider.dart';
import 'package:share_plus/share_plus.dart';
import 'package:gal/gal.dart';
import 'dart:io';
import '../../core/utils/formatters.dart';
import '../../providers/airtime_provider.dart';
import '../../providers/recharge_card_provider.dart';
import '../../models/airtime/airtime_network_model.dart';
import '../../models/recharge_card/epin_model.dart';
import '../transaction/transaction_summary_screen.dart';

class RechargeCardScreen extends ConsumerStatefulWidget {
  const RechargeCardScreen({super.key});

  @override
  ConsumerState<RechargeCardScreen> createState() => _RechargeCardScreenState();
}

class _RechargeCardScreenState extends ConsumerState<RechargeCardScreen> {
  final GlobalKey _repaintBoundaryKey = GlobalKey();

  // Local asset fallbacks keyed by lower-cased network name.
  static const Map<String, String> _logoAssets = {
    'mtn':     'assets/images/mtnlogo.jpg',
    '9mobile': 'assets/images/9mobilelogo.png',
    'airtel':  'assets/images/airtellogo.png',
    'glo':     'assets/images/glologo.png',
  };

  final List<String> denominations = [
    '100',
    '200',
    '500',
    '1000',
 
  ];
  final List<int> suggestedQuantities = [5, 10, 15, 20];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      // Always fetch fresh — older cached data may be missing image URLs.
      ref.read(airtimeNotifierProvider.notifier).getAirtimeNetworks();
      ref.read(rechargeCardNotifierProvider.notifier).fetchEpins();
    });
  }

  /// A circular network logo using the API's image URL when available, with a
  /// loading spinner while it fetches and a clear text fallback if the URL is
  /// missing or the request fails. We use Image.network (not CircleAvatar's
  /// backgroundImage) so we can react to load failures instead of silently
  /// showing a blank circle.
  Widget _networkLogo(AirtimeNetworkModel n, {required double radius}) {
    final size = radius * 2;
    final bg   = _networkColor(n);
    final url  = n.image;

    Widget fallback() {
      // Try a bundled asset first; if that's missing, show the network's
      // initial(s) on the brand colour so the circle is never blank.
      final asset = _logoAssets[(n.name ?? '').toLowerCase()];
      if (asset != null) {
        return Image.asset(asset, fit: BoxFit.cover, width: size, height: size,
            errorBuilder: (_, __, ___) => _initialsBadge(n, size));
      }
      return _initialsBadge(n, size);
    }

    Widget content;
    if (url != null && url.startsWith('http')) {
      content = Image.network(
        url,
        width: size,
        height: size,
        fit: BoxFit.cover,
        loadingBuilder: (ctx, child, progress) {
          if (progress == null) return child;
          return Center(
            child: SizedBox(
              width: size * 0.35,
              height: size * 0.35,
              child: const CircularProgressIndicator(
                  strokeWidth: 2, color: Colors.white),
            ),
          );
        },
        errorBuilder: (_, __, ___) => fallback(),
      );
    } else {
      content = fallback();
    }

    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: bg,
        shape: BoxShape.circle,
      ),
      child: ClipOval(child: content),
    );
  }

  Widget _initialsBadge(AirtimeNetworkModel n, double size) {
    final name = (n.name ?? '?').trim();
    final initials = name.isEmpty
        ? '?'
        : name.length <= 3
            ? name.toUpperCase()
            : name.substring(0, 2).toUpperCase();
    return Center(
      child: Text(
        initials,
        style: TextStyle(
          color: _networkTextColorByName(n.name ?? ''),
          fontWeight: FontWeight.bold,
          fontSize: size * 0.32,
        ),
      ),
    );
  }

  /// Brand-coloured background for each network.
  /// MTN → yellow, Airtel → red, Glo & 9mobile → green.
  Color _networkColor(AirtimeNetworkModel n) =>
      _networkColorByName(n.name ?? '');

  /// Same lookup but keyed by name string — used by the card-image generator
  /// which only has the network's display name (e.g. "AIRTEL" from the API).
  static Color _networkColorByName(String name) {
    switch (name.toLowerCase()) {
      case 'mtn':     return const Color(0xFFFFCC00); // MTN yellow
      case 'airtel':  return const Color(0xFFED1C24); // Airtel red
      case 'glo':     return const Color(0xFF50B848); // Glo green
      case '9mobile': return const Color(0xFF006E34); // 9mobile green
      default:        return Colors.white;
    }
  }

  /// Pick a readable text colour for a network badge based on its brand colour.
  static Color _networkTextColorByName(String name) {
    switch (name.toLowerCase()) {
      case 'mtn':     return Colors.black; // black on yellow
      case 'airtel':  return Colors.white; // white on red
      case 'glo':     return Colors.white; // white on green
      case '9mobile': return Colors.white; // white on dark green
      default:        return Colors.black;
    }
  }

  void _showRechargeCardDetails(AirtimeNetworkModel network) {
    String selectedDenomination = '1000';
    int selectedQuantity = 1;
    int? selectedSuggestedQuantity;
    final TextEditingController quantityController = TextEditingController(
      text: '1',
    );

    double getTotalAmount() =>
        selectedQuantity * double.parse(selectedDenomination);

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            return Padding(
              padding: EdgeInsets.only(
                left: 24,
                right: 24,
                top: 24,
                bottom: MediaQuery.of(context).viewInsets.bottom + 24,
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'Print Recharge Card',
                        style: TextStyle(
                          fontSize: 16.sp,
                          color: Colors.black,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      GestureDetector(
                        onTap: () => Navigator.of(context).pop(),
                        child: const Icon(
                          Icons.close,
                          color: Colors.red,
                          size: 20,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  Center(
                    child: Column(
                      children: [
                        _networkLogo(network, radius: 48),
                        const SizedBox(height: 16),
                        Text(
                          network.name ?? '',
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            color: const Color(0xFF34544A),
                            fontSize: 18.sp,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          'Recharge Card',
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            color: const Color(0xFF23235B),
                            fontSize: 22.sp,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 24),
                  _buildStyledTextField(
                    label: 'Select Denomination',
                    value: selectedDenomination,
                    onTap:
                        () => _showDenominationPicker(context, (value) {
                          setModalState(() {
                            selectedDenomination = value;
                          });
                        }),
                    hasDropdown: true,
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'Suggested Quantity:',
                    style: TextStyle(
                      fontWeight: FontWeight.bold,
                      color: Colors.black,
                      fontSize: 15.sp,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                    children:
                        suggestedQuantities.map((qty) {
                          final isSelected = selectedSuggestedQuantity == qty;
                          return GestureDetector(
                            onTap: () {
                              setModalState(() {
                                selectedSuggestedQuantity = qty;
                                selectedQuantity = qty;
                                quantityController.text = qty.toString();
                              });
                            },
                            child: Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 16,
                                vertical: 8,
                              ),
                              decoration: BoxDecoration(
                                color:
                                    isSelected
                                        ? const Color(0xFF34544A)
                                        : Colors.grey[300],
                                borderRadius: BorderRadius.circular(20),
                              ),
                              child: Text(
                                qty.toString(),
                                style: TextStyle(
                                  color:
                                      isSelected ? Colors.white : Colors.black,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ),
                          );
                        }).toList(),
                  ),
                  const SizedBox(height: 20),
                  _buildQuantityTextField(
                    controller: quantityController,
                    onChanged: (value) {
                      final qty = int.tryParse(value);
                      if (qty != null && qty > 0) {
                        setModalState(() {
                          selectedQuantity = qty;
                          selectedSuggestedQuantity = null;
                        });
                      }
                    },
                  ),
                  const SizedBox(height: 20),
                  _buildStyledTextField(
                    label: 'Total Amount',
                    value: formatCurrency(getTotalAmount()),
                    isReadOnly: true,
                  ),
                  const SizedBox(height: 32),
                  SizedBox(
                    width: double.infinity,
                    height: 48,
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF34544A),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      onPressed: () {
                        Navigator.of(context).pop(); // close the details sheet
                        final denominationInt =
                            int.tryParse(selectedDenomination) ?? 0;
                        final totalAmount = getTotalAmount();
                        final networkId = network.id;
                        if (networkId == null || denominationInt <= 0) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(
                              content: Text('Invalid network or denomination'),
                              backgroundColor: Colors.red,
                            ),
                          );
                          return;
                        }

                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => TransactionSummaryScreen(
                              data: TransactionSummaryData(
                                service: 'Recharge Card',
                                from: 'Wallet',
                                accountName: network.name ?? '',
                                amount: formatCurrency(totalAmount),
                                description:
                                    '$selectedQuantity × ₦$selectedDenomination'
                                    ' • ${network.name ?? ''}',
                                onConfirm: (pin) async {
                                  final result = await ref
                                      .read(rechargeCardNotifierProvider
                                          .notifier)
                                      .buyEpin(
                                        networkId:      networkId,
                                        value:          denominationInt,
                                        quantity:       selectedQuantity,
                                        transactionPin: pin,
                                      );

                                  // On success, share the freshly purchased
                                  // cards once the receipt screen is shown.
                                  if (result.success &&
                                      result.purchase != null &&
                                      result.purchase!.cards.isNotEmpty) {
                                    final purchase = result.purchase!;
                                    // Convert API cards to the shape the
                                    // existing share helper expects.
                                    final shareCards = purchase.cards
                                        .map((c) => {
                                              'pin':       c.pin,
                                              'reference': c.serial,
                                            })
                                        .toList();
                                    // Fire and forget — share dialog appears
                                    // after the user dismisses the receipt.
                                    Future.delayed(
                                      const Duration(milliseconds: 600),
                                      () {
                                        if (!mounted) return;
                                        _shareRechargeCards(
                                          purchase.cardNetwork.isNotEmpty
                                              ? purchase.cardNetwork
                                              : (network.name ?? ''),
                                          selectedDenomination,
                                          shareCards,
                                        );
                                      },
                                    );
                                  }

                                  return {
                                    'success':     result.success,
                                    'message':     result.message,
                                    'type':        'Debit',
                                    'reference':   result.purchase?.trxid ?? '',
                                    'description':
                                        '$selectedQuantity × '
                                        '₦$selectedDenomination ${network.name ?? ''}'
                                        ' recharge card(s)',
                                  };
                                },
                              ),
                            ),
                          ),
                        );
                      },
                      child: const Text(
                        'Print Recharge Cards',
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 18,
                          color: Colors.white,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 32),
                ],
              ),
            );
          },
        );
      },
    );
  }

  /// Map a network_id (from EpinModel) back to a network name using the
  /// currently loaded airtime networks list.
  String _networkNameById(int networkId) {
    final networks = ref.read(airtimeNotifierProvider).networks;
    if (networks == null) return 'Network';
    for (final n in networks) {
      if (n.id == networkId) return n.name ?? 'Network';
    }
    return 'Network';
  }

  String _getNetworkLogo(String networkName) {
    switch (networkName.toLowerCase()) {
      case 'mtn':
        return 'assets/images/mtnlogo.jpg';
      case '9mobile':
        return 'assets/images/9mobilelogo.png';
      case 'airtel':
        return 'assets/images/airtellogo.png';
      case 'glo':
        return 'assets/images/glologo.png';
      default:
        return 'assets/images/glologo.png';
    }
  }

  String _formatPin(String pin) {
    String formatted = '';
    for (int i = 0; i < pin.length; i++) {
      if (i > 0 && i % 4 == 0) {
        formatted += ' ';
      }
      formatted += pin[i];
    }
    return formatted;
  }

  // Method to capture screenshot and share recharge cards - invisible capture
  Future<void> _shareRechargeCards(
    String networkName,
    String denomination,
    List<Map<String, String>> cards,
  ) async {
    if (!mounted) return;

    try {
      // Show loading indicator
      showDialog(
        context: context,
        barrierDismissible: false,
        // Transparent dialog so only the spinner + caption show on top of the
        // dim barrier — no pill background.
        builder: (context) => Dialog(
          backgroundColor: Colors.transparent,
          elevation: 0,
          child: Center(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.center,
              children: const [
                SizedBox(
                  width: 22,
                  height: 22,
                  child: CircularProgressIndicator(
                    strokeWidth: 2.2,
                    color: Colors.white,
                  ),
                ),
                SizedBox(height: 10),
                Text(
                  'Generating recharge cards...',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 12,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
          ),
        ),
      );

      // Create the image widget for capture - offscreen
      final GlobalKey repaintBoundaryKey = GlobalKey();

      // Create a temporary overlay for invisible capture
      final overlay = Overlay.of(context);
      late OverlayEntry overlayEntry;

      overlayEntry = OverlayEntry(
        builder:
            (context) => Positioned(
              left: -2000, // Position offscreen
              top: -2000,
              child: RepaintBoundary(
                key: repaintBoundaryKey,
                child: Material(
                  color: Colors.white,
                  child: _buildCardsForCapture(
                    networkName,
                    denomination,
                    cards,
                  ),
                ),
              ),
            ),
      );

      overlay.insert(overlayEntry);

      // Wait for widget to render
      await Future.delayed(const Duration(milliseconds: 800));

      if (!mounted) {
        overlayEntry.remove();
        return;
      }

      // Find the RenderRepaintBoundary
      RenderRepaintBoundary boundary =
          repaintBoundaryKey.currentContext!.findRenderObject()
              as RenderRepaintBoundary;

      // Capture the image
      ui.Image image = await boundary.toImage(
        pixelRatio: 2.0,
      ); // Reduced pixel ratio for large quantities
      ByteData? byteData = await image.toByteData(
        format: ui.ImageByteFormat.png,
      );
      Uint8List pngBytes = byteData!.buffer.asUint8List();

      // Remove the overlay
      overlayEntry.remove();

      // Hide loading dialog
      if (mounted) Navigator.of(context).pop();

      // Try sharing directly
      if (mounted) {
        await _attemptShareCards(
          context,
          pngBytes,
          networkName,
          denomination,
          cards.length,
        );
      }
    } catch (e) {
      // Hide any dialogs
      if (mounted) Navigator.of(context).pop();

      // Show error message
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error sharing cards: ${e.toString()}'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  Future<void> _attemptShareCards(
    BuildContext context,
    Uint8List pngBytes,
    String networkName,
    String denomination,
    int quantity,
  ) async {
    if (!mounted) return;

    // iOS requires an anchor rect for the share-sheet popover. Without it the
    // sheet silently fails to appear on iPhone and crashes on iPad. Android
    // ignores this parameter. Derive it from the current screen.
    final box = context.findRenderObject() as RenderBox?;
    final Rect sharePosition = box != null
        ? box.localToGlobal(Offset.zero) & box.size
        : Rect.fromLTWH(0, 0, MediaQuery.of(context).size.width,
            MediaQuery.of(context).size.height / 2);

    try {
      // Method 1: Try sharing with file
      final directory = await getTemporaryDirectory();
      final file = File(
        '${directory.path}/recharge_cards_${networkName}_${denomination}_${DateTime.now().millisecondsSinceEpoch}.png',
      );
      await file.writeAsBytes(pngBytes);

      await Share.shareXFiles(
        [XFile(file.path)],
        text: 'Recharge Cards - $networkName $denomination ($quantity cards)',
        subject: 'Recharge Cards',
        sharePositionOrigin: sharePosition,
      );
    } catch (e1) {
      try {
        // Method 2: Try sharing with bytes directly
        await Share.shareXFiles([
          XFile.fromData(
            pngBytes,
            name: 'recharge_cards.png',
            mimeType: 'image/png',
          ),
        ],
            text: 'Recharge Cards - $networkName $denomination',
            sharePositionOrigin: sharePosition);
      } catch (e2) {
        // Method 3: Show manual share dialog as fallback
        if (mounted) {
          _showManualShareDialog(context, networkName, denomination, quantity);
        }
      }
    }
  }

  void _showManualShareDialog(
    BuildContext context,
    String networkName,
    String denomination,
    int quantity,
  ) {
    if (!mounted) return;

    showDialog(
      context: context,
      builder:
          (context) => AlertDialog(
            title: const Text('Share Recharge Cards'),
            content: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('$networkName Recharge Cards'),
                Text('Denomination: $denomination'),
                Text('Quantity: $quantity cards'),
                const SizedBox(height: 8),
                const Text('Image sharing not available on this device.'),
              ],
            ),
            actions: [
              TextButton(
                onPressed: () {
                  if (mounted) Navigator.of(context).pop();
                },
                child: const Text('OK'),
              ),
            ],
          ),
    );
  }

  Widget _buildCardsForCapture(
    String networkName,
    String denomination,
    List<Map<String, String>> cards,
  ) {
    const cardsPerRow = 2;
    final rows = (cards.length / cardsPerRow).ceil();

    // Limit the number of rows to prevent memory issues with large quantities
    final maxRows = 20; // Maximum 40 cards per image
    final actualRows = rows > maxRows ? maxRows : rows;
    final displayCards = cards.take(actualRows * cardsPerRow).toList();

    return SingleChildScrollView(
      child: Container(
        color: Colors.white,
        padding: const EdgeInsets.all(12),
        width: 600, // Reduced width to prevent overflow
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Show total count if cards were limited
            if (cards.length > displayCards.length)
              Container(
                padding: const EdgeInsets.all(8),
                margin: const EdgeInsets.only(bottom: 16),
                decoration: BoxDecoration(
                  color: Colors.blue[100],
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  'Showing first ${displayCards.length} of ${cards.length} cards',
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                    color: Colors.blue,
                  ),
                  textAlign: TextAlign.center,
                ),
              ),
            ...List.generate(actualRows, (rowIndex) {
              final startIndex = rowIndex * cardsPerRow;
              final endIndex = (startIndex + cardsPerRow).clamp(
                0,
                displayCards.length,
              );
              final rowCards = displayCards.sublist(startIndex, endIndex);

              return Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: Row(
                  children: List.generate(cardsPerRow, (colIndex) {
                    if (colIndex < rowCards.length) {
                      return Expanded(
                        child: Container(
                          margin: const EdgeInsets.symmetric(horizontal: 6),
                          child: _buildSingleCard(
                            networkName,
                            denomination,
                            rowCards[colIndex],
                          ),
                        ),
                      );
                    } else {
                      return const Expanded(child: SizedBox());
                    }
                  }),
                ),
              );
            }),
          ],
        ),
      ),
    );
  }

  Widget _buildSingleCard(
    String networkName,
    String denomination,
    Map<String, String> cardData,
  ) {
    return Container(
      constraints: const BoxConstraints(
        minHeight: 200,
        maxHeight: 250,
        maxWidth: 280,
      ),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.black, width: 1),
      ),
      padding: const EdgeInsets.all(12),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header row with logo and amount
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Flexible(
                flex: 1,
                child: Column(
                  children: [
                    Container(
                      width: 32,
                      height: 32,
                      decoration: BoxDecoration(
                        color: _networkColorByName(networkName),
                        shape: BoxShape.circle,
                      ),
                      child: Center(
                        child: Text(
                          networkName,
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 8,
                            color: _networkTextColorByName(networkName),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      networkName,
                      style: const TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.bold,
                      ),
                      textAlign: TextAlign.center,
                    ),
                  ],
                ),
              ),
              Flexible(
                flex: 1,
                child: Text(
                  '₦$denomination.00',
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                  ),
                  textAlign: TextAlign.right,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          // PIN section
          const Text(
            'PIN:',
            style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 4),
          Flexible(
            child: Text(
              _formatPin(cardData['pin']!),
              style: const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.bold,
                fontFamily: 'monospace',
                letterSpacing: 1.0,
              ),
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
          ),
          const SizedBox(height: 8),
          // Reference section
          const Text(
            'Ref:',
            style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 4),
          Flexible(
            child: Text(
              cardData['reference']!,
              style: const TextStyle(fontSize: 10, fontFamily: 'monospace'),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ),
          const SizedBox(height: 8),
          // USSD Code — coloured to match the network brand
          Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 12),
            decoration: BoxDecoration(
              color: _networkColorByName(networkName),
              borderRadius: BorderRadius.circular(6),
            ),
            child: Text(
              '*311*${cardData['pin']}#',
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.bold,
                color: _networkTextColorByName(networkName),
                fontFamily: 'monospace',
                letterSpacing: 0.5,
              ),
              textAlign: TextAlign.center,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _saveImageToDevice(
    Uint8List pngBytes,
    String networkName,
    String denomination,
    int quantity,
  ) async {
    try {
      // Save to the device photo gallery on BOTH platforms.
      // The previous iOS path wrote to the app's private Documents sandbox,
      // which the user can never see (file looked like it failed to save).
      // `gal` writes to the real photo library on iOS and Android.
      final fileName =
          '${networkName}_${denomination}_${quantity}cards_${DateTime.now().millisecondsSinceEpoch}';
      await Gal.putImageBytes(pngBytes, name: fileName);
    } catch (e) {
      throw Exception('Failed to save file: $e');
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
            const SizedBox(height: 30),
            Row(
              children: [
                IconButton(
                  icon: const Icon(Icons.arrow_back_ios, color: Colors.white),
                  onPressed: () => Navigator.of(context).pop(),
                ),
                Expanded(
                  child: Text(
                    'Print Recharge Card',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 25.sp,
                      fontWeight: FontWeight.w700,
                      color: Colors.white,
                    ),
                  ),
                ),
                const SizedBox(width: 48),
              ],
            ),
            const SizedBox(height: 20),
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
                    child: SingleChildScrollView(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          SizedBox(height: 24.h),
                          Padding(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 24.0,
                            ),
                            child: Text(
                              'Select Network',
                              style: TextStyle(
                                fontSize: 16.sp,
                                color: Colors.black,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                          SizedBox(height: 18.h),
                          _NetworkGrid(
                            networks:
                                ref.watch(airtimeNotifierProvider).networks,
                            isLoading:
                                ref.watch(airtimeNotifierProvider).isLoading,
                            error:
                                ref.watch(airtimeNotifierProvider).error,
                            onRetry: () => ref
                                .read(airtimeNotifierProvider.notifier)
                                .getAirtimeNetworks(forceRefresh: true),
                            onTap: _showRechargeCardDetails,
                            logoBuilder: (n) => _networkLogo(n, radius: 28),
                          ),
                          const SizedBox(height: 32),
                          _buildPreviousOrders(),
                          const SizedBox(height: 24),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildQuantityTextField({
    required TextEditingController controller,
    required Function(String) onChanged,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Quantity',
          style: TextStyle(
            fontWeight: FontWeight.bold,
            color: Colors.black,
            fontSize: 15.sp,
          ),
        ),
        const SizedBox(height: 8),
        TextField(
          controller: controller,
          keyboardType: TextInputType.number,
          decoration: InputDecoration(
            hintText: 'Enter quantity',
            filled: true,
            fillColor: const Color(0xFFF8FAF9),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(color: Color(0xFFE0E0E0)),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(color: Color(0xFF34544A)),
            ),
          ),
          onChanged: onChanged,
        ),
      ],
    );
  }

  Widget _buildPreviousOrders() {
    final state = ref.watch(rechargeCardNotifierProvider);
    final epins = state.epins;

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 24.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Previous Orders',
                style: TextStyle(
                  fontSize: 16.sp,
                  color: Colors.black,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          if (state.isLoading && epins.isEmpty)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 24),
              child: Center(
                child: CircularProgressIndicator(
                    color: Color(0xFF34544A)),
              ),
            )
          else if (state.error != null && epins.isEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 16),
              child: Column(children: [
                Text(state.error!,
                    style: TextStyle(color: Colors.grey[600], fontSize: 13),
                    textAlign: TextAlign.center),
                const SizedBox(height: 8),
                TextButton(
                  onPressed: () => ref
                      .read(rechargeCardNotifierProvider.notifier)
                      .fetchEpins(),
                  child: const Text('Retry'),
                ),
              ]),
            )
          else if (epins.isEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 24),
              child: Center(
                child: Text('No previous orders yet',
                    style:
                        TextStyle(color: Colors.grey[500], fontSize: 13)),
              ),
            )
          else
            Container(
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFFE0E0E0)),
              ),
              child: ListView.separated(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                itemCount: epins.length,
                separatorBuilder: (_, __) => const Divider(height: 1),
                itemBuilder: (context, index) {
                  final epin = epins[index];
                  return ListTile(
                    title: Text(
                      '${epin.quantity} × ${formatCurrency(epin.value)}',
                      style: const TextStyle(
                          fontWeight: FontWeight.bold),
                    ),
                    subtitle: Text(
                      '${_networkNameById(epin.networkId)} • '
                      '${_formatEpinDate(epin.createdAt)}',
                    ),
                    trailing: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        GestureDetector(
                          onTap: () => _sharePreviousOrder(epin),
                          child:
                              const Icon(Icons.share, color: Colors.grey),
                        ),
                        const SizedBox(width: 8),
                        GestureDetector(
                          onTap: () => _downloadPreviousOrder(epin),
                          child: const Icon(Icons.download,
                              color: Colors.grey),
                        ),
                      ],
                    ),
                  );
                },
              ),
            ),
        ],
      ),
    );
  }

  String _formatEpinDate(DateTime d) {
    const months = [
      'Jan','Feb','Mar','Apr','May','Jun',
      'Jul','Aug','Sep','Oct','Nov','Dec'
    ];
    return '${months[d.month - 1]} ${d.day}, ${d.year}';
  }

  // Share previous order using the real PINs returned by the backend.
  void _sharePreviousOrder(EpinModel epin) {
    if (epin.cards.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
            content: Text('No PINs available for this order')),
      );
      return;
    }
    final cards = epin.cards
        .map((c) => {'pin': c.pin, 'reference': c.serial})
        .toList();
    _shareRechargeCards(
      _networkNameById(epin.networkId),
      epin.value.toStringAsFixed(0),
      cards,
    );
  }

  // Same as share — the existing flow downloads via the share sheet.
  void _downloadPreviousOrder(EpinModel epin) => _sharePreviousOrder(epin);

  Widget _buildStyledTextField({
    required String label,
    required String value,
    VoidCallback? onTap,
    bool hasDropdown = false,
    bool isEditable = false,
    bool isReadOnly = false,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: TextStyle(
            fontWeight: FontWeight.bold,
            color: Colors.black,
            fontSize: 15.sp,
          ),
        ),
        const SizedBox(height: 8),
        GestureDetector(
          onTap: onTap,
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: BoxDecoration(
              color: const Color(0xFFF8FAF9),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: const Color(0xFFE0E0E0)),
            ),
            child: Row(
              children: [
                Expanded(
                  child: Text(
                    value,
                    style: TextStyle(
                      color: isReadOnly ? Colors.grey[600] : Colors.black,
                      fontSize: 16.sp,
                    ),
                  ),
                ),
                if (hasDropdown) const Icon(Icons.arrow_drop_down),
              ],
            ),
          ),
        ),
      ],
    );
  }

  void _showDenominationPicker(
    BuildContext context,
    Function(String) onSelect,
  ) {
    showModalBottomSheet(
      context: context,
      builder:
          (context) => Container(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Padding(
                  padding: EdgeInsets.all(16.0),
                  child: Text(
                    'Select Denomination',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                ),
                Expanded(
                  child: ListView.builder(
                    itemCount: denominations.length,
                    itemBuilder: (context, index) {
                      return ListTile(
                        title: Text(formatCurrency(denominations[index])),
                        onTap: () {
                          onSelect(denominations[index]);
                          Navigator.pop(context);
                        },
                      );
                    },
                  ),
                ),
              ],
            ),
          ),
    );
  }
}

// ─── Network grid (state-aware) ───────────────────────────────────────────────
class _NetworkGrid extends StatelessWidget {
  final List<AirtimeNetworkModel>? networks;
  final bool isLoading;
  final String? error;
  final VoidCallback onRetry;
  final void Function(AirtimeNetworkModel) onTap;
  final Widget Function(AirtimeNetworkModel) logoBuilder;

  const _NetworkGrid({
    required this.networks,
    required this.isLoading,
    required this.error,
    required this.onRetry,
    required this.onTap,
    required this.logoBuilder,
  });

  @override
  Widget build(BuildContext context) {
    if (isLoading && (networks == null || networks!.isEmpty)) {
      return const Padding(
        padding: EdgeInsets.symmetric(vertical: 30),
        child: Center(
            child:
                CircularProgressIndicator(color: Color(0xFF34544A))),
      );
    }
    if ((networks == null || networks!.isEmpty)) {
      return Padding(
        padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 24),
        child: Column(children: [
          Text(
            error ?? 'No networks available',
            style: TextStyle(color: Colors.grey[600], fontSize: 13),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 8),
          TextButton(onPressed: onRetry, child: const Text('Retry')),
        ]),
      );
    }
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 60.0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: networks!.map((n) {
          return GestureDetector(
            onTap: () => onTap(n),
            child: Column(children: [
              logoBuilder(n),
              const SizedBox(height: 6),
              Text(
                n.name ?? '',
                style: TextStyle(
                  fontSize: 13.sp,
                  color: Colors.black,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ]),
          );
        }).toList(),
      ),
    );
  }
}