// lib/screen/auth/contact_support_screen.dart

import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:url_launcher/url_launcher.dart';

class ContactSupportScreen extends StatefulWidget {
  const ContactSupportScreen({super.key});

  @override
  State<ContactSupportScreen> createState() => _ContactSupportScreenState();
}

class _ContactSupportScreenState extends State<ContactSupportScreen> {
  int? _expandedFaq;

  final List<Map<String, String>> _faqs = [
    {
      'q': 'How long does account verification take?',
      'a':
          'Account verification typically takes 24–48 hours. Ensure you submit clear photos of your documents. You will receive a notification once your account is verified.',
    },
    {
      'q': 'How do I reset my transaction PIN?',
      'a':
          'Go to More → Transaction PIN → Reset PIN. You will need to verify your identity via email OTP before setting a new PIN.',
    },
    {
      'q': 'Why is my transfer still pending?',
      'a':
          'Transfers can take up to 24 hours due to bank processing times. If it\'s been longer than 24 hours, please contact support with your transaction reference.',
    },
    {
      'q': 'How do I fund my wallet?',
      'a':
          'Go to Fund Wallet on your dashboard, copy your virtual account number, then make a bank transfer to that account. Your wallet will be credited instantly.',
    },
    {
      'q': 'What should I do if I sent money to the wrong account?',
      'a':
          'Contact our support team immediately with the transaction reference. We will investigate and assist with a reversal where possible.',
    },
    {
      'q': 'How do I increase my daily transaction limit?',
      'a':
          'Complete your KYC verification under More → KYC Verification. Higher verification levels unlock higher transaction limits.',
    },
  ];

  Future<void> _launchUrl(String url) async {
    final uri = Uri.parse(url);
    if (!await launchUrl(uri, mode: LaunchMode.externalApplication)) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Could not open $url'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF4F7FA),
      appBar: AppBar(
        backgroundColor: const Color(0xFF34544A),
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios, color: Colors.white),
          onPressed: () => Navigator.of(context).pop(),
        ),
        title: Text(
          'Contact Support',
          style: TextStyle(
            color: Colors.white,
            fontSize: 20.sp,
            fontWeight: FontWeight.w600,
          ),
        ),
        centerTitle: true,
      ),
      body: ListView(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 20),
        children: [
          // ── Header card ───────────────────────────────────────────────────
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.05),
                  blurRadius: 8,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Need help? We are here for you.',
                  style: TextStyle(
                    fontSize: 17.sp,
                    fontWeight: FontWeight.bold,
                    color: const Color(0xFF34544A),
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  'Reach us via email or WhatsApp and our team will respond as quickly as possible.',
                  style: TextStyle(
                    fontSize: 13.sp,
                    color: Colors.grey[600],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // ── Contact options ───────────────────────────────────────────────
          _ContactTile(
            icon: Icons.email_outlined,
            title: 'Email Support',
            subtitle: 'info@mydupay.com',
            onTap: () => _launchUrl('mailto:info@mydupay.com'),
          ),
          const SizedBox(height: 12),
          _ContactTile(
            icon: Icons.chat_outlined,
            title: 'WhatsApp Support',
            subtitle: 'wa.me/+2349060341324',
            onTap: () => _launchUrl('https://wa.me/2349060341324'),
          ),
          const SizedBox(height: 12),
          _ContactTile(
            icon: Icons.support_agent,
            title: 'Live Support',
            subtitle: 'Chat with our support team in-app',
            onTap: () => Navigator.pushNamed(context, '/liveChat'),
          ),
          const SizedBox(height: 28),

          // ── FAQs ──────────────────────────────────────────────────────────
          Text(
            'Common FAQs',
            style: TextStyle(
              fontSize: 18.sp,
              fontWeight: FontWeight.bold,
              color: const Color(0xFF34544A),
            ),
          ),
          const SizedBox(height: 14),
          ..._faqs.asMap().entries.map((entry) {
            final i   = entry.key;
            final faq = entry.value;
            final isExpanded = _expandedFaq == i;
            return Container(
              margin: const EdgeInsets.only(bottom: 10),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.04),
                    blurRadius: 4,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: InkWell(
                onTap: () =>
                    setState(() => _expandedFaq = isExpanded ? null : i),
                borderRadius: BorderRadius.circular(12),
                child: Padding(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 16, vertical: 16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: Text(
                              faq['q']!,
                              style: TextStyle(
                                fontSize: 14.sp,
                                fontWeight: FontWeight.w600,
                                color: Colors.black87,
                              ),
                            ),
                          ),
                          Icon(
                            isExpanded
                                ? Icons.keyboard_arrow_up
                                : Icons.keyboard_arrow_down,
                            color: const Color(0xFF34544A),
                          ),
                        ],
                      ),
                      if (isExpanded) ...[
                        const SizedBox(height: 10),
                        Text(
                          faq['a']!,
                          style: TextStyle(
                            fontSize: 13.sp,
                            color: Colors.grey[700],
                            height: 1.5,
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
              ),
            );
          }),
          const SizedBox(height: 40),
        ],
      ),
    );
  }
}

// ─── Contact tile widget ──────────────────────────────────────────────────────

class _ContactTile extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;

  const _ContactTile({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(14),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.05),
              blurRadius: 6,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Row(
          children: [
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                color: const Color(0xFF34544A).withOpacity(0.08),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(icon, color: const Color(0xFF34544A), size: 22),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 15.sp,
                      color: Colors.black87,
                    ),
                  ),
                  const SizedBox(height: 3),
                  Text(
                    subtitle,
                    style: TextStyle(
                      fontSize: 13.sp,
                      color: Colors.grey[600],
                    ),
                  ),
                ],
              ),
            ),
            Icon(Icons.arrow_forward_ios,
                size: 15, color: Colors.grey[400]),
          ],
        ),
      ),
    );
  }
}