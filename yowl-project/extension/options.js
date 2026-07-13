const input = document.getElementById("appUrl");
const status = document.getElementById("status");

chrome.storage.sync.get("appUrl", ({ appUrl }) => {
    input.value = appUrl || "http://localhost:5173";
});

document.getElementById("save").addEventListener("click", async () => {
    const value = input.value.trim().replace(/\/$/, "");
    if (!value) return;
    await chrome.storage.sync.set({ appUrl: value });
    status.textContent = "Enregistré !";
    setTimeout(() => (status.textContent = ""), 2000);
});
