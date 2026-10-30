// lib/models/auth/user_stats_model.dart
class UserStatsModel {
  final String dailyDeposit;
  final String dailySpent;
  final String dailyTransaction;
  final String allDeposit;
  final String allSpent;
  final String allTransaction;

  UserStatsModel({
    required this.dailyDeposit,
    required this.dailySpent,
    required this.dailyTransaction,
    required this.allDeposit,
    required this.allSpent,
    required this.allTransaction,
  });

  factory UserStatsModel.fromJson(Map<String, dynamic> json) {
    return UserStatsModel(
      dailyDeposit: json['daily_deposit']?.toString() ?? '0',
      dailySpent: json['daily_spent']?.toString() ?? '0',
      dailyTransaction: json['daily_transaction']?.toString() ?? '0',
      allDeposit: json['all_deposit']?.toString() ?? '0',
      allSpent: json['all_spent']?.toString() ?? '0',
      allTransaction: json['all_transaction']?.toString() ?? '0',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'daily_deposit': dailyDeposit,
      'daily_spent': dailySpent,
      'daily_transaction': dailyTransaction,
      'all_deposit': allDeposit,
      'all_spent': allSpent,
      'all_transaction': allTransaction,
    };
  }
}
