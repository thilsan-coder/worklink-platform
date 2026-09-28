import 'package:flutter/material.dart';
import '../../../core/theme/app_colors.dart';
import '../providers/worker_discovery_provider.dart';

class WorkerFilterSheet extends StatefulWidget {
  final WorkerDiscoveryProvider provider;

  const WorkerFilterSheet({
    super.key,
    required this.provider,
  });

  @override
  State<WorkerFilterSheet> createState() => _WorkerFilterSheetState();
}

class _WorkerFilterSheetState extends State<WorkerFilterSheet> {
  int? _selectedCategory;
  int? _selectedSkill;
  final _locationController = TextEditingController();
  final _minRateController = TextEditingController();
  final _maxRateController = TextEditingController();
  double? _selectedMinRating;
  bool _verifiedOnly = false;

  @override
  void initState() {
    super.initState();
    _selectedCategory = widget.provider.selectedCategoryId;
    _selectedSkill = widget.provider.selectedSkillId;
    _locationController.text = widget.provider.locationQuery ?? '';
    _minRateController.text = widget.provider.minRate?.toString() ?? '';
    _maxRateController.text = widget.provider.maxRate?.toString() ?? '';
    _selectedMinRating = widget.provider.minRating;
    _verifiedOnly = widget.provider.verifiedOnly;
  }

  @override
  void dispose() {
    _locationController.dispose();
    _minRateController.dispose();
    _maxRateController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final categories = widget.provider.categories;
    final skills = widget.provider.skills;

    return Container(
      padding: EdgeInsets.only(
        top: 20,
        left: 20,
        right: 20,
        bottom: MediaQuery.of(context).viewInsets.bottom + 20,
      ),
      decoration: const BoxDecoration(
        color: AppColors.background,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Filter Workers',
                  style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                ),
                TextButton(
                  onPressed: () {
                    widget.provider.clearFilters();
                    Navigator.of(context).pop();
                  },
                  child: const Text('Reset All'),
                ),
              ],
            ),
            const Divider(),
            const SizedBox(height: 12),

            // Verified Only Toggle
            SwitchListTile(
              contentPadding: EdgeInsets.zero,
              title: const Text(
                'Verified Workers Only',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
              ),
              subtitle: const Text('Show only workers with verified credentials'),
              value: _verifiedOnly,
              activeTrackColor: AppColors.secondary,
              onChanged: (val) => setState(() => _verifiedOnly = val),
            ),
            const SizedBox(height: 16),

            // Category Selector
            const Text('Category', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
            const SizedBox(height: 8),
            DropdownButtonFormField<int?>(
              initialValue: _selectedCategory,
              decoration: const InputDecoration(
                hintText: 'All Categories',
                contentPadding: EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              ),
              items: [
                const DropdownMenuItem<int?>(
                  value: null,
                  child: Text('All Categories'),
                ),
                ...categories.map(
                  (cat) => DropdownMenuItem<int?>(
                    value: cat['id'],
                    child: Text(cat['name'] ?? ''),
                  ),
                ),
              ],
              onChanged: (val) {
                setState(() {
                  _selectedCategory = val;
                  _selectedSkill = null; // reset skill when category changes
                });
              },
            ),
            const SizedBox(height: 16),

            // Skill Selector
            const Text('Specific Skill', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
            const SizedBox(height: 8),
            DropdownButtonFormField<int?>(
              initialValue: _selectedSkill,
              decoration: const InputDecoration(
                hintText: 'All Skills',
                contentPadding: EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              ),
              items: [
                const DropdownMenuItem<int?>(
                  value: null,
                  child: Text('All Skills'),
                ),
                ...skills
                    .where((s) => _selectedCategory == null || s['category_id'] == _selectedCategory)
                    .map(
                      (s) => DropdownMenuItem<int?>(
                        value: s['id'],
                        child: Text(s['name'] ?? ''),
                      ),
                    ),
              ],
              onChanged: (val) => setState(() => _selectedSkill = val),
            ),
            const SizedBox(height: 16),

            // Location Search
            const Text('Location / District', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
            const SizedBox(height: 8),
            TextFormField(
              controller: _locationController,
              decoration: const InputDecoration(
                hintText: 'e.g. Colombo, Kandy, Galle',
                prefixIcon: Icon(Icons.location_on_outlined),
              ),
            ),
            const SizedBox(height: 16),

            // Hourly Rate Range
            const Text('Hourly Rate (LKR)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
            const SizedBox(height: 8),
            Row(
              children: [
                Expanded(
                  child: TextFormField(
                    controller: _minRateController,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(hintText: 'Min Rate'),
                  ),
                ),
                const SizedBox(width: 12),
                const Text('to'),
                const SizedBox(width: 12),
                Expanded(
                  child: TextFormField(
                    controller: _maxRateController,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(hintText: 'Max Rate'),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),

            // Minimum Rating
            const Text('Minimum Rating', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              children: [null, 3.0, 4.0, 4.5].map((rating) {
                final isSelected = _selectedMinRating == rating;
                return ChoiceChip(
                  label: Text(rating == null ? 'Any Rating' : '★ $rating+'),
                  selected: isSelected,
                  selectedColor: AppColors.primary,
                  labelStyle: TextStyle(
                    color: isSelected ? Colors.white : AppColors.textPrimary,
                    fontWeight: FontWeight.bold,
                  ),
                  onSelected: (selected) {
                    setState(() => _selectedMinRating = selected ? rating : null);
                  },
                );
              }).toList(),
            ),
            const SizedBox(height: 24),

            // Apply Button
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () {
                  final minRate = double.tryParse(_minRateController.text.trim());
                  final maxRate = double.tryParse(_maxRateController.text.trim());
                  final location = _locationController.text.trim();

                  widget.provider.applyFilters(
                    categoryId: _selectedCategory,
                    skillId: _selectedSkill,
                    location: location.isNotEmpty ? location : null,
                    minRate: minRate,
                    maxRate: maxRate,
                    minRating: _selectedMinRating,
                    verifiedOnly: _verifiedOnly,
                  );

                  Navigator.of(context).pop();
                },
                child: const Text('Apply Filters'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
