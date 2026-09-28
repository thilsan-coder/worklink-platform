import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../providers/profile_provider.dart';

class WorkerProfileScreen extends StatefulWidget {
  const WorkerProfileScreen({super.key});

  @override
  State<WorkerProfileScreen> createState() => _WorkerProfileScreenState();
}

class _WorkerProfileScreenState extends State<WorkerProfileScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _bioController = TextEditingController();
  final _expController = TextEditingController();
  final _rateController = TextEditingController();
  final _radiusController = TextEditingController();
  final _addressController = TextEditingController();

  List<int> _selectedSkillIds = [];
  bool _isEditing = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadData();
    });
  }

  Future<void> _loadData() async {
    final provider = context.read<ProfileProvider>();
    await provider.fetchWorkerProfile();
    await provider.fetchCategoriesAndSkills();
    await provider.fetchPortfolio();
    await provider.fetchVerificationStatus();
    _populateFields();
  }

  void _populateFields() {
    final data = context.read<ProfileProvider>().workerData;
    if (data == null) return;

    final user = data['user'] ?? {};
    final profile = data['profile'] ?? {};
    final skills = profile['skills'] as List? ?? [];

    _nameController.text = user['name'] ?? '';
    _bioController.text = profile['bio'] ?? '';
    _expController.text = (profile['experience_years'] ?? 0).toString();
    _rateController.text = (profile['hourly_rate'] ?? 0).toString();
    _radiusController.text = (profile['service_area_radius_km'] ?? 25).toString();
    _addressController.text = profile['address'] ?? '';
    _selectedSkillIds = skills.map<int>((s) => s['id'] as int).toList();
  }

  Future<void> _pickAndUploadPhoto() async {
    final picker = ImagePicker();
    final pickedFile = await picker.pickImage(source: ImageSource.gallery, imageQuality: 80);

    if (pickedFile != null && mounted) {
      final success = await context.read<ProfileProvider>().uploadProfilePhoto(File(pickedFile.path));
      if (mounted && success) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Profile photo updated successfully')),
        );
      }
    }
  }

  Future<void> _saveProfile() async {
    if (!_formKey.currentState!.validate()) return;

    final provider = context.read<ProfileProvider>();
    final success = await provider.updateWorkerProfile(
      name: _nameController.text.trim(),
      email: provider.workerData?['user']?['email'] ?? '',
      bio: _bioController.text.trim(),
      experienceYears: int.tryParse(_expController.text.trim()) ?? 0,
      hourlyRate: double.tryParse(_rateController.text.trim()) ?? 0.0,
      serviceAreaRadius: int.tryParse(_radiusController.text.trim()) ?? 25,
      address: _addressController.text.trim(),
      skillIds: _selectedSkillIds,
    );

    if (mounted) {
      if (success) {
        setState(() => _isEditing = false);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Worker profile updated successfully')),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(provider.errorMessage ?? 'Update failed'),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }

  void _showAddPortfolioModal() {
    final titleController = TextEditingController();
    final descController = TextEditingController();
    File? selectedImage;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) {
        return StatefulBuilder(
          builder: (modalCtx, setModalState) {
            return Padding(
              padding: EdgeInsets.only(
                bottom: MediaQuery.of(modalCtx).viewInsets.bottom + 20,
                left: 20,
                right: 20,
                top: 20,
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const Text('Add Portfolio Item', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 16),
                  TextField(
                    controller: titleController,
                    decoration: const InputDecoration(labelText: 'Project Title'),
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: descController,
                    decoration: const InputDecoration(labelText: 'Description'),
                  ),
                  const SizedBox(height: 12),
                  InkWell(
                    onTap: () async {
                      final picker = ImagePicker();
                      final image = await picker.pickImage(source: ImageSource.gallery, imageQuality: 80);
                      if (image != null) {
                        setModalState(() => selectedImage = File(image.path));
                      }
                    },
                    child: Container(
                      height: 100,
                      decoration: BoxDecoration(
                        color: AppColors.surface,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: selectedImage != null
                          ? Image.file(selectedImage!, fit: BoxFit.cover)
                          : const Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.add_a_photo_outlined, color: AppColors.textMuted),
                                SizedBox(height: 4),
                                Text('Select Work Photo', style: TextStyle(fontSize: 12, color: AppColors.textMuted)),
                              ],
                            ),
                    ),
                  ),
                  const SizedBox(height: 20),
                  ElevatedButton(
                    style: ElevatedButton.styleFrom(backgroundColor: AppColors.secondary),
                    onPressed: () async {
                      if (titleController.text.isEmpty || selectedImage == null) return;
                      final nav = Navigator.of(modalCtx);
                      final success = await context.read<ProfileProvider>().addPortfolioItem(
                            title: titleController.text.trim(),
                            description: descController.text.trim(),
                            imageFile: selectedImage!,
                          );
                      if (success) {
                        nav.pop();
                      }
                    },
                    child: const Text('Upload Portfolio Item'),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  void _showDocumentVerificationModal() {
    final docNumController = TextEditingController();
    String docType = 'id_card';
    File? selectedDoc;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) {
        return StatefulBuilder(
          builder: (modalCtx, setModalState) {
            return Padding(
              padding: EdgeInsets.only(
                bottom: MediaQuery.of(modalCtx).viewInsets.bottom + 20,
                left: 20,
                right: 20,
                top: 20,
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const Text('Submit Identity Document', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 16),
                  DropdownButtonFormField<String>(
                    initialValue: docType,
                    decoration: const InputDecoration(labelText: 'Document Type'),
                    items: const [
                      DropdownMenuItem(value: 'id_card', child: Text('National ID Card (NIC)')),
                      DropdownMenuItem(value: 'license', child: Text('Driving License')),
                      DropdownMenuItem(value: 'certificate', child: Text('Trade Certificate')),
                    ],
                    onChanged: (val) => setModalState(() => docType = val ?? 'id_card'),
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: docNumController,
                    decoration: const InputDecoration(labelText: 'Document / Registration Number'),
                  ),
                  const SizedBox(height: 12),
                  InkWell(
                    onTap: () async {
                      final picker = ImagePicker();
                      final file = await picker.pickImage(source: ImageSource.gallery, imageQuality: 80);
                      if (file != null) {
                        setModalState(() => selectedDoc = File(file.path));
                      }
                    },
                    child: Container(
                      height: 100,
                      decoration: BoxDecoration(
                        color: AppColors.surface,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: selectedDoc != null
                          ? const Center(child: Text('Document Image Selected ✔', style: TextStyle(color: AppColors.secondary, fontWeight: FontWeight.bold)))
                          : const Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.upload_file_rounded, color: AppColors.textMuted),
                                SizedBox(height: 4),
                                Text('Attach Document Photo', style: TextStyle(fontSize: 12, color: AppColors.textMuted)),
                              ],
                            ),
                    ),
                  ),
                  const SizedBox(height: 20),
                  ElevatedButton(
                    style: ElevatedButton.styleFrom(backgroundColor: AppColors.secondary),
                    onPressed: () async {
                      if (selectedDoc == null) return;
                      final nav = Navigator.of(modalCtx);
                      final success = await context.read<ProfileProvider>().uploadVerificationDocument(
                            documentType: docType,
                            documentNumber: docNumController.text.trim(),
                            documentFile: selectedDoc!,
                          );
                      if (success) {
                        nav.pop();
                      }
                    },
                    child: const Text('Submit for Admin Review'),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<ProfileProvider>();
    final data = provider.workerData;
    final user = data?['user'] ?? {};
    final profile = data?['profile'] ?? {};
    final completion = provider.workerCompletion;
    final portfolio = provider.portfolioItems;
    final verificationStatus = profile['verification_status'] ?? 'unverified';

    final String? avatarPath = user['avatar']?.toString();
    final String? avatarUrl = (avatarPath != null && avatarPath.isNotEmpty)
        ? 'http://10.0.2.2:8000/storage/${avatarPath.replaceAll(RegExp(r'^/'), '')}'
        : null;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Worker Service Profile'),
        actions: [
          IconButton(
            icon: Icon(_isEditing ? Icons.close_rounded : Icons.edit_rounded),
            onPressed: () {
              setState(() {
                if (_isEditing) _populateFields();
                _isEditing = !_isEditing;
              });
            },
          ),
        ],
      ),
      body: provider.isLoading && data == null
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(20.0),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Center(
                      child: Stack(
                        children: [
                          CircleAvatar(
                            radius: 54,
                            backgroundColor: AppColors.secondary.withAlpha(30),
                            backgroundImage: avatarUrl != null ? NetworkImage(avatarUrl) : null,
                            child: avatarUrl == null
                                ? const Icon(Icons.engineering_rounded, size: 54, color: AppColors.secondary)
                                : null,
                          ),
                          Positioned(
                            bottom: 0,
                            right: 0,
                            child: InkWell(
                              onTap: _pickAndUploadPhoto,
                              child: Container(
                                padding: const EdgeInsets.all(8),
                                decoration: const BoxDecoration(
                                  color: AppColors.secondary,
                                  shape: BoxShape.circle,
                                ),
                                child: const Icon(Icons.camera_alt_rounded, size: 18, color: Colors.white),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),

                    Center(
                      child: Column(
                        children: [
                          Text(user['name'] ?? 'Worker', style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
                          const SizedBox(height: 4),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                            decoration: BoxDecoration(
                              color: verificationStatus == 'verified'
                                  ? AppColors.secondary.withAlpha(20)
                                  : Colors.amber.withAlpha(20),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(
                                  verificationStatus == 'verified' ? Icons.verified_rounded : Icons.shield_outlined,
                                  size: 14,
                                  color: verificationStatus == 'verified' ? AppColors.secondary : Colors.amber.shade700,
                                ),
                                const SizedBox(width: 4),
                                Text(
                                  verificationStatus == 'verified' ? 'Verified Professional' : 'Verification: ${verificationStatus.toUpperCase()}',
                                  style: TextStyle(
                                    fontSize: 12,
                                    fontWeight: FontWeight.bold,
                                    color: verificationStatus == 'verified' ? AppColors.secondary : Colors.amber.shade700,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 24),

                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: AppColors.surface,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              const Text('Worker Profile Completion', style: TextStyle(fontWeight: FontWeight.bold)),
                              Text('$completion%', style: const TextStyle(fontWeight: FontWeight.bold, color: AppColors.secondary)),
                            ],
                          ),
                          const SizedBox(height: 8),
                          ClipRRect(
                            borderRadius: BorderRadius.circular(8),
                            child: LinearProgressIndicator(
                              value: completion / 100.0,
                              minHeight: 8,
                              backgroundColor: AppColors.border,
                              color: AppColors.secondary,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 24),

                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16.0),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Identity Verification Status', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                            const SizedBox(height: 6),
                            Text('Status: ${verificationStatus.toUpperCase()}', style: const TextStyle(fontSize: 13, color: AppColors.textSecondary)),
                            if (profile['verification_rejection_reason'] != null)
                              Padding(
                                padding: const EdgeInsets.only(top: 6.0),
                                child: Text('Rejection Reason: ${profile['verification_rejection_reason']}', style: const TextStyle(color: AppColors.error, fontSize: 12)),
                              ),
                            const SizedBox(height: 12),
                            OutlinedButton.icon(
                              onPressed: _showDocumentVerificationModal,
                              icon: const Icon(Icons.upload_file_rounded),
                              label: const Text('Submit Verification Documents'),
                            ),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 24),

                    TextFormField(
                      controller: _nameController,
                      enabled: _isEditing,
                      decoration: const InputDecoration(labelText: 'Full Name', prefixIcon: Icon(Icons.person_outline_rounded)),
                      validator: (v) => (v == null || v.isEmpty) ? 'Required' : null,
                    ),
                    const SizedBox(height: 16),

                    TextFormField(
                      controller: _bioController,
                      enabled: _isEditing,
                      maxLines: 3,
                      decoration: const InputDecoration(labelText: 'Professional Bio', prefixIcon: Icon(Icons.description_outlined)),
                    ),
                    const SizedBox(height: 16),

                    Row(
                      children: [
                        Expanded(
                          child: TextFormField(
                            controller: _expController,
                            enabled: _isEditing,
                            keyboardType: TextInputType.number,
                            decoration: const InputDecoration(labelText: 'Experience (Yrs)', prefixIcon: Icon(Icons.history_edu_rounded)),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: TextFormField(
                            controller: _rateController,
                            enabled: _isEditing,
                            keyboardType: TextInputType.number,
                            decoration: const InputDecoration(labelText: 'Hourly Rate (LKR)', prefixIcon: Icon(Icons.attach_money_rounded)),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),

                    TextFormField(
                      controller: _addressController,
                      enabled: _isEditing,
                      decoration: const InputDecoration(labelText: 'Base Service Address', prefixIcon: Icon(Icons.location_on_outlined)),
                    ),
                    const SizedBox(height: 24),

                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('Skills & Expertise', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                        if (_isEditing)
                          TextButton(
                            onPressed: () {
                              _showSkillSelectionDialog(provider.skills);
                            },
                            child: const Text('+ Select Skills'),
                          ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: provider.skills
                          .where((s) => _selectedSkillIds.contains(s['id']))
                          .map((s) => Chip(
                                label: Text(s['name']),
                                backgroundColor: AppColors.secondary.withAlpha(20),
                                side: const BorderSide(color: AppColors.secondary),
                              ))
                          .toList(),
                    ),
                    const SizedBox(height: 24),

                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('Work Portfolio Showcase', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                        IconButton(
                          icon: const Icon(Icons.add_photo_alternate_rounded, color: AppColors.secondary),
                          onPressed: _showAddPortfolioModal,
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),

                    if (portfolio.isEmpty)
                      const Text('No portfolio items added yet.', style: TextStyle(color: AppColors.textMuted))
                    else
                      SizedBox(
                        height: 140,
                        child: ListView.builder(
                          scrollDirection: Axis.horizontal,
                          itemCount: portfolio.length,
                          itemBuilder: (ctx, idx) {
                            final item = portfolio[idx];
                            final String imgPath = item['image_path'].toString().replaceAll(RegExp(r'^/'), '');
                            return Container(
                              width: 140,
                              margin: const EdgeInsets.only(right: 12),
                              decoration: BoxDecoration(
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: AppColors.border),
                              ),
                              child: Stack(
                                children: [
                                  ClipRRect(
                                    borderRadius: BorderRadius.circular(12),
                                    child: Image.network(
                                      'http://10.0.2.2:8000/storage/$imgPath',
                                      height: 140,
                                      width: 140,
                                      fit: BoxFit.cover,
                                      errorBuilder: (context, error, stackTrace) => const Center(child: Icon(Icons.image_not_supported_rounded)),
                                    ),
                                  ),
                                  Positioned(
                                    top: 4,
                                    right: 4,
                                    child: InkWell(
                                      onTap: () async {
                                        await provider.deletePortfolioItem(item['id']);
                                      },
                                      child: Container(
                                        padding: const EdgeInsets.all(4),
                                        decoration: const BoxDecoration(color: Colors.black54, shape: BoxShape.circle),
                                        child: const Icon(Icons.delete_rounded, size: 16, color: Colors.white),
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            );
                          },
                        ),
                      ),
                    const SizedBox(height: 32),

                    if (_isEditing)
                      ElevatedButton(
                        style: ElevatedButton.styleFrom(backgroundColor: AppColors.secondary),
                        onPressed: provider.isLoading ? null : _saveProfile,
                        child: provider.isLoading
                            ? const SizedBox(height: 24, width: 24, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                            : const Text('Save Worker Profile Changes'),
                      ),
                  ],
                ),
              ),
            ),
    );
  }

  void _showSkillSelectionDialog(List<dynamic> allSkills) {
    showDialog(
      context: context,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (dialogCtx, setDialogState) {
            return AlertDialog(
              title: const Text('Select Skills'),
              content: SizedBox(
                width: double.maxFinite,
                child: ListView.builder(
                  shrinkWrap: true,
                  itemCount: allSkills.length,
                  itemBuilder: (context, index) {
                    final skill = allSkills[index];
                    final isSelected = _selectedSkillIds.contains(skill['id']);
                    return CheckboxListTile(
                      title: Text(skill['name']),
                      subtitle: Text(skill['category']?['name'] ?? ''),
                      value: isSelected,
                      onChanged: (checked) {
                        setDialogState(() {
                          if (checked == true) {
                            _selectedSkillIds.add(skill['id']);
                          } else {
                            _selectedSkillIds.remove(skill['id']);
                          }
                        });
                        setState(() {});
                      },
                    );
                  },
                ),
              ),
              actions: [
                TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Done')),
              ],
            );
          },
        );
      },
    );
  }
}
