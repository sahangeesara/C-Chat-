---

# 💬 C-Chat | Real-Time Chat Application

C-Chat is a **real-time chat application** built with **Laravel REST API** for the backend and **Vue.js** for the frontend.
The application enables users to send and receive messages instantly using **WebSocket technology with Pusher**.

This project demonstrates modern **full-stack development with real-time communication and JWT authentication**.

---

## 🚀 Features

* 🔐 JWT Authentication (Secure login & registration)
* 💬 Real-time messaging
* 👥 User-to-user chat
* ⚡ Instant message updates using WebSockets
* 🎨 Responsive UI with Bootstrap
* 🔔 Live notifications
* 🗂 Clean MVC backend architecture

---

## 🛠️ Tech Stack

### Programming Language

* PHP

### Backend

* Laravel (REST API)
* JWT Authentication

### Frontend

* Vue.js
* Bootstrap

### Real-Time Communication

* WebSocket
* Pusher

### Tools

* IntelliJ IDEA
* PhpStorm
* Git & GitHub

---

## 📂 Project Structure

```
C-Chat
│
├── client
│   ├── components
│   ├── views
│   ├── services
│   └── assets
│
├── server
│   ├── app
│   ├── routes
│   ├── controllers
│   ├── models
│   └── migrations
│
└── README.md
```

---

## ⚙️ Installation

### 1️⃣ Clone Repository

```bash
git clone https://github.com/sahangeesara/C-Chat.git
```

---

### 2️⃣ Backend Setup (Laravel)

```bash
cd server

composer install

cp .env.example .env

php artisan key:generate

php artisan migrate

php artisan serve
```

---

### 3️⃣ Frontend Setup (Vue.js)

```bash
cd client

npm install

npm run dev
```

---

## 🔌 WebSocket Setup (Pusher)

Update `.env` file:

```
BROADCAST_DRIVER=pusher

PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_key
PUSHER_APP_SECRET=your_secret
PUSHER_APP_CLUSTER=your_cluster
```

---

## 📸 Application Preview

Main features include:

* User authentication
* Real-time chat interface
* Instant message delivery
* Live online communication

*(You can add screenshots here)*

```
![Chat UI](screenshot.png)
```

---

## 👨‍💻 Developer

**Sahan Geesara**

* PHP / Laravel Developer
* Frontend Developer (Vue.js)

📍 Polonnaruwa, Sri Lanka

---

## 📜 License

This project is open-source and available under the MIT License.

---


