import 'package:flutter/foundation.dart';

class ApiEndpoints {
  // Base API URL (pointing to local Laravel backend)
  // For Android Emulator, use 10.0.2.2. For Web, Windows, or iOS, use localhost.
  static String get baseUrl {
    if (kIsWeb) {
      return 'http://localhost:8000/api';
    }
    if (defaultTargetPlatform == TargetPlatform.android) {
      return 'http://10.0.2.2:8000/api';
    }
    return 'http://localhost:8000/api';
  }

  // Base storage URL for public uploads (avatars, portfolio images)
  static String get storageUrl => '${baseUrl.replaceAll('/api', '')}/storage';

  // Auth
  static String get sendOtp => '$baseUrl/auth/send-otp';
  static String get verifyOtp => '$baseUrl/auth/verify-otp';
  static String get googleLogin => '$baseUrl/auth/google';
  static String get facebookLogin => '$baseUrl/auth/facebook';
  static String get logout => '$baseUrl/auth/logout';
  static String get userProfile => '$baseUrl/user/profile';
  static String get profilePhoto => '$baseUrl/profile/photo';

  // Customer & Worker Profiles
  static String get customerProfile => '$baseUrl/customer/profile';
  static String get workerProfile => '$baseUrl/worker/profile';
  static String get workerPortfolio => '$baseUrl/worker/portfolio';
  static String get workerVerification => '$baseUrl/worker/verification';
  static String get workerDocuments => '$baseUrl/worker/documents';

  // Categories & Skills
  static String get categories => '$baseUrl/categories';
  static String get skills => '$baseUrl/skills';

  // Directory & Jobs
  static String get workers => '$baseUrl/workers';
  static String get jobs => '$baseUrl/jobs';
  static String jobAccept(int id) => '$jobs/$id/accept';
  static String jobReject(int id) => '$jobs/$id/reject';
  static String jobSchedule(int id) => '$jobs/$id/schedule';
  static String jobStart(int id) => '$jobs/$id/start';
  static String jobComplete(int id) => '$jobs/$id/complete';
  static String jobCancel(int id) => '$jobs/$id/cancel';
  static String jobHistory(int id) => '$jobs/$id/history';

  static String get chats => '$baseUrl/chats';
  static String get conversations => '$baseUrl/conversations';
  static String conversationMessages(int id) => '$baseUrl/conversations/$id/messages';
  static String conversationRead(int id) => '$baseUrl/conversations/$id/read';
  static String messageRead(int id) => '$baseUrl/messages/$id/read';
  static String jobConversation(int jobId) => '$baseUrl/jobs/$jobId/conversation';
  static String get reviews => '$baseUrl/reviews';
  static String jobReview(int jobId) => '$baseUrl/jobs/$jobId/review';
  static String workerReviews(int workerId) => '$baseUrl/workers/$workerId/reviews';
  static String workerRating(int workerId) => '$baseUrl/workers/$workerId/rating';
  static String get complaints => '$baseUrl/complaints';
  static String get notifications => '$baseUrl/notifications';
}
