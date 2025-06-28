# 🚀 Project Auto Creator Dashboard

A powerful PHP-based automation tool to manage your GitHub repositories *from a simple web dashboard*.

---

## ✨ Features

✅ Connects to your GitHub account with a personal access token  
✅ Checks your connection status live on the dashboard  
✅ Logs all actions in `run.log`  
✅ One-click button to **edit and commit changes to the README** in your chosen repository  
✅ Fully private and secure  
✅ Modern, responsive, and clean interface

---

## ⚙️ How It Works

- Connects using the GitHub REST API
- Uses a personal access token you generate on GitHub
- Instead of creating new repositories each time, it **updates** the existing repository's README
- Commits changes automatically
- Logs everything so you can track the history
- Runs manually or on an automated cron job/server timer
- Keeps your workflow clean without cluttering your GitHub with too many repos

---

## 📁 Tech Stack

- PHP
- GitHub REST API
- HTML + CSS for the dashboard
- Simple log file tracking (`run.log`)

---

## 📌 Why Use This?

✅ Automate documentation changes  
✅ Test GitHub workflows  
✅ Easily trigger README updates  
✅ Keep a single private repo tidy  
✅ Never have to manually push changes again

---

## 🛠️ Setup

1. Place the PHP files on your web server  
2. Generate a **GitHub Personal Access Token** with `repo` scope  
3. Update the `utils.php` with your token  
4. Visit `index.php` to use the dashboard  
5. Tap the button to commit your updated README content!

---

> **Built with ❤️ by Utkarsh Singh**  
> Automate smarter, manage easier. 🚀
