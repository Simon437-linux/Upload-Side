document.addEventListener('DOMContentLoaded', () => {
    // 1) Username prüfen & Hidden-Feld befüllen
    const username = localStorage.getItem('username');
    if (!username) {
      alert('Bitte melde dich zuerst an.');
      window.location.href = 'index.html';
      return;
    }
    const usernameInput = document.getElementById('username');
    usernameInput.value = username;
  
    // 2) Form, Input, Counter & Galerie referenzieren
    const form = document.getElementById('upload-form');
    const fileInput = document.getElementById('upload');
    const counter = document.getElementById('counter');
    const gallery = document.getElementById('gallery');
  
    // 3) Galerie & Zähler aktualisieren
    async function updateCounterAndGallery() {
      try {
        const res = await fetch(`upload.php?username=${encodeURIComponent(username)}`);
        const data = await res.json();
        if (data.error) {
          console.error(data.error);
          return;
        }
        // Filtert password.json heraus
        const images = data.files.filter(f => f !== 'password.json');
        counter.textContent = `${images.length}/20`;
        gallery.innerHTML = '';
        images.forEach(file => {
          const img = document.createElement('img');
          img.src = `uploads/${username}/${file}`;
          img.alt = file;
          img.classList.add('gallery-image');
          img.addEventListener('click', () => {
            if (confirm(`Bild "${file}" löschen?`)) deleteImage(file);
          });
          gallery.appendChild(img);
        });
      } catch (err) {
        console.error('Fehler beim Laden der Galerie:', err);
      }
    }
  
    // 4) Bild löschen
    async function deleteImage(fileName) {
      try {
        const res = await fetch('upload.php', {
          method: 'DELETE',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `username=${encodeURIComponent(username)}&file=${encodeURIComponent(fileName)}`
        });
        const data = await res.json();
        if (data.success) {
          updateCounterAndGallery();
        } else {
          alert(data.error || 'Fehler beim Löschen.');
        }
      } catch (err) {
        console.error('Fehler beim Löschen:', err);
      }
    }
  
    // 5) Upload per AJAX
    form.addEventListener('submit', async e => {
      e.preventDefault();
      // Vorherige Anzahl ermitteln
      const prevCount = parseInt(counter.textContent.split('/')[0], 10) || 0;
      const selectedCount = fileInput.files.length;
      if (selectedCount === 0) {
        alert('Bitte wähle mindestens eine Datei aus.');
        return;
      }
  
      const formData = new FormData(form);
      formData.set('username', username);
  
      try {
        const res = await fetch('upload.php', { method: 'POST', body: formData });
        const data = await res.json();
  
        if (data.success) {
          const newCount = data.count || 0;
          const uploadedNow = newCount - prevCount;
          alert(`${uploadedNow} Datei(en) erfolgreich hochgeladen.`);
          form.reset();
          usernameInput.value = username;
          updateCounterAndGallery();
        } else {
          alert(data.error || 'Fehler beim Hochladen.');
        }
      } catch (err) {
        console.error('Fehler beim Hochladen:', err);
        alert('Beim Hochladen ist ein Fehler aufgetreten.');
      }
    });
  
    // Initial
    updateCounterAndGallery();
  });
  