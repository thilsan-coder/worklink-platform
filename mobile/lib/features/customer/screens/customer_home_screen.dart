import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../auth/providers/auth_provider.dart';
import '../../auth/screens/login_choice_screen.dart';
import '../../worker/screens/worker_home_screen.dart';

class CustomerHomeScreen extends StatefulWidget {
  const CustomerHomeScreen({super.key});

  @override
  State<CustomerHomeScreen> createState() => _CustomerHomeScreenState();
}

class _CustomerHomeScreenState extends State<CustomerHomeScreen> {
  int _currentIndex = 0;

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final userName = auth.user?['name'] ?? 'Customer';

    return Scaffold(
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Location',
              style: TextStyle(fontSize: 12, color: AppColors.textSecondary),
            ),
            Row(
              children: const [
                Icon(Icons.location_on_rounded, size: 16, color: AppColors.primary),
                SizedBox(width: 4),
                Text('Current Location', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
                Icon(Icons.keyboard_arrow_down_rounded, size: 16),
              ],
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.swap_horiz_rounded),
            tooltip: 'Switch to Worker Mode',
            onPressed: () async {
              final nav = Navigator.of(context);
              await auth.switchRole('worker');
              if (mounted) {
                nav.pushReplacement(
                  MaterialPageRoute(builder: (_) => const WorkerHomeScreen()),
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
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Hello, $userName 👋',
              style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 4),
            const Text(
              'What service do you need today?',
              style: TextStyle(color: AppColors.textSecondary),
            ),
            const SizedBox(height: 20),

            TextField(
              decoration: InputDecoration(
                hintText: 'Search plumbers, electricians, carpenters...',
                prefixIcon: const Icon(Icons.search_rounded),
                suffixIcon: Container(
                  margin: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: AppColors.primary,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Icon(Icons.tune_rounded, color: Colors.white, size: 20),
                ),
              ),
            ),
            const SizedBox(height: 24),

            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [AppColors.primary, AppColors.primaryDark],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: const [
                        Text(
                          'Verified Skilled Workers',
                          style: TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.bold,
                            fontSize: 18,
                          ),
                        ),
                        SizedBox(height: 6),
                        Text(
                          'Get top-rated experts with background verification.',
                          style: TextStyle(color: Colors.white70, fontSize: 12),
                        ),
                      ],
                    ),
                  ),
                  const Icon(Icons.verified_rounded, size: 48, color: Colors.white),
                ],
              ),
            ),
            const SizedBox(height: 24),

            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Categories',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
                TextButton(onPressed: () {}, child: const Text('See All')),
              ],
            ),
            const SizedBox(height: 12),

            GridView.count(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              crossAxisCount: 3,
              crossAxisSpacing: 12,
              mainAxisSpacing: 12,
              children: [
                _buildCategoryCard('Plumbing', Icons.plumbing_rounded, Colors.blue),
                _buildCategoryCard('Electrical', Icons.bolt_rounded, Colors.amber),
                _buildCategoryCard('Carpentry', Icons.handyman_rounded, Colors.orange),
                _buildCategoryCard('AC Fixing', Icons.ac_unit_rounded, Colors.cyan),
                _buildCategoryCard('Painting', Icons.format_paint_rounded, Colors.purple),
                _buildCategoryCard('Cleaning', Icons.cleaning_services_rounded, Colors.green),
              ],
            ),
          ],
        ),
      ),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _currentIndex,
        onTap: (index) => setState(() => _currentIndex = index),
        selectedItemColor: AppColors.primary,
        unselectedItemColor: AppColors.textMuted,
        items: const [
          BottomNavigationBarItem(icon: Icon(Icons.explore_rounded), label: 'Explore'),
          BottomNavigationBarItem(icon: Icon(Icons.assignment_outlined), label: 'My Jobs'),
          BottomNavigationBarItem(icon: Icon(Icons.chat_bubble_outline_rounded), label: 'Chats'),
          BottomNavigationBarItem(icon: Icon(Icons.person_outline_rounded), label: 'Profile'),
        ],
      ),
    );
  }

  Widget _buildCategoryCard(String title, IconData icon, Color color) {
    return Card(
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: () {},
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: color.withAlpha(25),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, color: color, size: 28),
            ),
            const SizedBox(height: 8),
            Text(
              title,
              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
            ),
          ],
        ),
      ),
    );
  }
}
