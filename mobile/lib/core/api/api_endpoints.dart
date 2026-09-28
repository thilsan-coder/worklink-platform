class ApiEndpoints {
  // Base API URL (pointing to local Laravel backend)
  // For Android Emulator, use 10.0.2.2. For physical device or local web, use localhost/IP.
  static const String baseUrl = 'http://10.0.2.2:8000/api';

  // Auth
  static const String sendOtp = '$baseUrl/auth/send-otp';
  static const String verifyOtp = '$baseUrl/auth/verify-otp';
  static const String googleLogin = '$baseUrl/auth/google';
  static const String facebookLogin = '$baseUrl/auth/facebook';
  static const String logout = '$baseUrl/auth/logout';
  static const String userProfile = '$baseUrl/user/profile';
  static const String profilePhoto = '$baseUrl/profile/photo';

  // Customer & Worker Profiles
  static const String customerProfile = '$baseUrl/customer/profile';
  static const String workerProfile = '$baseUrl/worker/profile';
  static const String workerPortfolio = '$baseUrl/worker/portfolio';
  static const String workerVerification = '$baseUrl/worker/verification';
  static const String workerDocuments = '$baseUrl/worker/documents';

  // Categories & Skills
  static const String categories = '$baseUrl/categories';
  static const String skills = '$baseUrl/skills';

  // Directory & Jobs
  static const String workers = '$baseUrl/workers';
  static const String jobs = '$baseUrl/jobs';
  static const String chats = '$baseUrl/chats';
  static const String reviews = '$baseUrl/reviews';
  static const String complaints = '$baseUrl/complaints';
  static const String notifications = '$baseUrl/notifications';
}
