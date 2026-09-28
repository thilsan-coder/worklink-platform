import 'dart:async';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../providers/auth_provider.dart';
import '../../customer/screens/customer_home_screen.dart';
import '../../worker/screens/worker_home_screen.dart';
import 'role_selection_screen.dart';

class OtpVerifyScreen extends StatefulWidget {
  final String phone;
  final String? name;

  const OtpVerifyScreen({
    super.key,
    required this.phone,
    this.name,
  });

  @override
  State<OtpVerifyScreen> createState() => _OtpVerifyScreenState();
}

class _OtpVerifyScreenState extends State<OtpVerifyScreen> {
  final _otpController = TextEditingController();
  bool _isLoading = false;
  int _resendCooldown = 60;
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _startCooldownTimer();
  }

  void _startCooldownTimer() {
    setState(() => _resendCooldown = 60);
    _timer?.cancel();
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (_resendCooldown > 0) {
        setState(() => _resendCooldown--);
      } else {
        timer.cancel();
      }
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    _otpController.dispose();
    super.dispose();
  }

  Future<void> _resendOtp() async {
    if (_resendCooldown > 0) return;

    final scaffoldMessenger = ScaffoldMessenger.of(context);
    final auth = context.read<AuthProvider>();

    final success = await auth.sendOtp(widget.phone);
    if (success) {
      _startCooldownTimer();
      scaffoldMessenger.showSnackBar(
        const SnackBar(content: Text('OTP code resent successfully.')),
      );
    } else {
      scaffoldMessenger.showSnackBar(
        SnackBar(
          content: Text(auth.errorMessage ?? 'Resend failed'),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }

  Future<void> _verifyOtp() async {
    final otpCode = _otpController.text.trim();
    if (otpCode.length != 6) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please enter 6-digit OTP code')),
      );
      return;
    }

    setState(() => _isLoading = true);

    final authProvider = context.read<AuthProvider>();
    final nav = Navigator.of(context);
    final scaffoldMessenger = ScaffoldMessenger.of(context);

    final success = await authProvider.verifyOtp(
      phone: widget.phone,
      otpCode: otpCode,
      name: widget.name,
    );

    setState(() => _isLoading = false);

    if (mounted) {
      if (success) {
        if (authProvider.isNewUser) {
          nav.pushAndRemoveUntil(
            MaterialPageRoute(builder: (_) => const RoleSelectionScreen()),
            (route) => false,
          );
        } else if (authProvider.activeRole == 'worker') {
          nav.pushAndRemoveUntil(
            MaterialPageRoute(builder: (_) => const WorkerHomeScreen()),
            (route) => false,
          );
        } else {
          nav.pushAndRemoveUntil(
            MaterialPageRoute(builder: (_) => const CustomerHomeScreen()),
            (route) => false,
          );
        }
      } else {
        scaffoldMessenger.showSnackBar(
          SnackBar(
            content: Text(authProvider.errorMessage ?? 'Invalid or Expired OTP'),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Verify Phone')),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                'Verification Code Sent',
                style: Theme.of(context).textTheme.titleLarge?.copyWith(fontSize: 24),
              ),
              const SizedBox(height: 8),
              Text(
                'Enter the 6-digit code sent to ${widget.phone}',
                style: const TextStyle(color: AppColors.textSecondary),
              ),
              const SizedBox(height: 24),

              TextFormField(
                controller: _otpController,
                keyboardType: TextInputType.number,
                maxLength: 6,
                textAlign: TextAlign.center,
                style: const TextStyle(fontSize: 28, letterSpacing: 8, fontWeight: FontWeight.bold),
                decoration: const InputDecoration(
                  hintText: '000000',
                  counterText: '',
                ),
                onChanged: (val) {
                  if (val.length == 6 && !_isLoading) {
                    _verifyOtp();
                  }
                },
              ),
              const SizedBox(height: 16),

              // Resend OTP Row
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Text('Didn\'t receive code? ', style: TextStyle(color: AppColors.textSecondary)),
                  TextButton(
                    onPressed: _resendCooldown == 0 ? _resendOtp : null,
                    child: Text(
                      _resendCooldown > 0 ? 'Resend in ${_resendCooldown}s' : 'Resend OTP',
                      style: TextStyle(
                        fontWeight: FontWeight.bold,
                        color: _resendCooldown == 0 ? AppColors.primary : AppColors.textMuted,
                      ),
                    ),
                  ),
                ],
              ),

              const Spacer(),
              ElevatedButton(
                onPressed: _isLoading ? null : _verifyOtp,
                child: _isLoading
                    ? const SizedBox(
                        height: 24,
                        width: 24,
                        child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                      )
                    : const Text('Verify & Continue'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
