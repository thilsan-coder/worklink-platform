import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../providers/worker_discovery_provider.dart';
import '../widgets/worker_card.dart';
import '../widgets/worker_filter_sheet.dart';

class WorkerDiscoveryScreen extends StatefulWidget {
  final int? initialCategoryId;

  const WorkerDiscoveryScreen({
    super.key,
    this.initialCategoryId,
  });

  @override
  State<WorkerDiscoveryScreen> createState() => _WorkerDiscoveryScreenState();
}

class _WorkerDiscoveryScreenState extends State<WorkerDiscoveryScreen> {
  final _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final provider = context.read<WorkerDiscoveryProvider>();
      provider.fetchCategories();
      provider.fetchSkills();

      if (widget.initialCategoryId != null) {
        provider.selectCategory(widget.initialCategoryId);
      } else {
        provider.fetchWorkers(refresh: true);
      }
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _openFilterSheet(WorkerDiscoveryProvider provider) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => WorkerFilterSheet(provider: provider),
    );
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<WorkerDiscoveryProvider>();

    return Scaffold(
      appBar: AppBar(
        title: const Text('Find Skilled Workers'),
      ),
      body: Column(
        children: [
          // Search & Filter Header
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8.0),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _searchController,
                    decoration: InputDecoration(
                      hintText: 'Search worker name, skill, location...',
                      prefixIcon: const Icon(Icons.search_rounded),
                      suffixIcon: _searchController.text.isNotEmpty
                          ? IconButton(
                              icon: const Icon(Icons.clear_rounded),
                              onPressed: () {
                                _searchController.clear();
                                provider.setSearchQuery('');
                              },
                            )
                          : null,
                    ),
                    onSubmitted: (query) {
                      provider.setSearchQuery(query);
                    },
                  ),
                ),
                const SizedBox(width: 8),
                Stack(
                  children: [
                    IconButton.filled(
                      style: IconButton.styleFrom(
                        backgroundColor: provider.hasActiveFilters ? AppColors.primary : AppColors.surface,
                        foregroundColor: provider.hasActiveFilters ? Colors.white : AppColors.textPrimary,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                          side: const BorderSide(color: AppColors.border),
                        ),
                      ),
                      icon: const Icon(Icons.tune_rounded),
                      onPressed: () => _openFilterSheet(provider),
                    ),
                    if (provider.hasActiveFilters)
                      Positioned(
                        right: 4,
                        top: 4,
                        child: Container(
                          width: 10,
                          height: 10,
                          decoration: const BoxDecoration(
                            color: AppColors.secondary,
                            shape: BoxShape.circle,
                          ),
                        ),
                      ),
                  ],
                ),
              ],
            ),
          ),

          // Categories Horizontal Bar
          if (provider.categories.isNotEmpty)
            SizedBox(
              height: 44,
              child: ListView.builder(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 16),
                itemCount: provider.categories.length + 1,
                itemBuilder: (context, index) {
                  if (index == 0) {
                    final bool isAllSelected = provider.selectedCategoryId == null;
                    return Padding(
                      padding: const EdgeInsets.only(right: 8),
                      child: ChoiceChip(
                        label: const Text('All'),
                        selected: isAllSelected,
                        selectedColor: AppColors.primary,
                        labelStyle: TextStyle(
                          color: isAllSelected ? Colors.white : AppColors.textPrimary,
                          fontWeight: FontWeight.bold,
                        ),
                        onSelected: (_) => provider.selectCategory(null),
                      ),
                    );
                  }

                  final category = provider.categories[index - 1];
                  final bool isSelected = provider.selectedCategoryId == category['id'];

                  return Padding(
                    padding: const EdgeInsets.only(right: 8),
                    child: ChoiceChip(
                      label: Text(category['name'] ?? ''),
                      selected: isSelected,
                      selectedColor: AppColors.primary,
                      labelStyle: TextStyle(
                        color: isSelected ? Colors.white : AppColors.textPrimary,
                        fontWeight: FontWeight.bold,
                      ),
                      onSelected: (_) => provider.selectCategory(category['id']),
                    ),
                  );
                },
              ),
            ),
          const SizedBox(height: 8),

          // Workers List & States
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => provider.fetchWorkers(refresh: true),
              child: _buildWorkerList(provider),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildWorkerList(WorkerDiscoveryProvider provider) {
    if (provider.isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (provider.error != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.error_outline_rounded, size: 48, color: AppColors.error),
              const SizedBox(height: 12),
              Text(
                provider.error!,
                style: const TextStyle(color: AppColors.textSecondary),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 16),
              ElevatedButton.icon(
                onPressed: () => provider.fetchWorkers(refresh: true),
                icon: const Icon(Icons.refresh_rounded),
                label: const Text('Retry'),
              ),
            ],
          ),
        ),
      );
    }

    if (provider.workers.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.search_off_rounded, size: 56, color: AppColors.textMuted),
              const SizedBox(height: 12),
              const Text(
                'No Workers Found',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 6),
              const Text(
                'Try broadening your search or resetting active filters.',
                style: TextStyle(color: AppColors.textSecondary),
                textAlign: TextAlign.center,
              ),
              if (provider.hasActiveFilters) ...[
                const SizedBox(height: 16),
                OutlinedButton.icon(
                  onPressed: () {
                    _searchController.clear();
                    provider.clearFilters();
                  },
                  icon: const Icon(Icons.filter_alt_off_rounded),
                  label: const Text('Clear Filters'),
                ),
              ],
            ],
          ),
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16.0),
      itemCount: provider.workers.length + 1,
      itemBuilder: (context, index) {
        if (index < provider.workers.length) {
          final worker = provider.workers[index];
          return WorkerCard(worker: worker);
        }

        // Pagination / Load More Footer
        if (provider.currentPage < provider.lastPage) {
          return Padding(
            padding: const EdgeInsets.symmetric(vertical: 16.0),
            child: Center(
              child: provider.isLoadingMore
                  ? const CircularProgressIndicator()
                  : OutlinedButton.icon(
                      onPressed: () => provider.loadMoreWorkers(),
                      icon: const Icon(Icons.expand_more_rounded),
                      label: Text('Load More (${provider.totalWorkers - provider.workers.length} remaining)'),
                    ),
            ),
          );
        }

        return Padding(
          padding: const EdgeInsets.symmetric(vertical: 24.0),
          child: Center(
            child: Text(
              'Showing all ${provider.totalWorkers} workers',
              style: const TextStyle(color: AppColors.textMuted, fontSize: 12),
            ),
          ),
        );
      },
    );
  }
}
