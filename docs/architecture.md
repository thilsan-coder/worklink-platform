# WorkLink — System Architecture & Design Specification

## 1. Overview
WorkLink is a multi-role skilled worker service marketplace platform connecting **Customers** with **Skilled Workers**, managed and governed by a web-based **Admin Panel**.

---

## 2. Component Blueprint

```
+-----------------------------------------------------------------------+
|                           WORKLINK PLATFORM                           |
+-----------------------------------+-----------------------------------+
|  Mobile Application (Flutter)     |  Web Admin Panel (HTML/CSS/JS)    |
|  - Customer Role                  |  - Verification & Governance      |
|  - Worker Role                    |  - Dispute Resolution & Analytics |
+-----------------------------------+-----------------------------------+
                                    |
                                API Requests (JSON / Bearer Token)
                                    |
                                    v
+-----------------------------------------------------------------------+
|                    Backend API Layer (Laravel REST)                    |
|  - Authentication (Phone OTP, OAuth Google/Facebook, JWT/Sanctum)    |
|  - Core Service Controllers (Jobs, Profiles, Reviews, Complaints)     |
|  - Middleware (Role Authorization, Rate Limiting, File Validation)    |
+-----------------------------------------------------------------------+
                                    |
                         Database & External Services
                                    |
        +---------------------------+---------------------------+
        |                                                       |
        v                                                       v
+-------------------------------+               +-------------------------------+
|  MariaDB / MySQL Database     |               | External Integrations         |
|  - Relational Schema          |               | - Firebase Cloud Messaging    |
|  - Spatial/Locational Index   |               | - Google Maps Platform        |
+-------------------------------+               +-------------------------------+
```

---

## 3. Tech Stack Matrix

| Subsystem | Technology | Architectural Role |
| :--- | :--- | :--- |
| **Mobile App** | Flutter 3.44+ & Dart 3.12+ | Dual-role mobile application (Customer & Worker) |
| **Backend API** | Laravel 11.x / 13.x (PHP 8.4) | RESTful API server, Business Logic, Auth & Database |
| **Database** | MariaDB / MySQL | Relational Data Storage with Foreign Keys & Indexes |
| **Admin Panel** | Vite + Web App Scaffold | Governance dashboard for platform administrators |
| **Push Push** | Firebase Cloud Messaging (FCM) | Real-time push alerts for job state updates & messages |
| **Maps & Geo** | Google Maps API & Location SDK | Geocoding, location pinning, and navigation |

---

## 4. Key Security Policies
1. **Zero Client Trust**: User identity and authorization MUST strictly derive from authenticated backend tokens (`Sanctum`), never from request payload parameters.
2. **Account Linking Policy**: Single unified identity per person across Phone OTP, Google, and Facebook credentials using verified email / phone mapping.
3. **Admin Isolation**: Admin web authentication uses a separate authentication guard and table (`admin_users`), disconnected from mobile mobile customer/worker tables.
