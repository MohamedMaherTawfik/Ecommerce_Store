const cache = new Map();

export function readInitialData(id) {
    if (cache.has(id)) {
        return cache.get(id);
    }

    const element = document.getElementById(id);
    if (!element?.textContent) {
        cache.set(id, null);
        return null;
    }

    try {
        const data = JSON.parse(element.textContent);
        cache.set(id, data);
        return data;
    } catch {
        cache.set(id, null);
        return null;
    }
}
