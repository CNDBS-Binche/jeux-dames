require('dotenv').config();
const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const mysql = require('mysql2/promise');
const session = require('express-session');
const path = require('path');

const app = express();
const server = http.createServer(app);
const io = new Server(server);

// Configuration de la connexion MySQL
const bdd = mysql.createPool({
    host: process.env.DB_HOST,
    user: process.env.DB_USER,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_NAME,
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
});

// Middleware pour gérer les formulaires et le JSON
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Configuration des sessions (partagées plus tard avec Socket.io)
app.use(session({
    secret: process.env.SESSION_SECRET,
    resave: false,
    saveUninitialized: false,
    cookie: { secure: false, maxAge: 24 * 60 * 60 * 1000 } // 1 jour
}));

// Servir les fichiers statiques (tes CSS, images, JS clients)
app.use(express.static(path.join(__dirname, 'public')));

// Exemple de route pour vérifier que tout fonctionne
app.get('/', (req, res) => {
    res.send('<h1>Le serveur Node.js du Jeu de Dames est en ligne ! ⚪🔴</h1>');
});

// Démarrage du serveur
const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
    console.log(`🚀 Serveur actif sur http://localhost:${PORT}`);
});