import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../providers/auth_provider.dart';
import 'otp_verify_screen.dart';

class PhoneAuthScreen extends StatefulWidget {
  const PhoneAuthScreen({super.key});

  @override
  State<PhoneAuthScreen> createState() => _PhoneAuthScreenState();
}

class _PhoneAuthScreenState extends State<PhoneAuthScreen> {
  final _phoneController = TextEditingController();
  final _nameController = TextEditingController();
  final _formKey = GlobalKey<FormState>();

  String _countryCode = '+94'; // Sri Lanka default
  bool _isLoading = false;

  final List<Map<String, String>> _countryCodes = [
    {'code': '+94', 'flag': '🇱🇰', 'name': 'Sri Lanka'},
    {'code': '+1', 'flag': '🇺🇸', 'name': 'United States'},
    {'code': '+44', 'flag': '🇬🇧', 'name': 'United Kingdom'},
    {'code': '+91', 'flag': '🇮🇳', 'name': 'India'},
    {'code': '+61', 'flag': '🇦🇺', 'name': 'Australia'},
    {'code': '+971', 'flag': '🇦🇪', 'name': 'UAE'},
  ];

  Future<void> _submitPhone() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);
    final rawNumber = _phoneController.text.trim().replaceAll(RegExp(r'[^0-9]'), '');
    final fullPhone = '$_countryCode$rawNumber';
    final name = _nameController.text.trim();

    final authProvider = context.read<AuthProvider>();
    final nav = Navigator.of(context);
    final scaffoldMessenger = ScaffoldMessenger.of(context);

    final success = await authProvider.sendOtp(fullPhone);

    setState(() => _isLoading = false);

    if (mounted) {
      if (success) {
        nav.push(
          MaterialPageRoute(
            builder: (_) => OtpVerifyScreen(
              phone: fullPhone,
              name: name.isNotEmpty ? name : null,
            ),
          ),
        );
      } else {
        scaffoldMessenger.showSnackBar(
          SnackBar(
            content: Text(authProvider.errorMessage ?? 'Failed to send OTP'),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Phone Authentication')),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const Text(
                  'Enter Phone Number',
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 8),
                const Text(
                  'We will send a 6-digit verification code to your phone via SMS.',
                  style: TextStyle(color: AppColors.textSecondary),
                ),
                const SizedBox(height: 24),
                TextFormField(
                  controller: _nameController,
                  decoration: const InputDecoration(
                    labelText: 'Full Name (Optional)',
                    prefixIcon: Icon(Icons.person_outline_rounded),
                  ),
                ),
                const SizedBox(height: 16),

                // Phone input with Country Code Selector
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      height: 56,
                      padding: const EdgeInsets.symmetric(horizontal: 12),
                      decoration: BoxDecoration(
                        color: AppColors.surface,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: DropdownButtonHideUnderline(
                        child: DropdownButton<String>(
                          value: _countryCode,
                          items: _countryCodes.map((item) {
                            return DropdownMenuItem<String>(
                              value: item['code'],
                              child: Text(
                                '${item['flag']} ${item['code']}',
                                style: const TextStyle(fontWeight: FontWeight.bold),
                              ),
                            );
                          }).toList(),
                          onChanged: (val) {
                            if (val != null) setState(() => _countryCode = val);
                          },
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: TextFormField(
                        controller: _phoneController,
                        keyboardType: TextInputType.phone,
                        decoration: const InputDecoration(
                          labelText: 'Mobile Number',
                          hintText: '77 123 4567',
                        ),
                        validator: (value) {
                          if (value == null || value.trim().isEmpty) {
                            return 'Enter mobile number';
                          }
                          final digits = value.replaceAll(RegExp(r'[^0-9]'), '');
                          if (digits.length < 7 || digits.length > 12) {
                            return 'Enter valid 9-10 digit number';
                          }
                          return null;
                        },
                      ),
                    ),
                  ],
                ),
                const Spacer(),
                ElevatedButton(
                  onPressed: _isLoading ? null : _submitPhone,
                  child: _isLoading
                      ? const SizedBox(
                          height: 24,
                          width: 24,
                          child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                        )
                      : const Text('Send Verification Code'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
