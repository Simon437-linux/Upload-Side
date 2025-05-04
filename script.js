// Holt die Eingabefelder für Benutzername und Passwort sowie das Login-Formular
const inputUsername = document.getElementById('username');
const inputPassword = document.getElementById('password');
const form = document.getElementById('loginForm');

// Fügt einen Event-Listener für das Absenden des Formulars hinzu
form.addEventListener('submit', async function (e) {
    e.preventDefault(); // Verhindert das Standardverhalten des Formulars (Seitenneuladen)

    // Liest die Werte aus den Eingabefeldern
    const username = inputUsername.value;
    const password = inputPassword.value;

    try {
        // Sendet eine POST-Anfrage an den Server, um den Login zu verarbeiten
        const response = await fetch('upload.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'login',
                username: username,
                password: password
            })
        });

        const data = await response.json(); // Parst die JSON-Antwort des Servers

        // Überprüft, ob der Login erfolgreich war
        if (data.success) {
            // Speichert den Benutzernamen im lokalen Speicher und leitet zur Upload-Seite weiter
            localStorage.setItem('username', username);
            window.location.href = 'upload.html';
        } else {
            // Zeigt eine Fehlermeldung an, falls der Login fehlschlägt
            alert(data.error || 'Login fehlgeschlagen.');
        }
    } catch (error) {
        // Gibt einen Fehler aus, falls die Anfrage fehlschlägt
        console.error('Die Anfrage konnte nicht verarbeitet werden:', error);
    }
});

// Speichert den username im localstorage
localStorage.setItem('username', inputUsername.value);