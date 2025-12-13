chrome.runtime.onInstalled.addListener(() => {
  console.log("YOWL Extension installed");
});
chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
  console.log("[YOWL] Background received message:", message);
  if (message.type === "open_popup") {
    console.log("[YOWL] Opening popup window");
    let popupUrl = chrome.runtime.getURL("popup.html");
    if (message.url) {
      popupUrl += `?url=${encodeURIComponent(message.url)}`;
    }
    chrome.windows.create({
      url: popupUrl,
      type: "popup",
      width: 480,
      height: 400
    });
  }
});