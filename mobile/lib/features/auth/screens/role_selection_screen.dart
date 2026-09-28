import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../providers/auth_provider.dart';
import 'customer_profile_setup_screen.dart';
import 'worker_profile_setup_screen.dart';

class RoleSelectionScreen extends StatefulWidget {
  const RoleSelectionScreen({super.key});

  @override
  State<RoleSelectionScreen> createState() => _RoleSelectionScreenState();
}

class _RoleSelectionScreenState extends State<RoleSelectionScreen> {
  String _selectedRole = 'customer';

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Choose Account Type')),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                'How do you want to use WorkLink?',
                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontSize: 22,
                      fontWeight: FontWeight.bold,
                    ),
              ),
              const SizedBox(height: 8),
              const Text(
                'Select your primary role. You can switch between roles anytime.',
                style: TextStyle(color: AppColors.textSecondary),
              ),
              const SizedBox(height: 32),

              // Customer Card Option
              GestureDetector(
                onTap: () => setState(() => _selectedRole = 'customer'),
                child: Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: _selectedRole == 'customer'
                        ? AppColors.primary.withAlpha(15)
                        : AppColors.surface,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(
                      color: _selectedRole == 'customer'
                          ? AppColors.primary
                          : AppColors.border,
                      width: 2,
                    ),
                  ),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: const BoxDecoration(
                          color: AppColors.primary,
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(Icons.person_search_rounded, color: Colors.white, size: 32),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: const [
                            Text(
                              'I want to hire skilled workers',
                              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                            ),
                            SizedBox(height: 4),
                            Text(
                              'Find plumbers, electricians, carpenters & track progress.',
                              style: TextStyle(fontSize: 12, color: AppColors.textSecondary),
                            ),
                          ],
                        ),
                      ),
                      if (_selectedRole == 'customer')
                        const Icon(Icons.check_circle_rounded, color: AppColors.primary, size: 28),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),

              // Worker Card Option
              GestureDetector(
                onTap: () => setState(() => _selectedRole = 'worker'),
                child: Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: _selectedRole == 'worker'
                        ? AppColors.secondary.withAlpha(15)
                        : AppColors.surface,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(
                      color: _selectedRole == 'worker'
                          ? AppColors.secondary
                          : AppColors.border,
                      width: 2,
                    ),
                  ),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: const BoxDecoration(
                          color: AppColors.secondary,
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(Icons.engineering_rounded, color: Colors.white, size: 32),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: const [
                            Text(
                              'I am a skilled worker looking for jobs',
                              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                            ),
                            SizedBox(height: 4),
                            Text(
                              'Create service profile, receive job requests & earn.',
                              style: TextStyle(fontSize: 12, color: AppColors.textSecondary),
                            ),
                          ],
                        ),
                      ),
                      if (_selectedRole == 'worker')
                        const Icon(Icons.check_circle_rounded, color: AppColors.secondary, size: 28),
                    ],
                  ),
                ),
              ),
              const Spacer(),

              ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: _selectedRole == 'worker' ? AppColors.secondary : AppColors.primary,
                ),
                onPressed: () async {
                  final nav = Navigator.of(context);
                  await context.read<AuthProvider>().switchRole(_selectedRole);
                  if (!mounted) return;

                  if (_selectedRole == 'worker') {
                    nav.push(MaterialPageRoute(builder: (_) => const WorkerProfileSetupScreen()));
                  } else {
                    nav.push(MaterialPageRoute(builder: (_) => const CustomerProfileSetupScreen()));
                  }
                },
                child: const Text('Continue to Profile Setup'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
