import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../providers/auth_provider.dart';
import '../../worker/screens/worker_home_screen.dart';

class WorkerProfileSetupScreen extends StatefulWidget {
  const WorkerProfileSetupScreen({super.key});

  @override
  State<WorkerProfileSetupScreen> createState() => _WorkerProfileSetupScreenState();
}

class _WorkerProfileSetupScreenState extends State<WorkerProfileSetupScreen> {
  final _formKey = GlobalKey<FormState>();
  final _bioController = TextEditingController();
  final _expController = TextEditingController(text: '2');
  final _rateController = TextEditingController(text: '1500');
  final _radiusController = TextEditingController(text: '25');
  final _addressController = TextEditingController();

  bool _isLoading = false;

  Future<void> _saveProfile() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    final auth = context.read<AuthProvider>();
    final nav = Navigator.of(context);

    final success = await auth.updateWorkerProfile(
      bio: _bioController.text.trim(),
      experienceYears: int.tryParse(_expController.text.trim()) ?? 0,
      hourlyRate: double.tryParse(_rateController.text.trim()) ?? 0.0,
      serviceAreaRadius: int.tryParse(_radiusController.text.trim()) ?? 25,
      address: _addressController.text.trim(),
      skillIds: [1, 2], // Default sample skill IDs for onboarding
    );

    setState(() => _isLoading = false);

    if (mounted) {
      if (success) {
        nav.pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => const WorkerHomeScreen()),
          (route) => false,
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(auth.errorMessage ?? 'Failed to update worker profile'),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Worker Service Profile Setup')),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24.0),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const Text(
                  'Set Up Your Skilled Worker Profile',
                  style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 8),
                const Text(
                  'Provide your experience, service area radius, and rates to start receiving jobs.',
                  style: TextStyle(color: AppColors.textSecondary),
                ),
                const SizedBox(height: 24),

                TextFormField(
                  controller: _bioController,
                  maxLines: 3,
                  decoration: const InputDecoration(
                    labelText: 'Service Bio / Professional Summary',
                    hintText: 'Describe your expertise, certifications, and service quality...',
                  ),
                  validator: (v) => (v == null || v.isEmpty) ? 'Please enter a bio' : null,
                ),
                const SizedBox(height: 16),

                Row(
                  children: [
                    Expanded(
                      child: TextFormField(
                        controller: _expController,
                        keyboardType: TextInputType.number,
                        decoration: const InputDecoration(
                          labelText: 'Experience (Years)',
                          prefixIcon: Icon(Icons.history_edu_rounded),
                        ),
                        validator: (v) => (v == null || v.isEmpty) ? 'Required' : null,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: TextFormField(
                        controller: _rateController,
                        keyboardType: TextInputType.number,
                        decoration: const InputDecoration(
                          labelText: 'Hourly Rate (LKR)',
                          prefixIcon: Icon(Icons.attach_money_rounded),
                        ),
                        validator: (v) => (v == null || v.isEmpty) ? 'Required' : null,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),

                TextFormField(
                  controller: _radiusController,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(
                    labelText: 'Service Area Radius (km)',
                    prefixIcon: Icon(Icons.map_rounded),
                  ),
                  validator: (v) => (v == null || v.isEmpty) ? 'Please enter radius' : null,
                ),
                const SizedBox(height: 16),

                TextFormField(
                  controller: _addressController,
                  decoration: const InputDecoration(
                    labelText: 'Base Location / City',
                    hintText: 'e.g. Colombo 05',
                    prefixIcon: Icon(Icons.location_city_rounded),
                  ),
                  validator: (v) => (v == null || v.isEmpty) ? 'Please enter base location' : null,
                ),
                const SizedBox(height: 32),

                ElevatedButton(
                  style: ElevatedButton.styleFrom(backgroundColor: AppColors.secondary),
                  onPressed: _isLoading ? null : _saveProfile,
                  child: _isLoading
                      ? const SizedBox(
                          height: 24,
                          width: 24,
                          child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                        )
                      : const Text('Save Worker Profile & Go Online'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
