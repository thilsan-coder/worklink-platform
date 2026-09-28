import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../providers/auth_provider.dart';
import 'phone_auth_screen.dart';
import 'role_selection_screen.dart';
import '../../customer/screens/customer_home_screen.dart';
import '../../worker/screens/worker_home_screen.dart';

class LoginChoiceScreen extends StatefulWidget {
  const LoginChoiceScreen({super.key});

  @override
  State<LoginChoiceScreen> createState() => _LoginChoiceScreenState();
}

class _LoginChoiceScreenState extends State<LoginChoiceScreen> {
  bool _isLoading = false;

  Future<void> _handleGoogleSignIn() async {
    setState(() => _isLoading = true);

    final auth = context.read<AuthProvider>();
    final nav = Navigator.of(context);
    final scaffoldMessenger = ScaffoldMessenger.of(context);

    // OAuth Credentials
    final success = await auth.googleLogin(
      googleId: 'google_user_10203040',
      email: 'user.worklink@gmail.com',
      name: 'Google WorkLink User',
      role: 'customer',
    );

    setState(() => _isLoading = false);

    if (mounted) {
      if (success) {
        if (auth.isNewUser) {
          nav.pushAndRemoveUntil(
            MaterialPageRoute(builder: (_) => const RoleSelectionScreen()),
            (route) => false,
          );
        } else if (auth.activeRole == 'worker') {
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
            content: Text(auth.errorMessage ?? 'Google Login failed'),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }

  Future<void> _handleFacebookSignIn() async {
    setState(() => _isLoading = true);

    final auth = context.read<AuthProvider>();
    final nav = Navigator.of(context);
    final scaffoldMessenger = ScaffoldMessenger.of(context);

    final success = await auth.facebookLogin(
      facebookId: 'fb_user_50607080',
      email: 'user.worklink@gmail.com',
      name: 'Facebook WorkLink User',
      role: 'customer',
    );

    setState(() => _isLoading = false);

    if (mounted) {
      if (success) {
        if (auth.isNewUser) {
          nav.pushAndRemoveUntil(
            MaterialPageRoute(builder: (_) => const RoleSelectionScreen()),
            (route) => false,
          );
        } else if (auth.activeRole == 'worker') {
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
            content: Text(auth.errorMessage ?? 'Facebook Login failed'),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const Spacer(),
              Center(
                child: Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: AppColors.primary.withAlpha(20),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: const Icon(
                    Icons.handyman_rounded,
                    size: 56,
                    color: AppColors.primary,
                  ),
                ),
              ),
              const SizedBox(height: 24),
              const Text(
                'Welcome to WorkLink',
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 28,
                  fontWeight: FontWeight.bold,
                  color: AppColors.textPrimary,
                ),
              ),
              const SizedBox(height: 8),
              const Text(
                'Find skilled workers near you or start earning as a verified professional.',
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 15,
                  color: AppColors.textSecondary,
                  height: 1.4,
                ),
              ),
              const Spacer(),

              // Phone Auth Button
              ElevatedButton.icon(
                onPressed: _isLoading
                    ? null
                    : () {
                        Navigator.of(context).push(
                          MaterialPageRoute(builder: (_) => const PhoneAuthScreen()),
                        );
                      },
                icon: const Icon(Icons.phone_iphone_rounded),
                label: const Text('Continue with Phone Number'),
              ),
              const SizedBox(height: 12),

              // Google Social Login Button
              OutlinedButton.icon(
                onPressed: _isLoading ? null : _handleGoogleSignIn,
                icon: const Icon(Icons.g_mobiledata_rounded, size: 28),
                label: const Text('Continue with Google'),
              ),
              const SizedBox(height: 12),

              // Facebook Social Login Button
              OutlinedButton.icon(
                onPressed: _isLoading ? null : _handleFacebookSignIn,
                icon: const Icon(Icons.facebook_rounded, color: Color(0xFF1877F2)),
                label: const Text('Continue with Facebook'),
              ),
              const SizedBox(height: 24),

              Text(
                'By continuing, you agree to WorkLink Terms of Service & Privacy Policy.',
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 12,
                  color: AppColors.textMuted,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
