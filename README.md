# 🧪 Lab-Ventory

A web-based laboratory inventory and equipment lending system. Lab assistants (admins) manage the equipment catalog and approve borrowing requests, while students (users) browse available equipment and submit requests, all with role-based access.

Built as **Laporan 4** for the *Pemrograman Web (PEMWEB)* course, Institut Teknologi PLN.

---

## Features

### Authentication and roles
- Session-based login with two roles: **admin** (lab assistant) and **user** (student)
- Passwords stored as bcrypt hashes and checked with `password_verify()`
- Role checks run on the backend in PHP, so a user cannot open the admin page by typing the URL
- Logged-in users are redirected to the right dashboard automatically

### Admin dashboard (`admin.php`)
- **Create:** add new equipment (name, stock, lab location)
- **Read:** view the full inventory master data and every borrowing request
- **Update:** approve, reject, or mark a request as returned; stock is adjusted automatically (−1 on approval, +1 on return)
- **Delete:** remove equipment, blocked while the item is still on loan (status `approved`)

### User dashboard (`user.php`)
- Browse the equipment catalog with live stock
- Submit a borrowing request with a date picker that does not allow past dates
- View personal borrowing history with status badges

### Validation and UX
- Backend validation for date format, past dates, and stock before a request is saved
- Prepared statements (PDO) for all queries to prevent SQL injection
- Output escaped with `htmlspecialchars()`
- Post/Redirect/Get after every form action to avoid duplicate submissions on refresh
- SweetAlert2 dialogs for confirmations and success or error messages

### Borrowing status flow

```
pending ──► approved ──► dikembalikan (returned)
   │
   └──────► ditolak (rejected)
```

---

## Tech Stack

| Layer | Technology |
| --- | --- |
| Backend | PHP (PDO) |
| Database | MySQL / MariaDB |
| Frontend | HTML, CSS, vanilla JavaScript |
| UI alerts | [SweetAlert2](https://sweetalert2.github.io/) (loaded from CDN) |
| Local server | XAMPP (Apache + MySQL) |

---

## Project Structure

```
lab-ventory/
├── index.php            # Landing page
├── login.php            # Login with session handling
├── logout.php           # Destroys the session and returns to login
├── admin.php            # Admin dashboard (CRUD + request approval)
├── user.php             # Student dashboard (catalog + borrowing)
├── config.php           # PDO database connection + session_start()
├── database.sql         # Table schema and seed data
└── assets/
    └── css/style.css    # Styling (white and blue theme)
```

---

## Database Schema

```
users                     barang                      peminjaman
─────────────             ──────────────              ─────────────────────
id (PK)                   id (PK)                     id (PK)
username (UNIQUE)         nama_barang                 user_id (FK → users.id)
password (bcrypt)         stok                        barang_id (FK → barang.id)
nama_lengkap              lokasi_lab                  tanggal_pinjam
role (admin | user)                                   status (pending | approved |
                                                              ditolak | dikembalikan)
```

`peminjaman` links students to equipment. Both foreign keys use `ON DELETE CASCADE`.

---

## Getting Started

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) (or any Apache + PHP + MySQL stack)
- A browser with internet access (SweetAlert2 is loaded from a CDN)

### Installation

1. **Clone the repository into your web root**

   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/<your-username>/lab-ventory.git
   ```

2. **Start Apache and MySQL** from the XAMPP Control Panel.

3. **Import the database**
   - Open [phpMyAdmin](http://localhost/phpmyadmin)
   - Go to the **Import** tab, choose `database.sql`, and click **Go**
   - The `lab_ventory` database, tables, and seed data are created automatically

4. **Check the database settings** in `config.php` and change them if your setup differs:

   ```php
   $host   = "localhost";
   $dbname = "lab_ventory";
   $dbuser = "root";
   $dbpass = "";
   ```

5. **Open the app** at <http://localhost/lab-ventory/index.php>

### Demo accounts

| Role | Username | Password |
| --- | --- | --- |
| Admin (lab assistant) | `admin` | `password123` |
| User (student) | `hanif` | `password123` |

> These accounts are for local testing only. Change or remove them before deploying anywhere public.

---

## Usage Walkthrough

1. Log in as **hanif** and click **Pinjam** on an item, pick a date, and confirm. The request appears in your history as `Pending`.
2. Log out and log in as **admin**. Under *Daftar Permintaan Peminjaman*, click **Setujui** (approve) or **Tolak** (reject).
3. After approval, the item's stock drops by one. When the item comes back, click **Tandai Selesai / Kembali** and the stock is restored.
4. Try deleting an item that is currently on loan. The system blocks it with a warning.

---

## Known Limitations and Ideas for Improvement

This is a course project, so a few things are intentionally simple:

- No CSRF tokens on the forms
- No `session_regenerate_id()` after login
- Admin actions (approve, reject, return) do not re-check the request's current status on the server, so a replayed request could change stock twice
- A request can be approved even when stock has reached 0 (the stock update is guarded, but the status still changes)
- Deleting an item also deletes its borrowing history through `ON DELETE CASCADE`
- Database credentials are hard-coded in `config.php`; moving them to environment variables or an untracked config file would be safer
- No update/edit form for existing equipment, and no user registration

---

## Author

**Amanda Damayanti**, NIM 202432027
Information Systems, Institut Teknologi PLN

---

## License

Add your license here (for example MIT), or remove this section if the repository is only for coursework.
