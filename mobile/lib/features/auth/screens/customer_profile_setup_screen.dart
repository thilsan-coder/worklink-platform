import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../providers/auth_provider.dart';
import '../../customer/screens/customer_home_screen.dart';

class CustomerProfileSetupScreen extends StatefulWidget {
  const CustomerProfileSetupScreen({super.key});

  @override
  State<CustomerProfileSetupScreen> createState() => _CustomerProfileSetupScreenState();
}

class _CustomerProfileSetupScreenState extends State<CustomerProfileSetupScreen> {
  final _formKey = GlobalKey<FormState>();
  final _addressController = TextEditingController();
  final _emergencyContactController = TextEditingController();
  String _paymentMethod = 'cash';
  bool _isLoading = false;

  Future<void> _saveProfile() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    final auth = context.read<AuthProvider>();
    final nav = Navigator.of(context);

    final success = await auth.updateCustomerProfile(
      address: _addressController.text.trim(),
      emergencyContact: _emergencyContactController.text.trim(),
      paymentMethod: _paymentMethod,
    );

    setState(() => _isLoading = false);

    if (mounted) {
      if (success) {
        nav.pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => const CustomerHomeScreen()),
          (route) => false,
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(auth.errorMessage ?? 'Failed to update profile'),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Customer Profile Setup')),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24.0),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const Text(
                  'Complete Your Customer Profile',
                  style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 8),
                const Text(
                  'Set your primary address and contact preferences for service requests.',
                  style: TextStyle(color: AppColors.textSecondary),
                ),
                const SizedBox(height: 24),

                TextFormField(
                  controller: _addressController,
                  decoration: const InputDecoration(
                    labelText: 'Primary Address',
                    hintText: 'e.g. 123 Main St, Colombo 03',
                    prefixIcon: Icon(Icons.home_outlined),
                  ),
                  validator: (v) => (v == null || v.isEmpty) ? 'Please enter primary address' : null,
                ),
                const SizedBox(height: 16),

                TextFormField(
                  controller: _emergencyContactController,
                  keyboardType: TextInputType.phone,
                  decoration: const InputDecoration(
                    labelText: 'Emergency Contact Number',
                    hintText: '+94 77 123 4567',
                    prefixIcon: Icon(Icons.contact_phone_outlined),
                  ),
                ),
                const SizedBox(height: 16),

                const Text('Preferred Payment Method', style: TextStyle(fontWeight: FontWeight.w600)),
                const SizedBox(height: 8),
                DropdownButtonFormField<String>(
                  initialValue: _paymentMethod,
                  decoration: const InputDecoration(
                    prefixIcon: Icon(Icons.payments_outlined),
                  ),
                  items: const [
                    DropdownMenuItem(value: 'cash', child: Text('Cash on Completion')),
                    DropdownMenuItem(value: 'card', child: Text('Credit / Debit Card')),
                    DropdownMenuItem(value: 'bank_transfer', child: Text('Bank Transfer')),
                  ],
                  onChanged: (val) => setState(() => _paymentMethod = val ?? 'cash'),
                ),
                const SizedBox(height: 32),

                ElevatedButton(
                  onPressed: _isLoading ? null : _saveProfile,
                  child: _isLoading
                      ? const SizedBox(
                          height: 24,
                          width: 24,
                          child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                        )
                      : const Text('Complete & Start Exploring'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
