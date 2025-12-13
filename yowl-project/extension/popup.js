const apiBase = " http://localhost:8000/api";

const loginDiv = document.getElementById("login");
const reviewDiv = document.getElementById("reviewForm");
const loginBtn = document.getElementById("loginBtn");
const sendBtn = document.getElementById("sendBtn");
const logoutBtn = document.getElementById("logoutBtn");
const errorEl = document.getElementById("error");
const currentUrlEl = document.getElementById("currentUrl");

// Vérifie si le token est déjà stocké
chrome.storage.local.get("token", async({ token }) => {
    if (token) showReviewForm();
});

// Connexion utilisateur
loginBtn.addEventListener("click", async() => {
    const email = document.getElementById("email").value;
    const password = document.getElementById("password").value;

    try {
        const res = await fetch(`${apiBase}/login`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ email, password }),
        });

        const data = await res.json();

        if (res.ok && data.token) {
            await chrome.storage.local.set({ token: data.token });
            showReviewForm();
        } else {
            errorEl.textContent = data.message || "Login error";
        }
    } catch (err) {
        errorEl.textContent = "Network error";
    }
});

// Affiche le formulaire et affiche l’URL actuelle
async function showReviewForm() {
    loginDiv.style.display = "none";
    reviewDiv.style.display = "block";

    // Cherche un paramètre d'URL ?url=...
    const params = new URLSearchParams(window.location.search);
    let url = params.get("url");
    if (!url) {
        // Fallback : récupère l'URL de l'onglet actif
        try {
            const tabs = await chrome.tabs.query({ active: true, currentWindow: true });
            url = tabs[0]?.url || "";
        } catch {
            url = "";
        }
    }
    currentUrlEl.textContent = url ? `Actual link : ${url}` : "";
    currentUrlEl.dataset.url = url;
}

// Envoi de la review
sendBtn.addEventListener("click", async() => {
    const content = document.getElementById("content").value.trim();
    const tags = document.getElementById("tags").value.trim();
    const link = currentUrlEl.dataset.url;
    const { token } = await chrome.storage.local.get("token");

    if (!token) return alert("Please log in again.");
    if (!token) return alert("Please log in again..");
    if (!content) return alert("The content is empty.");

    // tag_id (fixer un par défaut pour l’extension)
    const tag_id = tags ? parseInt(tags) || 1 : 1;

    const payload = {
        content,
        link,
        tag_id,
        is_published: true,
    };

    const res = await fetch(`${apiBase}/reviews`, {
        method: "POST",
        headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
        },
        body: JSON.stringify(payload),
    });

    const data = await res.json();

    const successMsg = document.getElementById("successMsg");
    if (res.ok) {
        // Affiche le message de succès
        successMsg.textContent = "Review saved successfully !";
        successMsg.classList.remove("hidden");
        document.getElementById("content").value = "";
        document.getElementById("tags").value = "";
        setTimeout(() => {
            successMsg.classList.add("hidden");
        }, 3000);
    } else {
        console.error(data);
        successMsg.textContent = "Error while saving.";
        successMsg.classList.remove("hidden");
        setTimeout(() => {
            successMsg.classList.add("hidden");
        }, 3000);
    }
});

// Déconnexion
logoutBtn.addEventListener("click", async() => {
    await chrome.storage.local.remove("token");
    reviewDiv.style.display = "none";
    loginDiv.style.display = "block";
});
