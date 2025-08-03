# 🏫 Hostel Check-In/Out System

An open-source, lightweight web application for recording hostel student in/out movements using **QR codes** and **WhatsApp notifications**.  
Built with **PHP 8.3**, **MySQL**, and **Bootstrap 5.3** — mobile responsive and easy to deploy.

> **Requested by modern parents** to keep track of their children's movements in hostel schools.

---

## ✨ Features

- 🔐 Role-based login: Superadmin, Admin, Guard, Parent  
- 🎓 Student QR generation (Admin)
- 📲 Guard QR scanning for instant **Check-In / Check-Out**
- 📤 Auto-send WhatsApp message to parent when student scans
- 📋 Parent view with login link to see history of in/out logs
- 📱 Mobile responsive (Bootstrap 5.3)
- 💾 Simple and fast MySQL backend

---

## ⚙️ Tech Stack

- PHP 8.3
- MySQL
- Bootstrap 5.3
- WhatsApp (via URL link)
- Static QR codes (no regeneration needed)

---

## 🚀 Getting Started

### 1. Clone the Repo

```bash
git clone https://github.com/yourusername/hostel-checkin-out.git
cd hostel-checkin-out
````

### 2. Set Up `.env`

Create a `.env` file in the root directory:

```dotenv
HOSTEL_NAME="Asrama Aman"
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=hostel_test
DB_USER=root
DB_PASS=
BASE_URL=http://localhost/hostel-checkin-out/public
SESSION_SECRET=supersecret123
```

### 3. Import Database

Use the provided SQL schema or create your own. Basic structure includes:

* `users`
* `students`
* `parents`
* `inout_logs`

*(SQL schema coming soon)*

### 4. Run on Local Server

Make sure your server (Apache/Nginx) is pointing to `/public` folder as document root.

---

## 📸 How It Works

1. **Admin** generates student QR codes.
2. **Guard** scans QR using phone camera.
3. System logs **Check-In** or **Check-Out** and sends a WhatsApp message to parent.
4. **Parent** clicks the link, logs in, and views the log history.

---

## 📖 License

This project is **Open Source** and **Free to Use**.
If you find it useful, feel free to **donate** or contribute 🙏

---

## 💬 Footer

Please do not remove the footer:

> **Powered By Sabily Enterprise**

---

## 🔬 Status

This system is in **experimental stage** and made as a simple project to address real-world needs with modern tech.
It's designed to be:

* Simple
* Maintainable
* Easy to integrate

---

## 🤝 Contribute / Donate

Pull requests and donations are always welcome!
Thank you for supporting open-source education tools.

```