<div align="center">
  
  # 💸 MoneyTrack
  
  **A clean, calm, and intuitive personal finance and expense tracking web application.**

  [![PHP Version](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
  [![MySQL](https://img.shields.io/badge/MySQL-Supported-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
  [![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
  [![Maintenance](https://img.shields.io/badge/Maintained%3F-yes-green.svg?style=for-the-badge)](https://github.com/arshlankhan786)
  
  <br />
  
  <!-- Replace the src below with a real screenshot of your dashboard once hosted -->
  <img src="https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&q=80&w=1200&h=500" alt="MoneyTrack Interface" style="border-radius: 12px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
  
  <br /><br />
</div>

---

## 🌟 The Purpose: What Issue Does It Solve?

<img align="right" width="350" src="https://images.unsplash.com/photo-1633158829585-23ba8f7c8caf?auto=format&fit=crop&q=80&w=600" alt="Calm tracking" style="border-radius: 12px; margin-left: 20px;">

In today's fast-paced world, tracking where your money comes from and where it goes can be overwhelming. Many financial tools are either too complex (built for businesses rather than individuals) or too noisy (filled with unnecessary graphs and upsells). 

**MoneyTrack solves this by providing a quiet, private space for your money story.** 

It allows you to:
- 🎯 **See exactly** what comes in, what goes out, and what stays with you.
- 🧘‍♂️ **Avoid "financial anxiety"** by keeping the interface simple and focused on daily/monthly habits.
- ⏱️ **Record everyday entries in seconds** to build a clear picture of your financial health over time.

<br clear="both">

---

## ✨ Features

<table>
  <tr>
    <td width="50%">
      <h3>🔒 Private by Design</h3>
      <p>User-scoped data means your financial information is secure and isolated. Features session-based authentication, CSRF protection, and PDO prepared statements.</p>
    </td>
    <td width="50%">
      <h3>📊 Calm Dashboard</h3>
      <p>Get an immediate snapshot of your all-time balance and net flow for the current period without any overwhelming clutter.</p>
    </td>
  </tr>
  <tr>
    <td width="50%">
      <h3>🎨 Custom Categories</h3>
      <p>Make it yours! Create your own income sources and expense categories. Choose from a variety of icons and colors to match your real life.</p>
    </td>
    <td width="50%">
      <h3>⚡ Fast Entry</h3>
      <p>Slide-out drawers make adding transactions quick and painless, whether you are on a desktop or on the go with your mobile.</p>
    </td>
  </tr>
</table>

---

## 🚀 How to Use It

1. **Create an Account:** Start by clicking "Create account" on the landing page. All you need is your name, email, and a secure password.
2. **Set up your Categories:** Head over to the **Categories** tab. Add your income sources (e.g., Salary, Freelance) and expense categories (e.g., Groceries, Rent, Coffee).
3. **Record Activity:** Click the **+** (Add activity) buttons to record when you receive money or spend it. 
4. **Monitor your Dashboard:** Your Dashboard will automatically update to reflect your current balance, total received, and total spent for the period.

---

## 🛠️ Deployment Guide (Hostinger / cPanel)

<img align="right" width="250" src="https://img.icons8.com/color/250/000000/web-hosting.png" alt="Hosting">

This directory is the deployable PHP 8+ / MySQL application. Upload it to a standard document root (like Hostinger's `public_html`).

### Prerequisites
- `PHP 8.0` or higher
- `MySQL` / `MariaDB` Database
- `mod_rewrite` enabled (for `.htaccess`)

### Step 1: Database Setup
1. Create a MySQL database and user in your hosting panel.
2. Import the `database/schema.sql` file into your newly created database via phpMyAdmin.

### Step 2: Application Configuration
1. Upload all files to your web server.
2. Locate and edit `config/database.php` to include your database credentials. (You can also set these as environment variables `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).
3. Ensure the `.htaccess` file is present in the root directory to handle clean URLs.

### Step 3: Test
Navigate to your domain. You should see the landing page and be able to create a new account!

<br clear="both">

---

## 👨‍💻 Credits

<div align="center">
  <img src="https://avatars.githubusercontent.com/arshlankhan786" width="100" style="border-radius: 50%;">
  <br>
  Built and maintained with ❤️ by <strong><a href="https://github.com/arshlankhan786">@arshlankhan786</a></strong>
</div>
