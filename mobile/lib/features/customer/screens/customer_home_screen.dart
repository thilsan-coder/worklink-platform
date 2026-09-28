import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';
import '../../../core/theme/app_colors.dart';
import '../../auth/providers/auth_provider.dart';
import '../../auth/screens/login_choice_screen.dart';
import '../../discovery/providers/worker_discovery_provider.dart';
import '../../discovery/screens/worker_discovery_screen.dart';
import '../../profile/screens/customer_profile_screen.dart';
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
      body: IndexedStack(
        index: _currentIndex,
        children: [
          _buildExploreView(context, userName),
          const _CustomerJobsTab(),
          const Center(child: Text('Chat Conversations (Phase 7)')),
          const CustomerProfileScreen(),
        ],
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

  Widget _buildExploreView(BuildContext context, String userName) {
    final discoveryProvider = context.watch<WorkerDiscoveryProvider>();
    final categories = discoveryProvider.categories;

    return SingleChildScrollView(
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

          // Search trigger bar
          InkWell(
            onTap: () {
              Navigator.of(context).push(
                MaterialPageRoute(builder: (_) => const WorkerDiscoveryScreen()),
              );
            },
            borderRadius: BorderRadius.circular(12),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: AppColors.border),
              ),
              child: Row(
                children: [
                  const Icon(Icons.search_rounded, color: AppColors.textMuted),
                  const SizedBox(width: 12),
                  const Expanded(
                    child: Text(
                      'Search plumbers, electricians, carpenters...',
                      style: TextStyle(color: AppColors.textMuted, fontSize: 14),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.all(6),
                    decoration: BoxDecoration(
                      color: AppColors.primary,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Icon(Icons.tune_rounded, color: Colors.white, size: 18),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 24),

          // Verified Workers Promo Banner
          InkWell(
            onTap: () {
              Navigator.of(context).push(
                MaterialPageRoute(builder: (_) => const WorkerDiscoveryScreen()),
              );
            },
            child: Container(
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
                          'Browse top-rated experts with background verification.',
                          style: TextStyle(color: Colors.white70, fontSize: 12),
                        ),
                      ],
                    ),
                  ),
                  const Icon(Icons.verified_rounded, size: 44, color: Colors.white),
                ],
              ),
            ),
          ),
          const SizedBox(height: 24),

          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'Browse Categories',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              ),
              TextButton(
                onPressed: () {
                  Navigator.of(context).push(
                    MaterialPageRoute(builder: (_) => const WorkerDiscoveryScreen()),
                  );
                },
                child: const Text('See All'),
              ),
            ],
          ),
          const SizedBox(height: 12),

          if (categories.isEmpty)
            const Center(child: Padding(padding: EdgeInsets.all(24), child: CircularProgressIndicator()))
          else
            GridView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 3,
                crossAxisSpacing: 12,
                mainAxisSpacing: 12,
                childAspectRatio: 1.0,
              ),
              itemCount: categories.length,
              itemBuilder: (context, index) {
                final cat = categories[index];
                return _buildCategoryCard(
                  context,
                  cat['id'],
                  cat['name'] ?? '',
                  _getCategoryIcon(cat['name']),
                  _getCategoryColor(index),
                );
              },
            ),
        ],
      ),
    );
  }

  Widget _buildCategoryCard(BuildContext context, int catId, String title, IconData icon, Color color) {
    return Card(
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: () {
          Navigator.of(context).push(
            MaterialPageRoute(
              builder: (_) => WorkerDiscoveryScreen(initialCategoryId: catId),
            ),
          );
        },
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
              textAlign: TextAlign.center,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }

  IconData _getCategoryIcon(String? name) {
    final lower = (name ?? '').toLowerCase();
    if (lower.contains('plumb')) return Icons.plumbing_rounded;
    if (lower.contains('electr')) return Icons.bolt_rounded;
    if (lower.contains('carpent')) return Icons.handyman_rounded;
    if (lower.contains('paint')) return Icons.format_paint_rounded;
    if (lower.contains('ac') || lower.contains('air')) return Icons.ac_unit_rounded;
    if (lower.contains('clean')) return Icons.cleaning_services_rounded;
    if (lower.contains('garden')) return Icons.yard_rounded;
    if (lower.contains('mason')) return Icons.foundation_rounded;
    return Icons.build_rounded;
  }

  Color _getCategoryColor(int index) {
    final colors = [
      Colors.blue,
      Colors.amber,
      Colors.orange,
      Colors.cyan,
      Colors.purple,
      Colors.green,
      Colors.teal,
      Colors.indigo,
    ];
    return colors[index % colors.length];
  }
}

class _CustomerJobsTab extends StatefulWidget {
  const _CustomerJobsTab();

  @override
  State<_CustomerJobsTab> createState() => _CustomerJobsTabState();
}

class _CustomerJobsTabState extends State<_CustomerJobsTab> {
  bool _isLoading = true;
  String? _error;
  List<dynamic> _jobs = [];

  @override
  void initState() {
    super.initState();
    _fetchCustomerJobs();
  }

  Future<void> _fetchCustomerJobs() async {
    try {
      final res = await ApiClient.instance.client.get(ApiEndpoints.jobs);
      if (res.statusCode == 200 && res.data['success'] == true) {
        setState(() {
          _jobs = res.data['data']['data'] ?? [];
          _isLoading = false;
        });
      }
    } on DioException catch (e) {
      setState(() {
        _error = e.response?.data['message'] ?? 'Failed to load jobs';
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) return const Center(child: CircularProgressIndicator());
    if (_error != null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.error_outline_rounded, color: AppColors.error, size: 40),
            const SizedBox(height: 8),
            Text(_error!),
            ElevatedButton(onPressed: _fetchCustomerJobs, child: const Text('Retry')),
          ],
        ),
      );
    }

    if (_jobs.isEmpty) {
      return const Center(
        child: Text('No job requests submitted yet.', style: TextStyle(color: AppColors.textMuted)),
      );
    }

    return RefreshIndicator(
      onRefresh: _fetchCustomerJobs,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _jobs.length,
        itemBuilder: (context, index) {
          final job = _jobs[index];
          return Card(
            margin: const EdgeInsets.only(bottom: 12),
            child: ListTile(
              title: Text(job['title'] ?? 'Job Request', style: const TextStyle(fontWeight: FontWeight.bold)),
              subtitle: Text('${job['description']}\nStatus: ${job['status']}'),
              trailing: Chip(
                label: Text(job['status'] ?? '', style: const TextStyle(fontSize: 11, color: Colors.white)),
                backgroundColor: AppColors.primary,
              ),
            ),
          );
        },
      ),
    );
  }
}
