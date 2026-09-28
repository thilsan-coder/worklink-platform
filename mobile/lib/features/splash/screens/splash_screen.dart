import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../auth/providers/auth_provider.dart';
import '../../auth/screens/login_choice_screen.dart';
import '../../customer/screens/customer_home_screen.dart';
import '../../worker/screens/worker_home_screen.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _checkSession();
    });
  }

  Future<void> _checkSession() async {
    final auth = context.read<AuthProvider>();
    await auth.restoreSession();

    if (!mounted) return;

    if (auth.isAuthenticated) {
      if (auth.activeRole == 'worker') {
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(builder: (_) => const WorkerHomeScreen()),
        );
      } else {
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(builder: (_) => const CustomerHomeScreen()),
        );
      }
    } else {
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => const LoginChoiceScreen()),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.primary,
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(24),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withAlpha(30),
                    blurRadius: 20,
                    offset: const Offset(0, 10),
                  ),
                ],
              ),
              child: const Icon(
                Icons.work_outline_rounded,
                size: 64,
                color: AppColors.primary,
              ),
            ),
            const SizedBox(height: 24),
            const Text(
              'WorkLink',
              style: TextStyle(
                fontSize: 36,
                fontWeight: FontWeight.bold,
                color: Colors.white,
                letterSpacing: 1.2,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Connect. Work. Complete.',
              style: TextStyle(
                fontSize: 16,
                color: Colors.white.withAlpha(200),
                letterSpacing: 0.5,
              ),
            ),
            const SizedBox(height: 48),
            const CircularProgressIndicator(
              valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
            ),
          ],
        ),
      ),
    );
  }
}
