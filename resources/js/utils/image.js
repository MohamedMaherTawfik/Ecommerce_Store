const DEFAULT_IMAGE = "/images/categorey.webp";

const isUnsplashImage = (path) =>
    typeof path === "string" && path.includes("images.unsplash.com");

const withImageWidth = (path, width) => {
    if (!isUnsplashImage(path) || !width) {
        return path;
    }

    const url = new URL(path);
    url.searchParams.set("w", String(width));
    url.searchParams.set("auto", "format");
    url.searchParams.set("fit", "crop");
    url.searchParams.set("q", "80");

    return url.toString();
};

export const imageUrl = (path, width = null) => {
    if (!path) {
        return DEFAULT_IMAGE;
    }

    if (/^(https?:)?\/\//.test(path)) {
        return withImageWidth(path, width);
    }

    if (path.startsWith("/")) {
        return path;
    }

    return `/storage/${path}`;
};

export const imageSrcset = (path, widths = [480, 768, 1200]) => {
    if (!isUnsplashImage(path)) {
        return undefined;
    }

    return widths
        .map((width) => `${withImageWidth(path, width)} ${width}w`)
        .join(", ");
};
