console.log("[YOWL] Content script loaded");
const btn = document.createElement("button");
btn.textContent = "💬 YOWL Comment";
btn.style.position = "fixed";
btn.style.bottom = "20px";
btn.style.right = "20px";
btn.style.background = "#FF6B35";
btn.style.color = "white";
btn.style.border = "none";
btn.style.padding = "10px 15px";
btn.style.borderRadius = "8px";
btn.style.cursor = "pointer";
btn.style.zIndex = "999999";

btn.onclick = () => {
    console.log("[YOWL] Button clicked, sending message to background");
    chrome.runtime.sendMessage({ type: "open_popup", url: window.location.href });
};

document.body.appendChild(btn);