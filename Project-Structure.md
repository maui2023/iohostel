## 📁 Project Folder Structure

```
hostel-checkin-out/
│
├── .env                      # Contains config like HOSTEL_NAME, DB credentials
├── config/
│   └── database.php          # DB connection setup
│
├── public/                   # Publicly accessible files
│   ├── assets/               # CSS, JS, images, etc.
│   │   ├── css/
│   │   ├── js/
│   │   └── img/
│   ├── index.php             # Entry point (can be login selector)
│   └── qr/                   # Generated student QR codes
│
├── routes/
│   └── web.php               # Route definitions / central page handling
│
├── controllers/
│   ├── AuthController.php    # Handles login/logout per role
│   ├── SuperadminController.php
│   ├── AdminController.php
│   ├── GuardController.php
│   └── ParentController.php
│
├── models/
│   ├── User.php
│   ├── Student.php
│   ├── ParentModel.php
│   └── InOutLog.php
│
├── views/                    # HTML with embedded PHP (Bootstrap UI)
│   ├── auth/                 # Login pages
│   │   ├── login_superadmin.php
│   │   ├── login_admin.php
│   │   ├── login_guard.php
│   │   └── login_parent.php
│   ├── layouts/              # Shared headers, navs, footers
│   │   ├── header.php
│   │   ├── footer.php
│   │   └── sidebar.php
│   ├── dashboard/            # Dashboards by role
│   │   ├── superadmin.php
│   │   ├── admin.php
│   │   ├── guard.php
│   │   └── parent.php
│   ├── students/
│   │   ├── list.php
│   │   ├── create.php
│   │   └── view_qr.php
│   └── logs/
│       └── list.php
│
├── helpers/
│   ├── auth.php              # Login/session utilities
│   ├── qr.php                # QR generation logic
│   └── whatsapp.php          # WhatsApp link generator
│
├── scan.php                  # QR endpoint for guard to scan
├── logout.php                # Universal logout
├── composer.json             # (optional) if you use libraries
└── README.md                 # Project info
```

---

### 🌐 `.env` Sample

```env
HOSTEL_NAME=Asrama Aman
DB_HOST=localhost
DB_USER=root
DB_PASS=password
DB_NAME=hostel_system
```

---

### ✅ Notes

* All roles are **separated logically**: views, controllers, and dashboards.
* QR codes are saved in `/public/qr/` and linked via student record.
* Guard scans go to `scan.php`, which processes the student ID/token and prompts for In/Out.
* WhatsApp messages are built in `helpers/whatsapp.php`.
