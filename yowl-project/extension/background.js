const DEFAULT_APP_URL = "http://localhost:5173";

// Récupère l'URL de l'application YOWL configurée dans les options
async function getAppUrl() {
    const { appUrl } = await chrome.storage.sync.get("appUrl");
    return (appUrl || DEFAULT_APP_URL).replace(/\/$/, "");
}

// Ouvre le composeur de partage de l'application avec l'URL et le titre de la page
async function openShare(url, title) {
    const appUrl = await getAppUrl();
    const params = new URLSearchParams();
    if (url) params.set("url", url);
    if (title) params.set("title", title);
    chrome.windows.create({
        url: `${appUrl}/share?${params.toString()}`,
        type: "popup",
        width: 640,
        height: 760,
    });
}

// Clic sur l'icône de l'extension
chrome.action.onClicked.addListener((tab) => {
    openShare(tab?.url || "", tab?.title || "");
});

// Menu contextuel "Partager sur YOWL"
chrome.runtime.onInstalled.addListener(() => {
    chrome.contextMenus.create({
        id: "yowl-share-page",
        title: "Partager cette page sur YOWL",
        contexts: ["page"],
    });
    chrome.contextMenus.create({
        id: "yowl-share-link",
        title: "Partager ce lien sur YOWL",
        contexts: ["link"],
    });
});

chrome.contextMenus.onClicked.addListener((info, tab) => {
    if (info.menuItemId === "yowl-share-page") {
        openShare(info.pageUrl || tab?.url || "", tab?.title || "");
    } else if (info.menuItemId === "yowl-share-link") {
        openShare(info.linkUrl || "", "");
    }
});
