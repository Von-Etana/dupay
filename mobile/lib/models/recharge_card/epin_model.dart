// lib/models/recharge_card/epin_model.dart

import 'dart:convert';

/// A single PIN + serial pair inside an ePIN order.
class EpinCardItem {
  final String pin;
  final String serial;

  const EpinCardItem({required this.pin, required this.serial});

  factory EpinCardItem.fromJson(Map<String, dynamic> json) => EpinCardItem(
        pin:    json['pin']?.toString()    ?? '',
        serial: json['serial']?.toString() ?? '',
      );

  Map<String, dynamic> toJson() => {'pin': pin, 'serial': serial};
}

/// A purchased ePIN order from GET /user/fetch-epins.
///
/// API quirk: `cards` arrives as a JSON-encoded *string*, not a list.
/// Example:
///   "cards": "[{\"pin\": \"...\", \"serial\": \"...\"}, ...]"
class EpinModel {
  final int                id;
  final int                userId;
  final int                networkId;
  final double             value;
  final int                quantity;
  final List<EpinCardItem> cards;
  final String?            pdfPath;
  final String             reference;
  final DateTime           createdAt;

  const EpinModel({
    required this.id,
    required this.userId,
    required this.networkId,
    required this.value,
    required this.quantity,
    required this.cards,
    required this.reference,
    required this.createdAt,
    this.pdfPath,
  });

  factory EpinModel.fromJson(Map<String, dynamic> json) {
    // Parse the JSON-string cards field defensively.
    final rawCards = json['cards'];
    List<EpinCardItem> cards = const [];
    try {
      if (rawCards is String && rawCards.isNotEmpty) {
        final decoded = jsonDecode(rawCards);
        if (decoded is List) {
          cards = decoded
              .whereType<Map<String, dynamic>>()
              .map(EpinCardItem.fromJson)
              .toList();
        }
      } else if (rawCards is List) {
        // Server might evolve to send a real array later.
        cards = rawCards
            .whereType<Map<String, dynamic>>()
            .map(EpinCardItem.fromJson)
            .toList();
      }
    } catch (_) {
      cards = const [];
    }

    return EpinModel(
      id:        (json['id']         as num?)?.toInt() ?? 0,
      userId:    (json['user_id']    as num?)?.toInt() ?? 0,
      networkId: (json['network_id'] as num?)?.toInt() ?? 0,
      value:     double.tryParse(json['value']?.toString() ?? '') ?? 0,
      quantity:  (json['quantity']   as num?)?.toInt() ?? 0,
      cards:     cards,
      pdfPath:   json['pdf_path']?.toString(),
      reference: json['reference']?.toString() ?? '',
      createdAt: DateTime.tryParse(json['created_at']?.toString() ?? '') ??
          DateTime.now(),
    );
  }

  /// Serialise for the local cache. We emit `cards` as a real JSON array (not
  /// the JSON-encoded string the API sends) — `fromJson` handles both shapes.
  Map<String, dynamic> toJson() => {
        'id':         id,
        'user_id':    userId,
        'network_id': networkId,
        'value':      value,
        'quantity':   quantity,
        'cards':      cards.map((c) => c.toJson()).toList(),
        'pdf_path':   pdfPath,
        'reference':  reference,
        'created_at': createdAt.toIso8601String(),
      };
}

/// Response payload from POST /buy-epin.
///
/// Example:
///   {
///     "card_network": "AIRTEL",
///     "value": "500",
///     "quantity": "1",
///     "cards": [{"pin": "...", "serial": "..."}],
///     "trxid": "21900000087327583"
///   }
class RechargeCardPurchase {
  final String             cardNetwork;
  final String             value;
  final String             quantity;
  final List<EpinCardItem> cards;
  final String             trxid;

  const RechargeCardPurchase({
    required this.cardNetwork,
    required this.value,
    required this.quantity,
    required this.cards,
    required this.trxid,
  });

  factory RechargeCardPurchase.fromJson(Map<String, dynamic> json) {
    final raw = json['cards'];
    List<EpinCardItem> cards = const [];
    if (raw is List) {
      cards = raw
          .whereType<Map<String, dynamic>>()
          .map(EpinCardItem.fromJson)
          .toList();
    } else if (raw is String && raw.isNotEmpty) {
      try {
        final decoded = jsonDecode(raw);
        if (decoded is List) {
          cards = decoded
              .whereType<Map<String, dynamic>>()
              .map(EpinCardItem.fromJson)
              .toList();
        }
      } catch (_) {}
    }
    return RechargeCardPurchase(
      cardNetwork: json['card_network']?.toString() ?? '',
      value:       json['value']?.toString()        ?? '',
      quantity:    json['quantity']?.toString()     ?? '',
      cards:       cards,
      trxid:       json['trxid']?.toString()        ?? '',
    );
  }
}