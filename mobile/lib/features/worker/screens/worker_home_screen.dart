import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../auth/providers/auth_provider.dart';
import '../../auth/screens/login_choice_screen.dart';
import '../../customer/screens/customer_home_screen.dart';
import '../../jobs/screens/worker_jobs_screen.dart';
import '../../profile/screens/worker_profile_screen.dart';

class WorkerHomeScreen extends StatefulWidget {
  const WorkerHomeScreen({super.key});

  @override
  State<WorkerHomeScreen> createState() => _WorkerHomeScreenState();
}

class _WorkerHomeScreenState extends State<WorkerHomeScreen> {
  int _currentIndex = 0;

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final userName = auth.user?['name'] ?? 'Worker';

    return Scaffold(
      appBar: AppBar(
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: AppColors.secondary.withAlpha(20),
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: AppColors.secondary),
              ),
              child: Row(
                children: [
                  const Icon(Icons.engineering_rounded, size: 16, color: AppColors.secondary),
                  const SizedBox(width: 4),
                  Text('Worker Mode ($userName)', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppColors.secondary)),
                ],
              ),
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.swap_horiz_rounded),
            tooltip: 'Switch to Customer Mode',
            onPressed: () async {
              final nav = Navigator.of(context);
              await auth.switchRole('customer');
              if (mounted) {
                nav.pushReplacement(
                  MaterialPageRoute(builder: (_) => const CustomerHomeScreen()),
                );
              }
            },
          ),
          IconButton(
            icon: const Icon(Icons.logout_rounded),
            tooltip: 'Logout',
            onPressed: () async {
              final nav = Navigator.of(context);
              await auth.logout();
              if (mounted) {
                nav.pushAndRemoveUntil(
                  MaterialPageRoute(builder: (_) => const LoginChoiceScreen()),
                  (route) => false,
                );
              }
            },
          ),
        ],
      ),
      body: IndexedStack(
        index: _currentIndex,
        children: [
          const WorkerJobsScreen(),
          const WorkerJobsScreen(),
          const Center(child: Text('Job Chats (Phase 8)')),
          const WorkerProfileScreen(),
        ],
      ),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _currentIndex,
        onTap: (index) => setState(() => _currentIndex = index),
        selectedItemColor: AppColors.secondary,
        unselectedItemColor: AppColors.textMuted,
        items: const [
          BottomNavigationBarItem(icon: Icon(Icons.dashboard_rounded), label: 'Dashboard'),
          BottomNavigationBarItem(icon: Icon(Icons.work_history_outlined), label: 'Jobs'),
          BottomNavigationBarItem(icon: Icon(Icons.chat_bubble_outline_rounded), label: 'Chats'),
          BottomNavigationBarItem(icon: Icon(Icons.badge_outlined), label: 'Profile'),
        ],
      ),
    );
  }
}
